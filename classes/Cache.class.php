<?php
/**
 * Class to cache DB and web lookup results.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018-2022 Lee Garner <lee@leegarner.com>
 * @package     sitemap
 * @version     2.1.0
 * @since       2.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Sitemap;

/**
 * Class for Sitemap Cache
 * @package sitemap
 */
class Cache
{
    const MIN_GVERSION = '2.0.0';
    private static $_TAGS = array('sitemap', 'plugin');


    /**
     * Update the cache.
     * Adds an array of tags including the plugin name.
     *
     * @param   string  $key    Item key
     * @param   mixed   $data   Data, typically an array
     * @param   mixed   $tag    Tag, or array of tags.
     * @param   integer $ttl    Time to live, in minutes. Default 1 day.
     * @return  boolean         True on success, False on failure
     */
    public static function set(string $key, $data, $tag='', $ttl = 1440)
    {
        if ($ttl !== NULL) {
            $ttl = (int)$ttl * 60;   // convert to seconds
        }
        // Always make sure the base tag is included
        $tags = self::$_TAGS;
        if (!empty($tag)) {
            if (is_array($tag)) {     // allow for multiple tags
                $tags = array_merge($tags, $tag);
            } else {
                $tags[] = $tag;
            }
        }
        $key = self::makeKey($key);
        return \glFusion\Cache\Cache::getInstance()
            ->set($key, $data, $tags, $ttl);
    }


    /**
     * Delete a single item from the cache by key.
     *
     * @param   string  $key    Base key, e.g. item ID
     * @return  boolean         True on success, False on failure
     */
    public static function delete(string $key)
    {
        $key = self::makeKey($key);
        return \glFusion\Cache\Cache::getInstance()->delete($key);
    }


    /**
     * Clear the cache for specific items, or completely.
     *
     * @param   string  $item_type  Type of item (plugin name)
     */
    public static function clear($item_type = '')
    {
        $tags = self::$_TAGS;
        if ($item_type != '') {
            // delete item and category cache for one plugin
            // If $item_type is not a plugin, then the category deletion
            // just won't do anything
            if (is_array($item_type)) {     // allow for multiple tags
                $tags = array_merge($tags, $item_type);
            } else {
                $tags[] = $item_type;
            }
        }
        return \glFusion\Cache\Cache::getInstance()->deleteItemsByTagsAll($tags);
    }


    /**
     * Create a unique cache key.
     * Intended for internal use, but public in case it is needed.
     *
     * @param   string  $key    Base key, e.g. Item ID
     * @return  string          Encoded key string to use as a cache ID
     */
    public static function makeKey(string $key)
    {
        $key = \glFusion\Cache\Cache::getInstance()->createKey('sitemap_' . $key);
        return $key;
    }


    /**
     * Get an item from cache.
     *
     * @param   string  $key    Key to retrieve
     * @return  mixed       Value of key, or NULL if not found
     */
    public static function get(string $key)
    {
        $key = self::makeKey($key);
        if (\glFusion\Cache\Cache::getInstance()->has($key)) {
            return \glFusion\Cache\Cache::getInstance()->get($key);
        } else {
            return NULL;
        }
    }

}

