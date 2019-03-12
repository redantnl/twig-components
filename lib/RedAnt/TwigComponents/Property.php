<?php

namespace RedAnt\TwigComponents;

use RedAnt\TwigComponents\Exception\TwigComponentsException;
use RedAnt\TwigComponents\TokenParser\ComponentTokenParser;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;

/**
 * Stores one Component property.
 *
 * @author Gert Wijnalda <gert@redant.nl>
 */
class Property
{
    const FIELDS = [ 'type', 'required', 'default', 'comment', 'preview' ];
    const SCALAR_FIELDS = [ 'type', 'required', 'comment' ];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var null|string
     */
    protected $type = null;

    /**
     * @var bool
     */
    protected $required = false;

    /**
     * @var AbstractExpression
     */
    protected $default;

    /**
     * @var ?string
     */
    protected $comment = null;

    /**
     * @var mixed
     */
    protected $preview = null;

    /**
     * Property constructor.
     *
     * @param string $name
     *
     * @throws TwigComponentsException
     */
    public function __construct(string $name)
    {
        $this->setName($name);
        $this->default = new ConstantExpression(null, -1);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Property
     * @throws TwigComponentsException
     */
    public function setName(string $name): Property
    {
        if (preg_match('/[A-Z]/', $name)) {
            throw new TwigComponentsException(
                sprintf('Best practice violation: use lower cased and underscored variable names (found "%s").',
                    $name));
        }

        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param null|string $type
     *
     * @return Property
     */
    public function setType(?string $type): Property
    {
        $map = [ 'boolean' => 'bool', 'integer' => 'int', 'double' => 'float' ];
        if (array_key_exists($type, $map)) {
            $type = $map[$type];
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @param bool $required
     *
     * @return Property
     * @throws TwigComponentsException
     */
    public function setRequired($required): Property
    {
        if (!is_bool($required)) {
            throw new TwigComponentsException(sprintf('Property "%s" requires a valid boolean.', 'required'));
        }
        $this->required = $required;

        return $this;
    }

    /**
     * @return AbstractExpression
     */
    public function getDefault(): AbstractExpression
    {
        return $this->default;
    }

    /**
     * @param AbstractExpression $default
     *
     * @return Property
     */
    public function setDefault(AbstractExpression $default): Property
    {
        $this->default = $default;

        return $this;
    }

    public function getDefaultValue()
    {
        return ComponentTokenParser::unpackTwigExpression($this->default);
    }

    /**
     * @return string|null
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     *
     * @return Property
     * @throws TwigComponentsException
     */
    public function setComment(string $comment): Property
    {
        if (strpos($comment, '.') === strlen($comment) - 1) {
            throw new TwigComponentsException(
                sprintf('Best practice violation: comment for "%s" should not end with ".".', $this->getName())
            );
        }
        $this->comment = $comment;

        return $this;
    }

    /**
     * @param mixed|null $value
     *
     * @return Property
     */
    public function setPreview($value): Property
    {
        $this->preview = $value;

        return $this;
    }

    /**
     * Get a preview value for this property.
     * If fallback is true, we'll try to auto-generate one.
     *
     * @param bool $fallback
     *
     * @return mixed|null
     */
    public function getPreview(bool $fallback = false)
    {
        $value = $this->preview;

        if (null === $value) {
            if (!$fallback) {
                return null;
            } else {
                $map = [
                    'bool'   => true,
                    'int'    => 123,
                    'int[]'  => "[ 1, 2, 3 ]",
                    'float'  => 3.14,
                    'string' => ucfirst($this->getName()),
                    'array'  => "[ 'a', 'b' ]"
                ];
                if (array_key_exists($this->getType(), $map)) {
                    $value = $map[$this->getType()];
                }
            }

            if (is_null($value)) {
                return 'null';
            }
        }

        if ('string' === $this->type) {
            return "'" . $value . "'";
        }

        return $value;
    }
}