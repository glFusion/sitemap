<?php
/**
*   Class to handle plugin configurations for sitemaps.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017-2018 Lee Garner <lee@leegarner.com>
*   @package    sitemap
*   @version    2.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Sitemap;

/**
*   Class for sitemap configurations.
*/
class Config
{
    var $isNew;
    var $properties = array();
    public static $local;
    const TAG = 'smap_configs';      // tag applied for caching

    /**
    *   Constructor.  Sets the local properties using the array $item.
    *
    *   @param  string  $pi_name    Plugin name to read. Optional.
    */
    public function __construct($pi_name = '')
    {
        global $_USER, $_SMAP_CONF;

        $this->isNew = true;
        if ($pi_name == '') {
            $this->pi_name = '';
            $this->freq = 'weekly';
            $this->xml_enabled = 0;
            $this->html_enabled = 0;
            $this->orderby = 999;
            $this->priority = 0.5;
        } else {
            $this->pi_name = $pi_name;
            if ($this->Read()) {
                $this->isNew = false;
            }
        }
    }


    /**
    *   Read this field definition from the database.
    *
    *   @see Config::SetVars
    *   @param  string  $pi_name    Plugin name
    *   @return boolean     Status from SetVars()
    */
    public function Read($pi_name = '')
    {
        global $_TABLES;

        if ($pi_name != '') {
            $this->pi_name = $pi_name;
        }
        $sql = "SELECT * FROM {$_TABLES['smap_maps']}
                WHERE pi_name='" . DB_escapeString($this->pi_name) . "'";
        $res = DB_query($sql, 1);
        if ($res == NULL || DB_numRows($res) < 1) {
            return false;
        } else {
            return $this->SetVars(DB_fetchArray($res, false));
        }
    }


    /**
    *   Set a value into a property
    *
    *   @param  string  $name       Name of property
    *   @param  mixed   $value      Value to set
    */
    public function __set($name, $value)
    {
        global $LANG_FORMS;
        switch ($name) {
        case 'orderby':
            $this->properties[$name] = (int)$value;
            break;

        case 'priority':
            // Ensure proper formatting regardless of locale
            $this->properties[$name] = number_format((float)$value, 1, '.', '');
            break;

        case 'xml_enabled':
        case 'html_enabled':
            $this->properties[$name] = $value == 0 ? 0 : 1;
            break;

        default:
            $this->properties[$name] = trim($value);
            break;
        }
    }


    /**
    *   Get a property's value
    *
    *   @param  string  $name       Name of property
    *   @return mixed       Value of property, or empty string if undefined
    */
    public function __get($name)
    {
        if (array_key_exists($name, $this->properties)) {
           return $this->properties[$name];
        } else {
            return '';
        }
    }


    /**
    *   Set all variables for this field.
    *   Data is expected to be from $_POST or a database record
    *
    *   @param  array   $item   Array of fields for this item
    *   @param  boolean $fromdb Indicate whether this is read from the DB
    */
    public function SetVars($A)
    {
        if (!is_array($A))
            return false;

        $this->orderby = $A['orderby'];
        $this->xml_enabled = $A['xml_enabled'];
        $this->html_enabled = $A['html_enabled'];
        $this->priority = $A['priority'];
        $this->freq = $A['freq'];
        return true;
    }


    /**
    *   Save the field definition to the database.
    *
    *   @param  mixed   $val    Value to save
    *   @return string          Error message, or empty string for success
    */
    public function Save($A = '')
    {
        global $_TABLES, $_CONF_FRM;

        $sql1 = '';
        $sql2 = '';
        $sql3 = '';

        if (is_array($A))
            $this->SetVars($A);

        if ($this->isNew) {
            $sql1 = "INSERT INTO {$_TABLES['smap_maps']} SET
                pi_name = '" . DB_escapeString($this->pi_name). "', ";
            $sql3 = '';
        } else {
            // Existing record, perform update
            $sql1 = "UPDATE {$_TABLES['smap_maps']} SET ";
            $sql3 .= " WHERE pi_name = '" . DB_escapeString($this->pi_name) . "'";
        }

        $sql2 = "orderby = {$this->orderby},
                xml_enabled = {$this->xml_enabled},
                html_enabled = {$this->html_enabled},
                priority = {$this->priority},
                freq = '" . DB_escapeString($this->freq) . "'";
        $sql = $sql1 . $sql2 . $sql3;

        DB_query($sql, 1);
        if (!DB_error()) {
            // After saving, reorder the fields
            self::reOrder();
        }
        return true;
    }


