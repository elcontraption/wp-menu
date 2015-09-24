<?php

namespace WpMenu;

use WpMenu\MenuBuilder;

class Menu {

    /**
     * The current menu object
     * 
     * @var object
     */
    protected $menuObject;

    /**
     * Menu item objects
     * 
     * @var array
     */
    protected $menuItems;

    /**
     * The current menu item object
     * 
     * @var object
     */
    protected $currentItemObject;

    /**
     * Create a new instance of Menu.
     * 
     * @param string $menu Menu ID, slug, or name
     */
    public function __construct($menu)
    {
        if ( ! $menuObject = wp_get_nav_menu_object($menu)) return false;

        $this->menuObject = $menuObject;

        $this->menuItems = wp_get_nav_menu_items($menuObject);

        $this->currentItemObject = $this->getCurrentItemObject();

        return $this;
    }

    /**
     * Change the current menu item object
     * 
     * @param  string $context A nav menu item post object or id
     * @return $this
     */
    public function context($menuItem)
    {
        if ( ! $this->currentItemObject) return $this;

        if (is_int($menuItem))
        {
            $this->currentItemObject = $this->getItemObjectById($menuItem);
        }
        else if (is_object($menuItem))
        {
            $this->currentItemObject = $menuItem;
        }
        
        return $this;
    }

    /**
     * Get the current item object
     *
     * TODO: clean this up. 
     * TODO: make this work for custom post types
     * 
     * @return object
     */
    protected function getCurrentItemObject()
    {
        $queriedObject = get_queried_object();

        if ( ! $queriedObject) return false;

        $pageForPostsId = get_option('page_for_posts');
        $postTypeAssociatedPageId = get_option($queriedObject->post_type . '_page_association');

        foreach ($this->menuItems as $item)
        {
            // Test for post type 'post' home
            if ($item->object_id == $pageForPostsId && $queriedObject->post_type == 'post') return $item;

            // Test for menu item associated with a custom post type
            if ($item->object_id == $postTypeAssociatedPageId ) return $item;

            // Test for current queried object
            if ($item->object_id == $queriedObject->ID) return $item;
        }
    }

    /**
     * Get item object by ID
     * 
     * @param  int $id 
     * @return object
     */
    protected function getItemObjectById($id)
    {
        foreach ($this->menuItems as $item)
        {
            if ($item->ID == $id)
            {
                return $item;
            }
        }
    }

    /**
     * Get the current object
     * 
     * @return object
     */
    public function current($id = null)
    {
        if ( ! $this->currentItemObject) return false;

        if ($id) return $id == $this->currentItemObject->ID;

        return $this->currentItemObject;
    }

    /**
     * Get the parent object or test for id of parent
     * 
     * @return object
     */
    public function parent($id = null)
    {
        if ( ! $this->currentItemObject) return false;

        if ( ! $this->currentItemObject->menu_item_parent) return false;

        $parent = $this->getItemObjectById($this->currentItemObject->menu_item_parent);

        if ($id) return $id == $parent->ID;

        return $parent;
    }

    /**
     * Get ancestor ids or test if id is an ancestor
     *
     * @param mixed $id ID or array of IDs to test
     * @return  mixed Array of ids or boolean
     */
    public function ancestors($id = null)
    {
        if ( ! $this->currentItemObject) return false;

        $currentItemObject = $this->currentItemObject;
        $ancestorIds = array();

        $currentId = $ancestorIds[] = $currentItemObject->menu_item_parent;

        while ($ancestor = $this->getItemObjectById($currentId))
        {
            if (empty($ancestor->menu_item_parent) || ($ancestor->menu_item_parent == $currentItemObject->ID) || in_array($ancestor->menu_item_parent, $ancestorIds))
            {
                break;
            }

            $currentId = $ancestorIds[] = $ancestor->menu_item_parent;
        }

        if (is_integer($id)) return in_array($id, $ancestorIds);   

        if (is_array($id)) return count(array_intersect($id, $ancestorIds)) == count($id);

        return $ancestorIds;
    }

    /**
     * TODO: this is a potentially cleaner version
     * of the ancestors compiler from get_post_ancestors()
     * Get ancestor IDs
     * 
     * @param  object $post \_w\Post\PostInspector
     * @return array       Ancestor Ids
     */
    // private function getAncestorIds($post)
    // {
    //     $ancestors = array();
    //     $id = $ancestors[] = $post->menu_item_parent;
    //     while ($ancestor = new PostInspector(get_post($id)))
    //     {
    //         if (empty($ancestor->menu_item_parent) || ($ancestor->menu_item_parent == $post->ID) || in_array($ancestor->menu_item_parent, $ancestors))
    //         {
    //             break;
    //         }
    //         $id = $ancestors[] = $ancestor->menu_item_parent;
    //     }
    //     return $ancestors;
    // }

    /**
     * Get descendant items or test if id is a child
     * 
     * @param  mixed $id ID or array of IDs to test
     * @return mixed Array of ids or boolean
     */
    public function descendants($id = null)
    {
        if ( ! $this->currentItemObject) return false;

        $descendants = $this->getDescendants($this->currentItemObject->ID, $this->menuItems);

        if ( ! $id) return $descendants;

        $descendantIds = array_map(function($item)
        {
            return $item->ID;
        }, $descendants);

        if (is_integer($id)) return in_array($id, $descendantIds);

        if (is_array($id)) return count(array_intersect($id, $descendantIds)) == count($id);
    }

    /**
     * Get descendant items
     * 
     * @param  int $id    
     * @param  array $items 
     * @return array        
     */
    protected function getDescendants($id, $items)
    {
        $list = array();

        foreach ((array) $items as $item)
        {
            if ($item->menu_item_parent == $id)
            {
                $list[] = $item;

                if ($descendants = $this->getDescendants($item->ID, $items))
                {
                    $list = array_merge($list, $descendants);
                }
            }
        }

        return $list;
    }

    /**
     * Render the menu
     * 
     * @return string HTML
     */
    public function render($args = array())
    {
        return (new MenuBuilder($this, $args))->render($args);
    }

    /**
     * Menu object accessor
     * 
     * @return object
     */
    public function getMenuObject()
    {
        return $this->menuObject;
    }

    /**
     * Menu items accessor
     * 
     * @return array 
     */
    public function getItems()
    {
        return $this->menuItems;
    }

    /**
     * Build an HTML attribute string from an array.
     *
     * @param  array  $attributes
     * @return string
     */
    public function htmlAttributes($attributes)
    {
        $html = array();

        // For numeric keys we will assume that the key and the value are the same
        // as this will convert HTML attributes such as "required" to a correct
        // form like required="required" instead of using incorrect numerics.
        foreach ((array) $attributes as $key => $value)
        {
            $element = $this->attributeElement($key, $value);

            if ( ! is_null($element)) $html[] = $element;
        }
        return count($html) > 0 ? ' '.implode(' ', $html) : '';
    }

    /**
     * Build a single attribute element.
     *
     * @param  string  $key
     * @param  string  $value
     * @return string
     */
    protected function attributeElement($key, $value)
    {
        if (is_numeric($key)) $key = $value;
        if ( ! is_null($value)) return $key.'="'.htmlentities($value, ENT_SUBSTITUTE).'"';
    }
    
}