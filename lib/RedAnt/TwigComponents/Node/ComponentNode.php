<?php

namespace RedAnt\TwigComponents\Node;

use RedAnt\TwigComponents\Property;
use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\AbstractExpression as Expression;
use Twig\Node\Node;

/**
 * Defines and compiles a Component node.
 *
 * Creates a PHP array with the default values (just like {% set %}), and merges the supplied options hash:
 *
 *     $context['button'] = ['container' => 'button', 'label' => '']
 *
 * @author  Gert Wijnalda <gert@redant.nl>
 */
class ComponentNode extends Node
{
    /**
     * @var string
     */
    protected $componentName;

    /**
     * @var Compiler
     */
    protected $compiler;

    public function __construct($name, Expression $value, array $properties, Expression $options, $line, $tag = null)
    {
        parent::__construct(
            [ 'value' => $value, 'options' => $options ],
            [ 'name' => $name, 'properties' => $properties ], $line, $tag);
    }

    /**
     * @param Compiler $compiler
     *
     * @throws SyntaxError
     */
    public function compile(Compiler $compiler): void
    {
        $this->compiler = $compiler;
        $this->componentName = $this->getAttribute('name');

        $properties = $this->getAttribute('properties');

        $context = [
            'component' => '$context[\'' . $this->getAttribute('name') . '\']',
            'options'   => '$context[\'' . $this->getNode('options')->getAttribute('name') . '\']'
        ];

        // Add debugging info
        $compiler->addDebugInfo($this);

        // Write base component configuration
        $compiler
            ->write($context['component'] . ' = ')
            ->subcompile($this->getNode('value'))
            ->raw(";\n");

        // Check supplied properties
        $this->compileUndefinedPropertyTest($context['options'], $context['component']);
        $this->compilePropertyTests($properties, $context['options']);

        // Write array_merge between component configuration and supplied options
        $compiler
            ->write($context['component'] . ' = ')
            ->raw('array_merge(' . $context['component'])
            ->raw(', ' . $context['options'] . ");\n");
    }

    protected function compileUndefinedPropertyTest(string $contextOptions, string $contextComponent)
    {
        $this->compiler
            ->write('foreach (array_keys(' . $contextOptions . ') as $option) {' . PHP_EOL)->indent()
            ->write('$defined = false;' . PHP_EOL)
            ->write('$candidates = [];' . PHP_EOL)
            ->write('foreach (array_keys(' . $contextComponent . ') as $definedOption) {' . PHP_EOL)->indent()
            ->write('if ($definedOption === $option) {' . PHP_EOL)->indent()
            ->write('$defined = true;' . PHP_EOL)
            ->write('continue;' . PHP_EOL)
            ->outdent()->write('}' . PHP_EOL . PHP_EOL)
            ->write('if (false !== strpos($definedOption, $option) || levenshtein($option, $definedOption) <= strlen($option) / 2) {' . PHP_EOL)->indent()
            ->write('$candidates[] = $definedOption;' . PHP_EOL)
            ->outdent()->write('}' . PHP_EOL)
            ->outdent()->write('}' . PHP_EOL)
            ->write('if (!$defined) {' . PHP_EOL)->indent()
            ->write('$message = sprintf(\'Component "%s" does not contain property "%s".\', \'' . $this->componentName . '\', $option);' . PHP_EOL)
            ->write('if (!empty($candidates)) {' . PHP_EOL)->indent()
            ->write('$message .= sprintf(\' Did you mean "%s"?\', join(\'", "\', $candidates));' . PHP_EOL)
            ->outdent()->write('}' . PHP_EOL)
            ->write('throw new \Twig\Error\RuntimeError($message);' . PHP_EOL)
            ->outdent()->write('}' . PHP_EOL)
            ->outdent()->write('}' . PHP_EOL);
    }

    /**
     * Compile a type check line for every defined property.
     *
     * @param Property[] $properties
     * @param string     $contextOptions
     *
     * @throws SyntaxError
     */
    protected function compilePropertyTests(array $properties, $contextOptions): void
    {
        /** @var Property $property */
        foreach ($properties as $property) {
            $option = $contextOptions . '[\'' . $property->getName() . '\']';

            $message = sprintf('Property "%s" ', $property->getName()) .
                ($property->isRequired() ? 'is required' : '');

            $test = $property->isRequired()
                ? '!isset(' . $option . ') || '
                : 'isset(' . $option . ') && ';

            $typeTest = $this->getTestForType($property->getType());
            if ($typeTest !== null) {
                $message .= ($property->isRequired() ? ', and ' : '')
                    . sprintf('should be of type "%s"', $property->getType());
                $test .= '!' . $typeTest;
            }

            $this->compiler
                ->write('if (' . sprintf($test, $option) . ') {' . PHP_EOL)->indent()
                ->indent()->write('throw new \Twig\Error\RuntimeError(\'' . $message . '.\');' . PHP_EOL)
                ->outdent()->write('}' . PHP_EOL);
        }
    }

    /**
     * Get type check function for a defined (scalar) type.
     *
     * @param string|null $type
     *
     * @return string|null
     * @throws SyntaxError
     */
    private function getTestForType(?string $type)
    {
        switch ($type) {
            case null:
                return null;
            case 'string':
                return 'is_string(%s)';
            case 'bool':
            case 'boolean':
                return 'is_bool(%s)';
            case 'int':
            case 'integer':
                return 'is_int(%s)';
            case 'float':
            case 'double':
                return 'is_float(%s)';
            case 'array':
                return 'is_array(%s)';
            default:
                if ($nullable = stripos($type, '?') === 0) {
                    $type = substr($type, 1);
                }

                if ('[]' === substr($type, -2)) {
                    return 'is_array(%s)';
                }

                if (class_exists($type) or interface_exists($type)) {
                    return '%s instanceof ' . $type;
                } elseif ($nullable) {
                    return 'false';
                }

                throw new SyntaxError(
                    sprintf("Component contains an invalid type '%s'" .
                        " (allowed types are string, bool, int, float, array, ...[], or any existing class)",
                        $type),
                    $this->getTemplateLine(),
                    $this->getSourceContext()
                );
        }
    }
}