    /**
    *   Delete a sitemap plugin item from the configuration.
    *   Used to remove un-installed plugins.
    *
    *   @param  mixed   $pi_names   Single name or array of names
    *   @return boolean     True on success, False on failure
    */
    public static function Delete($pi_names)
    {
        global $_TABLES;

        if (!is_array($pi_names)) $pi_names = array($pi_names);
        foreach ($pi_names as $pi_name) {
            // Skip non-plugin sitemaps such as articles
            if (in_array($pi_name, self::$local)) continue;

            $values[] = "'" . DB_escapeString($pi_name) . "'";
            Cache::clear($pi_name);
        }
        if (!empty($values)) {
            $values = implode(', ', $values);
            $sql = "DELETE FROM {$_TABLES['smap_maps']}
                    WHERE pi_name IN ($values)";
            DB_query($sql, 1);
            if (!DB_error()) {
            }
            Cache::clear(self::TAG);
        }
        return true;
    }


    /**
    *   Add one or more sitemap configs to the config table.
    *   Used to add newly-installed plugins
    *
    *   @param  mixed   $pi_names   Single name or array of names
    *   @param  boolean $relaod     True to reorder and reload, False to skip
    *   @return boolean     True on success, False on failure
    */
    public static function Add($pi_names, $clear_cache = true)
    {
        global $_TABLES;

        if (!is_array($pi_names)) {
            $pi_names = array($pi_names);
        }
        foreach ($pi_names as $pi_name) {
            // Get the default enabled flags and priority from the driver
            $html = 1;
            $xml = 1;
            $prio = '0.5';
            if (self::piEnabled($pi_name)) {
                $driver = Drivers\BaseDriver::getDriver($pi_name);
                if (!$driver) continue;
            }
            $html = (int)$driver->html_enabled;
            $xml = (int)$driver->xml_enabled;
            $priority = (float)$driver->priority;
            $values[] = "('" . DB_escapeString($pi_name) .
                    "', $html, $xml, 9900, $priority)";
        }
        if (!empty($values)) {
            $values = implode(', ', $values);
            $sql = "INSERT IGNORE INTO {$_TABLES['smap_maps']}
                (pi_name, html_enabled, xml_enabled, orderby, priority)
                VALUES $values";
            DB_query($sql, 1);
            if (!DB_error()) {
                Cache::clear(self::TAG);
                self::reOrder();
            }
        }
        return true;
    }


    /**
    *   Move a sitemap item up or down in the list.
    *   The order field is incremented by 10, so this adds or subtracts 11
    *   to change the order, then reorders the fields.
    *
    *   @uses   Config::reOrder()
    *   @param  integer $pi_name    Item to move
    *   @param  string  $where      Direction to move ('up' or 'down')
    */
    public static function Move($pi_name, $where)
    {
        global $_CONF, $_TABLES, $LANG21;

        $pi_name = DB_escapeString($pi_name);

        switch ($where) {
        case 'up':
            $sign = '-';
            break;

        case 'down':
            $sign = '+';
            break;

        default:
            // Invalid option, return true but do nothing
            return true;
            break;
        }
        $sql = "UPDATE {$_TABLES['smap_maps']}
                SET orderby = orderby $sign 11
                WHERE pi_name = '$pi_name'";
        //echo $sql;die;
        DB_query($sql, 1);
        if (!DB_error()) {
            // Reorder fields to get them separated by 10
            return self::reOrder();
        }
        return true;
    }


    /**
    *   Reorder the sitemap items.
    *   Updates the database, and also the in-memory config array.
    *
    *   @return boolean     True on success, False on DB error
    */
    public static function reOrder()
    {
        global $_TABLES;

        $sql = "SELECT pi_name, orderby FROM {$_TABLES['smap_maps']}
                ORDER BY orderby ASC";
        $result = DB_query($sql);

        $order = 10;
        $stepNumber = 10;
        $clear_cache = false;
        while ($A = DB_fetchArray($result, false)) {
            if ($A['orderby'] != $order) {  // only update incorrect ones
                $sql = "UPDATE {$_TABLES['smap_maps']}
                    SET orderby = '$order'
                    WHERE pi_name = '" . DB_escapeString($A['pi_name']) . "'";
                DB_query($sql, 1);
                if (DB_error()) {
                    COM_errorLog("Config::reOrder() SQL error: $sql");
                    return false;
                }
                // Update the in-memory config array
                $clear_cache = true;
            }
            $order += $stepNumber;
        }
        // Clear the cache of all configs and sitemaps since the order has
        // changed.
        if ($clear_cache) Cache::clear();
        return true;
    }


