<?php

namespace RedAnt\TwigComponents;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class Registry contains a collection of Components.
 *
 * @author Gert Wijnalda <gert@redant.nl>
 */
class Registry
{
    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * @var array
     */
    protected $_components = [];

    /**
     * @var array
     */
    protected $components = [];

    /**
     * @var array
     */
    private $_callStack = [];

    /**
     * Create a new Twig Component registry.
     *
     * @param Environment                    $twig
     * @param PropertyAccessorInterface|null $propertyAccessor
     */
    public function __construct(Environment $twig, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->twig = $twig;

        if (null === $propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        } else {
            $this->propertyAccessor = $propertyAccessor;
        }
    }

    /**
     * Add a component to the registry.
     *
     * Use a namespaced dotted name for the component, such as 'button' or 'ui.elements.button'.
     * For the template reference, use the Twig namespace, relative path and template file name
     * without '.html.twig', for instance '@Blog/article/list.html.twig'.
     *
     * @param string $name
     * @param string $templateReference
     */
    public function addComponent(string $name, string $templateReference)
    {
        $this->components[$name] = $templateReference;
        $name = '[' . join("][", explode('.', $name)) . ']';
        $this->propertyAccessor->setValue($this->_components, $name, $templateReference);
    }

    /**
     * Get defined components as an array with key: componentName and
     * value: the associated Twig template reference.
     *
     * @return array
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @param string $method
     * @param array  $variables
     *
     * @return $this
     *
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function __call($method, $variables)
    {
        $callStack = array_filter(array_merge($this->_callStack, [ $method ]));
        $propertyPath = '[' . join('][', $callStack) . ']';
        $dotNotatedComponentName = join('.', $callStack);

        $template = $this->propertyAccessor->getValue($this->_components, $propertyPath);
        if (null === $template) {
            throw new RuntimeError(
                sprintf('Component or namespace "%s" does not exist.', $dotNotatedComponentName));
        }
        if (!is_array($template)) {
            if (count($variables) > 1 || key($variables) !== 0) {
                throw new RuntimeError(
                    sprintf('Component "%s" accepts only one (anonymous) argument.',
                        $dotNotatedComponentName));
            }

            echo $this->twig->render($template, [ 'options' => current($variables) ]);
        } else {
            $this->_callStack[] = $method;

            return $this;
        }
    }

    /**
     * Transforms strings like '/components/ui/button.html.twig' to 'ui.button'.
     *
     * @param string $templateReference
     * @param string $removePrefix
     *
     * @return string
     */
    public static function getDotNotatedComponentName(string $templateReference, $removePrefix = '/components'): string
    {
        $componentName = substr($templateReference,
            strpos($templateReference,
                substr($removePrefix, 1)) + strlen($removePrefix),
            -strlen('.html.twig')
        );

        return join('.', explode('/', $componentName));
    }
}