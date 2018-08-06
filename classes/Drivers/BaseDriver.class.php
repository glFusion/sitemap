<?php
/**
*   Base class for sitemaps.
*   Derived from the Dataproxy plugin. Each plugin that wishes to
*   contribute a sitemap should supply a class in its "sitemap"
*   directory named "<plugin_name>.class.php". The class name should be
*   "<plugin_name>" and extend this base class.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017-2018 Lee Garner <lee@leegarner.com>
*   @package    sitemap
*   @version    2.0.2
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Sitemap\Drivers;

/**
*   Class for sitemap items
*/
class BaseDriver
{
    protected $uid;
    protected $all_langs;   // true to include all languages
    protected $smap_type;   // HTML or XML
    protected $name;        // Name of this plugin or sitemap type
    protected $config;      // Config elements for this driver

    /**
    *   Constructor. Sets internal values to defaults
    *
    *   @param  array   $config     Driver config
    */
    public function __construct($config = NULL)
    {
        $this->all_langs = false;   // Assume only the user's language
        $this->setHTML();           // Default to HTML sitemap
        if ($config !== NULL) {
            $this->config = $config;
        } elseif (!empty($this->name)) {
            // Have a driver name, assume it should be enabled
            $this->config = array(
                'html_enabled' => 1,
                'xml_enabled' => 1,
                'priority' => '0.5',
            );
        } else {
            // No name set by the driver, protect from trying to run
            // this sitemap.
            $this->config = array(
                'html_enabled' => 0,
                'xml_enabled' => 0,
                'priority' => '0.5',
            );
        }
    }


    /**
     * Get a config value
     *
     * @param   string  $key    Name of config item to retrieve
     * @return  mixed           Value of $config[$key], NULL if undefined
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        } else {
            return NULL;
        }
    }


    /**
    *   Set the sitemap type to "HTML" for display online.
    */
    public function setHTML()
    {
        global $_USER;

        $this->smap_type = 'html';
        $this->uid = (int)$_USER['uid'];
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


    /**
     * Load a single sitemap driver.
     * First tries to load the namespaced driver, then falls back
     * to the older sitemap_<drivername> class for plugins not updated yet.
     *
     * @param   string  $pi_name    Name of driver (plugin)
     * @param   array   $pi_config  Driver configuration
     * @return  object              Sitemap driver object, NULL if not found
     */
    public static function getDriver($pi_name, $pi_config=NULL)
    {
        $driver = NULL;

        // First try to find a namespaced driver
        $cls = '\\Sitemap\\Drivers\\' . $pi_name;
        if (class_exists($cls)) {
            $driver = new $cls($pi_config);
        } else {
            // Temporary fallback until plugins are updated
            $cls = 'sitemap_' . $pi_name;
            if (class_exists($cls)) {
                $driver = new $cls($pi_config);
            }
        }
        return $driver;
    }

}

?>