    /**
    *   Update the priority of a sitemap element.
    *   The valid priorities are defined in sitemap.php.
    *
    *   @param  string  $newvalue   New priority to set
    *   @return float       New value, or old value on error
    */
    public function updatePriority($newvalue)
    {
        global $_SMAP_CONF, $_TABLES;

        // Ensure that the new value is a valid priority. If not,
        // return the original value.
        $good = false;
        foreach ($_SMAP_CONF['priorities'] as $prio) {
            if ($newvalue == $prio) {
                $good = true;
                break;
            }
        }
        if (!$good) return $this->priority;

        DB_change($_TABLES['smap_maps'], 'priority', $newvalue,
            'pi_name', $this->pi_name);
        if (DB_error()) {
            COM_errorLog("Config::updatePriority() SQL error");
        } else {
            // Change the current object's value and Update the in-memory config
            $this->priority = $newvalue;
            Cache::clear($this->name);
            Cache::clear(self::TAG);
        }
        return $this->priority;
    }


    /**
    *   Update the frequency for the current config item.
    *   Called via Ajax from the admin screen.
    *
    *   @param  string  $newfreq    New frequency value
    *   @return string      New value, or old value on error
    */
    public function updateFreq($newfreq)
    {
        global $LANG_SMAP, $_TABLES;

        $this->freq = $newfreq;
        // Make sure the new value is valid
        if (array_key_exists($newfreq, $LANG_SMAP['freqs'])) {
            DB_change($_TABLES['smap_maps'], 'freq', $newfreq,'pi_name', $this->pi_name);
            if (DB_error()) {
                // Log error and return the old value
                COM_errorLog("Config::updateFreq error: $sql");
            } else {
                // Update the in-memory config and return the new value
                $this->freq = $newfreq;
                Cache::clear($this->name);
                Cache::clear(self::TAG);
            }
        }
        return $this->freq;
    }


    /**
    *   Toggle the Enabled state for sitemap types
    *
    *   @param  string  $pi_name    Plugin (driver) name to update
    *   @param  string  $type       Sitemap type (xml or html)
    *   @param  integer $oldtype    Current value, 1 or 0
    *   @return integer     New value, or old value on error
    */
    public static function toggleEnabled($pi_name, $type, $oldval)
    {
        global $_TABLES;

        // Sanitize and set values
        $oldval = $oldval == 1 ? 1 : 0;
        $newval = $oldval == 0 ? 1 : 0;
        DB_change($_TABLES['smap_maps'],
                $type . '_enabled', $newval,
                'pi_name', DB_escapeString($pi_name));
        if (DB_error()) {
            COM_errorLog("Config::toggle() error: $sql");
            return $oldval;
        } else {
            Cache::clear($pi_name);
            Cache::clear(self::TAG);
            return $newval;
        }
    }


    /**
    *   Clean up sitemap configs by removing uninstalled plugins
    *   and adding new ones. Calls Add() and Delete() without reordering
    *   and reloading until the end to avoid unnecessary DB activity.
    *
    *   Calls self::getAll(false) to reload the configs only if any have changed.
    *
    *   @uses   self::getAll()
    *   @param  array   $configs    Array of configs, NULL if not loaded yet
    *   @return array       Updated array of configs.
    */
    public static function updateConfigs(&$configs = NULL)
    {
        global $_PLUGINS, $_PLUGIN_INFO, $_CONF, $_SMAP_CONF;
        if ($configs === NULL) {
            // prevent looping since updateConfigs is called by getAll()
            $configs = self::getAll();
        }
        $have_updates = false;    // Change to true if any changes are made

        // Get any enabled plugins that aren't already in the sitemap table
        // and add them, if so configured
        if ($_SMAP_CONF['auto_add_plugins']) {
            // Get all enabled plugins, plus check local maps
            $plugins = array_merge($_PLUGINS, self::$local);
            $values = array();
            foreach ($plugins as $pi_name) {
                if (!isset($configs[$pi_name])) {
                    // Plugin not in config table, see if there's a driver for it
                    if (self::piEnabled($pi_name)) {
                        $values[] = $pi_name;
                    }
                }
            }
            if (!empty($values)) {
                self::Add($values, false);
                $have_updates = true;
            }
        }

        // Now clean out entries for removed plugins, if any.
        // Ignore local drivers and just remove configs for uninstalled
        // plugins, e.g. not in the $_PLUGIN_INFO array, or those for
        // which a driver can't be found.
        $values = array();
        foreach ($configs as $pi_name=>$info) {
            if (in_array($pi_name, self::$local)) {
                continue;
            }
            // Don't use self::piEnabled() here since we're looking for plugins
            // that are actually uninstalled, not just disabled
            if (!isset($_PLUGIN_INFO[$pi_name]) || !is_file(self::getDriverPath($pi_name))) {
                $values[] = $pi_name;
            }
        }
        if (!empty($values)) {
            self::Delete($values, false);
            $have_updates = true;
        }
        if ($have_updates) {
            Cache::clear();
            $configs = self::getAll(false);
        }
        return;
    }


