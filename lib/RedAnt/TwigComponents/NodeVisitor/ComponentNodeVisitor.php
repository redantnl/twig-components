<?php

namespace RedAnt\TwigComponents\NodeVisitor;

use RedAnt\TwigComponents\Node\ComponentNode;
use RedAnt\TwigComponents\Property;
use RedAnt\TwigComponents\Registry;

/**
 * Class ComponentNodeVisitor.
 *
 * @author Gert Wijnalda <gert@redant.nl>
 */
class ComponentNodeVisitor implements \Twig_NodeVisitorInterface
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
     * @param \Twig_Node        $node
     * @param \Twig_Environment $env
     *
     * @return \Twig_Node The modified node
     */
    public function enterNode(\Twig_Node $node, \Twig_Environment $env): \Twig_Node
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
     * @param \Twig_Node        $node
     * @param \Twig_Environment $env
     *
     * @return \Twig_Node|false The modified node or false if the node must be removed
     */
    public function leaveNode(\Twig_Node $node, \Twig_Environment $env)
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