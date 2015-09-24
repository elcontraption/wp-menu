<?php

namespace WpMenu;

use WpMenu\MenuWalker;

class MenuBuilder {

    /**
     * Instance of Menu class
     * 
     * @var Menu
     */
    protected $menu;

    /**
     * Walked menu
     * 
     * @var [type]
     */
    protected $walker;

    /**
     * Arguments to pass to walker
     * 
     * @var array
     */
    protected $args;

    public function __construct($menu, $args = array())
    {
        $this->menu = $menu;

        $this->args = wp_parse_args($args, $this->defaults());

        $this->walker = call_user_func_array(array(
            $this->args['walker'], 'walk'), 
            array($this->menu->getItems(), $this->args['depth'], $this->args));
    }

    /**
     * Default menu arguments
     * 
     * @return array
     */
    protected function defaults()
    {
        return array(

            // Child of 
            'child_of' => null,

            // Max depth
            'depth' => 0,

            // Container element
            'container' => 'ul',

            // Item elements 
            'items' => 'li',

            // Container attributes
            'container_attributes' => array(),

            // Item attributes 
            'item_attributes' => array(),

            // Link attributes
            'link_attributes' => array(),

            // Custom walker
            'walker' => new MenuWalker($this->menu),

            // Output text before the <a> of the link
            'before' => '',

            // Output text after the </a> of the link
            'after' => '',

            // Output text before the link text
            'link_before' => '',

            // Output text after the link text
            'link_after' => '',

            // Current menu item class
            'current_item_class' => '',

            // Current menu parent class
            'current_parent_class' => '',

            // Current ancestor class
            'current_ancestor_class' => '',
        );
    }

    /**
     * Return the HTML menu output
     * 
     * @return string HTML menu
     */
    public function render()
    {
        if ( ! $this->menu) return false;

        $filteredAttributes = apply_filters('menu_container_attributes', array());
        $optionAttributes = $this->args['container_attributes'];

        $attributes = array_merge_recursive($filteredAttributes, $optionAttributes);
        $attributes = array_filter($attributes);

        // Flatten attributes
        foreach ($attributes as $key => $value)
        {
            if (is_array($value))
                $attributes[$key] = implode(' ', $value);
        }

        $container = $this->args['container'];
        $attributes = $this->menu->htmlAttributes($attributes);

        return "<{$container}{$attributes}>{$this->walker}</{$container}>";
    }
}