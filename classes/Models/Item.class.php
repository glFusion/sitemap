<?php
/**
 * Definition for a sitemap item.
 * Allows plugins to be sure all needed elements are provided.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner <lee@leegarner.com>
 * @package     shop
 * @version     v2.1.0
 * @since       v2.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Sitemap\Models;


/**
 * Define button cache keys.
 * @package shop
 */
class Item implements \ArrayAccess
{
    /** Information properties.
     * @var array */
    private $properties = array(
        'id'        => false,   // Item ID
        'pid'       => false,   // Parent ID (for categories)
        'title'     => false,   // Item title
        'uri'       => false,   // Link to item
        'date'      => false,   // Publication date
        'image_uri' => false,   // URL to thumbnail image
    );


    /**
     * Set a property when accessing as an array.
     *
     * @param   string  $key    Property name
     * @param   mixed   $value  Property value
     */
    public function offsetSet($key, $value)
    {
        $this->properties[$key] = $value;
    }


    /**
     * Check if a property is set when calling `isset($this)`.
     *
     * @param   string  $key    Property name
     * @return  boolean     True if property exists, False if not
     */
    public function offsetExists($key)
    {
        return isset($this->properties[$key]);
    }


    /**
     * Remove a property when using unset().
     *
     * @param   string  $key    Property name
     */
    public function offsetUnset($key)
    {
        unset($this->properties[$key]);
    }


    /**
     * Get a property when referencing the class as an array.
     *
     * @param   string  $key    Property name
     * @return  mixed       Property value, NULL if not set
     */
    public function offsetGet($key)
    {
        return isset($this->properties[$key]) ? $this->properties[$key] : NULL;
    }


    /**
     * Get the internal properties as a native array.
     *
     * @return  array   $this->properties
     */
    public function toArray()
    {
        return $this->properties;
    }


    /**
     * Set the item ID value.
     *
     * @param   string  $id     Item ID
     * @return  object  $this
     */
    public function withItemId(string $id) : self
    {
        $this->properties['id'] = $id;
        return $this;
    }


    /**
     * Set the item's parent ID value.
     *
     * @param   string  $id     Parent ID
     * @return  object  $this
     */
    public function withParentId(string $id) : self
    {
        $this->properties['pid'] = $id;
        return $this;
    }


    /**
     * Set the title string.
     *
     * @param   string  $title  Title string
     * @return  object  $this
     */
    public function withTitle(string $title) : self
    {
        $this->properties['title'] = $title;
        return $this;
    }


    /**
     * Set the item URL.
     *
     * @param   string  $url    URL to the item
     * @return  object  $this
     */
    public function withUrl(string $url) : self
    {
        $this->properties['uri'] = $url;
        return $this;
    }


    /**
     * Set the item's update date.
     *
     * @param   string  $title  Title string
     * @return  object  $this
     */
    public function withDate(string $date) : self
    {
        $this->properties['date'] = $date;
        return $this;
    }


    /**
     * Set the URL to the item's image.
     *
     * @param   string  $url    Image URL
     * @return  object  $this
     */
    public function withImageUrl(string $url) : self
    {
        $this->properties['image_uri'] = $url;
        return $this;
    }

}
