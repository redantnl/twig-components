<?php

namespace RedAnt\TwigComponents\TokenParser;

use RedAnt\TwigComponents\Exception\TwigComponentsException;
use RedAnt\TwigComponents\Node\ComponentNode;
use RedAnt\TwigComponents\Property;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Parser;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Defines a component.
 *
 *     {% component button = {
 *         container: { type: 'string', default: 'button', comment: 'HTML element, e.g. "button" (default) or "a"' },
 *         label:     { type: 'string', required: true, comment: 'Button text (printed as raw HTML)' }
 *     } with options %}
 *
 * @author  Gert Wijnalda <gert@redant.nl>
 */
class ComponentTokenParser extends AbstractTokenParser
{
    /**
     * Parses a 'component' token and returns a ComponentNode node.
     *
     * @param Token $token
     *
     * @return ComponentNode A Twig\Node instance
     *
     * @throws TwigComponentsException
     * @throws SyntaxError
     */
    public function parse(Token $token): ComponentNode
    {
        /** @var Parser $parser */
        $parser = $this->parser;
        $stream = $parser->getStream();

        $name = $stream->expect(Token::NAME_TYPE)->getValue();
        if (preg_match('/[A-Z]/', $name)) {
            throw new TwigComponentsException(
                sprintf('Best practice violation: use lower cased and underscored component names (found "%s").',
                    $name));
        }

        $configuration = $parser->getExpressionParser()->parseHashExpression();

        // Expect a nested hash, with type, value, comment as keys
        $properties = $this->parseConfigurationOptions($configuration);

        $stream->expect(Token::NAME_TYPE, 'with');
        $options = $parser->getExpressionParser()->parseExpression();

        $stream->expect(Token::BLOCK_END_TYPE);

        return new ComponentNode($name, $configuration, $properties, $options,
            $stream->getCurrent()->getLine(), 'component');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag(): string
    {
        return 'component';
    }

    /**
     * Parses a hash map to an array of Property.
     *
     *    {
     *       container: { type: 'string', default: 'button', comment: 'HTML element, e.g. "button" (default) or "a"' },
     *       label: { type: 'string', required: true, comment: 'Button text (printed as raw HTML)' }
     *    }
     *
     * @param ArrayExpression $hash
     *
     * @return Property[]
     * @throws TwigComponentsException
     */
    private function parseConfigurationOptions(ArrayExpression $hash): array
    {
        /** @var Parser $parser */
        $parser = $this->parser;

        $definitions = [];
        $valueIndex = 1; // start at the second key, i.e. the first value in the key/value pair
        $stream = $parser->getStream();

        foreach (static::unpackTwigExpression($hash) as $key => $options) {
            $definition = new Property($key);

            if (!is_array($options)) {
                throw new TwigComponentsException(
                    'A Twig component must supply a nested hash with configuration options.'
                    . PHP_EOL . 'Example: {% component header = { title: { type: "string", default: "Sample title", comment: "The page title" } } %}',
                    $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }

            foreach ($options as $optionKey => $optionValue) {
                if (!in_array($optionKey, Property::FIELDS)) {
                    $error = new TwigComponentsException(
                        sprintf('A Twig component variable cannot configure "%s".', $optionKey),
                        $stream->getCurrent()->getLine(),
                        $stream->getSourceContext());
                    $error->addSuggestions($optionKey, Property::FIELDS);
                    throw $error;
                }

                $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $optionKey)));
                $definition->$setter($optionValue);
            }

            $definitions[$key] = $definition;

            $hash->setNode($valueIndex, $definition->getDefault());
            $valueIndex += 2; // every key/value pair takes up two indexes
        }

        return $definitions;
    }

    public static function unpackTwigExpression(AbstractExpression $expression)
    {
        if ($expression instanceof ConstantExpression) {
            return $expression->getAttribute('value');
        }

        if ($expression instanceof ArrayExpression) {
            $array = [];

            foreach ($expression->getKeyValuePairs() as $keyValuePair) {
                /** @var ConstantExpression $keyValuePair ['key'] */
                $key = $keyValuePair['key']->getAttribute('value');
                if ('default' === $key) {
                    $array[$key] = $keyValuePair['value'];
                } else {
                    $array[$key] = static::unpackTwigExpression($keyValuePair['value']);
                }
            }

            return $array;
        }

        return $expression;
    }
}