<?php
/**
*   Base class for sitemaps.
*   Derived from the Dataproxy plugin. Each plugin that wishes to
*   contribute a sitemap should supply a class in its "classes"
*   directory named "<plugin_name>.class.php". The class name should be
*   "sitemap_<plugin_name>" and extend this sitemap_base class.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee Garner <lee@leegarner.com>
*   @package    sitemap
*   @version    2.0.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/**
*   Class for sitemap items
*/
class sitemap_base
{
    protected $uid;
    protected $all_langs;   // true to include all languages
    protected $smap_type;   // HTML or XML
    protected $name;        // Name of this plugin or sitemap type
    protected $config;      // Config elements for this driver

    /**
    *   Constructor. Sets internal values to defaults
    *
    *   @param  array   $config Driver config from $_SMAP_MAPS
    */
    public function __construct()
    {
        global $_USER, $_SMAP_MAPS;

        $this->uid = (int)$_USER['uid'];
        $this->all_langs = false;   // Assume only the user's language
        $this->setHTML();           // Default to HTML sitemap
        if (isset($_SMAP_MAPS[$this->name])) {
            $this->config = $_SMAP_MAPS[$this->name];
        } else {
            // No name set by the driver, protect from trying to run
            // this sitemap.
            $this->config = array(
                'html_enabled' => 0,
                'xml_enabled' => 0,
            );
        }
    }


    /**
    *   Set the sitemap type to "HTML" for display online.
    */
    public function setHTML()
    {
        $this->smap_type = 'html';
    }


    /**
    *   Set the sitemap type to "gsmap", indicating an XML sitemap.
    */
    public function setXML()
    {
        $this->smap_type = 'xml';
        $this->uid = 1;     // XML sitemaps are public, access as anonymous
    }


    /**
    *   Check if the current sitemap is being created for online use.
    *
    *   @return boolean     True if this is an HTML sitemap
    */
    public function isHTML()
    {
        return $this->smap_type == 'html' ? true : false;
    }


    /**
    *   Check if the current sitemap is being created as an XML file.
    *
    *   @return boolean     True if this is an XML sitemap
    */
    public function isXML()
    {
        return $this->smap_type == 'xml' ? true : false;
    }


    /**
    *   If all_langs is false only items in the user's language are returned.
    *   If true, all items are returned regardless of the item's language.
    *
    *   @param  boolean $status     True to get all languages, False to restrict
    */
    public function setAllLangs($status = true)
    {
        $this->all_langs = $status === true ? true : false;
    }


    /**
    *   Get the name of this sitemap class
    *
    *   @return string  Short name for this sitemap type
    */
    public function getName()
    {
        return $this->name;
    }


    /**
    *   Get the display name of this sitemap class.
    *   Normally should return a value from the LANG file.
    *
    *   @return string  Friendly name of the plugin or content type
    */
    public function getDisplayName()
    {
        return $this->name;
    }


    /**
    *   Get the plugin or item's entry point.
    *   Typically this is {site_url}/pluginname/index.php
    *
    *   @return mixed Base URL to content items, or False if n/a
    */
    public function getEntryPoint()
    {
        global $_CONF;
        return $_CONF['site_url'] . '/' . $this->name . '/index.php';
    }


    /**
    *   Get all the categories under the given base category.
    *
    *   @param  mixed   $base   Base category
    *   @return array(
    *               array(
    *                   'id'    => Category 1 ID,
    *                   'title' => Category title,
    *                   'uri'   => URL to category display page,
    *                   'date'  => Last updated date (False if not used),
    *                   'image_uri' => URL to category image,
    *               ),
    *               // ... etc.
    *           );
    */
     public function getChildCategories($base_cat=false)
    {
        return array();
    }


    /**
    *   Get all the items for this plugin under the given category ID.
    *
    *   @param  mixed   $cat_id     Category ID
    *   @return array(
    *               array(
    *                   'id'    => Item 1 ID,
    *                   'title' => Item title,
    *                   'uri'   => URL to item
    *                   'date'  => Last update timestamp,
    *                   'image_url => URL to item's image,
    *               ),
    *               // ... etc.
    *           );
    */
    public function getItems($cat_id = 0)
    {
        return array();
    }


    /**
    *   Escapes a string for display
    *
    *   @param  $str string: a string to escape
    *   @return      string: an escaped string
    */
    public function Escape($str)
    {
        $str = str_replace(
            array('&lt;', '&gt;', '&amp;', '&quot;', '&#039;'),
            array(   '<',    '>',     '&',      '"',      "'"),
            $str
        );
        return htmlspecialchars($str, ENT_QUOTES);
    }

}

?>
