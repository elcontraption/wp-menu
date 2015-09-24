<?php 

namespace WpMenu;

use Walker;

class MenuWalker extends Walker {

    /**
     * Instance of Menu class
     */
    protected $menu;

    public function __construct($menu)
    {
        $this->menu = $menu;
    }

    /**
     * Database fields to use.
     *
     * @see Walker::$db_fields
     * @var array
     */
    public $db_fields = array( 'parent' => 'menu_item_parent', 'id' => 'db_id' );

    /**
     * Run when the walker reaches the start of a new branch
     * 
     * @param  string  $output 
     * @param  integer $depth  
     * @param  array   $args   
     * @return parent::start_lvl          
     */
    public function start_lvl(&$output, $depth = 0, $args = array())
    {
        $branchContainerAttributes = $this->buildAttributes('', $args, 'menu_branch_container_attributes', 'branch_container_attributes'); 

        $indent = str_repeat("\t", $depth);
        $output .= "\n$indent<ul$branchContainerAttributes>\n";

        parent::start_lvl($output, $depth, $args);
    }

    /**
     * Run when the walker reaches the end of a branch
     * 
     * @param  string  $output 
     * @param  integer $depth  
     * @param  array   $args   
     * @return parent::end_lvl          
     */
    public function end_lvl(&$output, $depth = 0, $args = array())
    {
        $indent = str_repeat("\t", $depth);
        $output .= "\n$indent</ul>\n";

        parent::end_lvl($output, $depth, $args);
    }

    /**
     * Run when the walker reaches the start of an element
     * 
     * @param  string  $output            
     * @param  object  $object            
     * @param  integer $depth             
     * @param  array   $args              
     * @param  integer $current_object_id 
     * @return parent::start_el                     
     */
    public function start_el(&$output, $object, $depth = 0, $args = array(), $current_object_id = 0)
    {
        $indent = str_repeat("\t", $depth + 1);

        $object->classes[] = $this->getContextClass($object, $args);

        $itemAttributes = $this->buildAttributes($object, $args, 'menu_item_attributes', 'item_attributes', array('class' => array_filter($object->classes)));

        $linkAttributes = $this->buildAttributes($object, $args, 'menu_link_attributes', 'link_attributes', array(
            'title'     => $object->attr_title,
            'target'    => $object->target,
            'rel'       => $object->xfn,
            'href'      => $object->url,
        ));

        $output .= $args['before'];
        $output .= "\n$indent<li$itemAttributes>";
        $output .= "<a$linkAttributes>";
        $output .= $args['link_before'];
        $output .= apply_filters('the_title', $object->title, $object->ID);
        $output .= "</a>";
        $output .= $args['link_after'];
        $output .= $args['after'];

        parent::start_el($output, $object, $depth, $args, $current_object_id);
    }

    /**
     * Run when the walker reaches the end of an element
     * @param  string  $output 
     * @param  object  $object 
     * @param  integer $depth  
     * @param  array   $args   
     * @return parent::end_el          
     */
    public function end_el(&$output, $object, $depth = 0, $args = array())
    {
        $output .= "</li>\n";

        parent::end_el($output, $object, $depth, $args);
    }

    /**
     * Build attributes
     *
     * Attributes are added in order of importance, least to greatest:
     *     1. Filtered on the passed filter string
     *     2. Option 'element_attributes' passed through Menu::show()
     *     3. Attributes set by user in menu admin
     * 
     * @param  object $object 
     * @param  array $args   
     * @return string         
     */
    protected function buildAttributes($object, $args, $filter, $option, $defaults = array())
    {
        $filteredAttributes = apply_filters($filter, array(), $object, $args);
        $optionAttributes = $args[$option];

        $attributes = array_merge_recursive($filteredAttributes, $optionAttributes, $defaults);
        $attributes = array_filter($attributes);

        // Flatten attributes
        foreach ($attributes as $key => $value)
        {
            if (is_array($value))
                $attributes[$key] = implode(' ', $value);
        }

        return $this->menu->htmlAttributes($attributes);
    }

    protected function getContextClass($object, $args)
    {
        $id = $object->ID;
        $classes = array();
        $currentItemClass = $args['current_item_class'];
        $currentParentClass = $args['current_parent_class'];
        $currentAncestorClass = $args['current_ancestor_class'];

        // Is ancestor?
        if ($this->menu->ancestors($id)) $classes[] = $currentAncestorClass;

        // Is parent?
        if ($this->menu->parent($id)) $classes[] = $currentParentClass;

        // Is current?
        if ($this->menu->current($id)) $classes[] = $currentItemClass;

        return implode(' ', array_filter($classes));
    }
}