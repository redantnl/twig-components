<?php

namespace RedAnt\TwigComponents\NodeVisitor;

use RedAnt\TwigComponents\Node\ComponentNode;
use RedAnt\TwigComponents\Property;
use RedAnt\TwigComponents\Registry;
use Twig\Environment;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Class ComponentNodeVisitor.
 *
 * @author Gert Wijnalda <gert@redant.nl>
 */
class ComponentNodeVisitor implements NodeVisitorInterface
{
    /**
     * @var Property[]
     */
    protected $definitions = [];

    /**
     * @return Property[]
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * Called before child nodes are visited.
     *
     * @param Node        $node
     * @param Environment $env
     *
     * @return Node The modified node
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ComponentNode) {
            $template = $node->getTemplateName();
            $componentName = Registry::getDotNotatedComponentName($template);

            $this->definitions[$componentName] = $node->getAttribute('properties');
        }

        return $node;
    }

    /**
     * Called after child nodes are visited.
     *
     * @param Node        $node
     * @param Environment $env
     *
     * @return Node|false The modified node or false if the node must be removed
     */
    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    /**
     * Returns the priority for this visitor.
     *
     * @return int The priority level
     */
    public function getPriority(): int
    {
        return 0;
    }
}