    /**
    *   Get the path to a sitemap driver.
    *   Checks the plugin directory for a class file, then checks the
    *   bundled ones.
    *
    *   @param  string  $pi_name    Name of plugin
    *   @return string      Path to driver file, or NULL if not found
    */
    public static function getDriverPath($pi_name)
    {
        global $_CONF, $_SMAP_CONF;
        static $paths = array();

        // Check first for a plugin-supplied driver, then look for bundled
        $dirs = array(
            $pi_name . '/sitemap/',
            'sitemap/classes/Drivers/',
        );

        if (!array_key_exists($pi_name, $paths)) {
            $paths[$pi_name] = NULL;
            foreach ($dirs as $dir) {
                $path = $_CONF['path'] . '/plugins/' . $dir .
                    $pi_name . '.class.php';
                if (is_file($path)) {
                    $paths[$pi_name] = $path;
                    break;
                }
            }
        }
        return $paths[$pi_name];
    }


    /**
    *   Load all the sitemap configs into the config array.
    *   Updates the global array variable, no return value.
    *
    *   First loads all the configured sitemaps where the driver belongs
    *   to an installed plugin, then calls updateConfigs() to scan for
    *   additional plugins with drivers. updateConfigs() calls this
    *   function but sets $do_update to false to prevent loops.
    *
    *   @uses   self::updateConfigs()
    *   @param  boolean $do_update  True to call updateConfigs
    *   @return array       Array of config objects
    */
    public static function getAll($do_update = true)
    {
        global $_TABLES, $_PLUGINS;
        static $configs = NULL;

        if (!$do_update) $configs = NULL;   // force re-reading
        if ($configs === NULL) {
            $cache_key = 'smap_configs';
            $configs = Cache::get($cache_key);
            if ($configs === NULL) {
                $configs = array();
                $sql = "SELECT * FROM {$_TABLES['smap_maps']}
                        ORDER BY orderby ASC";
                $result = DB_query($sql, 1);
                if (DB_error()) {
                    COM_errorLog("Config::getAll() SQL error: $sql");
                    return;
                }

                // Only load configs for enabled plugins
                while ($A = DB_fetchArray($result, false)) {
                    $configs[$A['pi_name']] = $A;
                }
                Cache::set($cache_key, $configs, self::TAG);
            }
        }
        if ($do_update) {
            self::updateConfigs($configs);
        }
        return $configs;
    }


    /**
     * Get all the sitemap drivers.
     * Checks a static variable first since this may be called multiple
     * times in a page load (admin index page, for example)
     *
     * @return  array       Array of driver objects
     */
    public static function getDrivers()
    {
        static $drivers = NULL;

        if ($drivers === NULL) {
            $cache_key = 'smap_drivers';
            $drivers = Cache::get($cache_key);
            if ($drivers === NULL) {
                $drivers = array();
                foreach (self::getAll() as $pi_name=>$pi_config) {
                    // Gets all the config items, but only loads drivers for
                    // enabled plugins
                    if (self::piEnabled($pi_name)) {
                        $driver = Drivers\BaseDriver::getDriver($pi_name, $pi_config);
                        if ($driver) $drivers[] = $driver;
                    }
                }
                Cache::set($cache_key, $drivers, self::TAG);
            }
        }
        return $drivers;
    }


    /**
     * Check if a plugin should be included in the sitemaps.
     * Checks that the plugin is enabled or local, and that there is a 
     * sitemap driver for it.
     *
     * @return  booolean    True if plugin is enabled or local
     */
    public static function piEnabled($pi_name)
    {
        global $_PLUGINS;

        // Cache paths for repetitive calls.
        static $plugins = array();

        if (!isset($plugins[$pi_name])) {
            if ( (!in_array($pi_name, $_PLUGINS) &&
                !in_array($pi_name, self::$local) ) ||
                !is_file(self::getDriverPath($pi_name))) {
                $plugins[$pi_name] = false;
            } else {
                $plugins[$pi_name] = true;
            }
        }
        return $plugins[$pi_name];
    }
 
}

// Set the static variable to the names of non-plugin sitemap drivers
// included with the Sitemap plugin. These cannot be deleted.
// This could be a single string set above inside the objecdt, but using an
// array allows other types to be added easily if needed.
Config::$local = array('article');

?>
