<?php
/**
*   Class to handle plugin configurations for sitemaps.
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
*   Class for sitemap configurations.
*/
class smapConfig
{
    var $isNew;
    var $properties = array();
    public static $local;

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
            $this->xml_enabled = 1;
            $this->html_enabled = 1;
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
    *   @see smapConfig::SetVars
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
    *   @param  boolean $relaod     True to reorder and reload, False to skip
    *   @return boolean     True on success, False on failure
    */
    public static function Delete($pi_names, $reload = true)
    {
        global $_TABLES;

        if (!is_array($pi_names)) $pi_names = array($pi_names);
        foreach ($pi_names as $pi_name) {
            // Skip non-plugin sitemaps such as articles
            if (in_array($pi_name, self::$local)) continue;
            $values[] = "'" . DB_escapeString($pi_name) . "'";
        }
        if (!empty($values)) {
            $values = implode(', ', $values);
            $sql = "DELETE FROM {$_TABLES['smap_maps']}
                    WHERE pi_name IN ($values)";
            DB_query($sql, 1);
            if (!DB_error()) {
                if ($reload) {
                    self::loadConfigs();
                }
            }
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
    public static function Add($pi_names, $reload = true)
    {
        global $_TABLES;

        $res = DB_query("SELECT MAX(orderby) AS maxorder FROM {$_TABLES['smap_maps']}");
        if ( DB_numRows($res) > 0 ) {
            $mo = DB_fetchArray($res);
            $maxOrder = $mo['maxorder'];
        } else {
            $maxOrder = 10;
        }

        if (!is_array($pi_names)) {
            $pi_names = array($pi_names);
        }
        USES_sitemap_class_base();
        foreach ($pi_names as $pi_name) {

            // Get the default enabled flags and priority from the driver
            $html = 1;
            $xml = 1;
            $prio = '0.5';
            if (!in_array($pi_name, self::$local)) {
                $classfile = self::getClassPath($pi_name);
                if (is_file($classfile)) {
                    include_once $classfile;
                    $classname = 'sitemap_' . $pi_name;
                    $S = new $classname();
                    $html = (int)$S->html_enabled;
                    $xml = (int)$S->xml_enabled;
                    $priority = (float)$S->priority;
                }
            }

            $maxOrder += 10;
            $values[] = "('" . DB_escapeString($pi_name) .
                    "', $html, $xml, $maxOrder, $priority)";
        }
        if (!empty($values)) {
            $values = implode(', ', $values);
            $sql = "INSERT IGNORE INTO {$_TABLES['smap_maps']}
                (pi_name, html_enabled, xml_enabled, orderby, priority)
                VALUES $values";
            DB_query($sql, 1);
            if (!DB_error()) {
                self::reOrder();
                if ($reload) {
                    self::loadConfigs();
                }
            }
        }
        return true;
    }


    /**
    *   Move a sitemap item up or down in the list.
    *   The order field is incremented by 10, so this adds or subtracts 11
    *   to change the order, then reorders the fields.
    *
    *   @uses   smapConfig::reOrder()
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
        global $_TABLES, $_SMAP_MAPS;

        $sql = "SELECT pi_name, orderby FROM {$_TABLES['smap_maps']}
                ORDER BY orderby ASC";
        $result = DB_query($sql);

        $order = 10;
        $stepNumber = 10;
        while ($A = DB_fetchArray($result, false)) {
            if ($A['orderby'] != $order) {  // only update incorrect ones
                $sql = "UPDATE {$_TABLES['smap_maps']}
                    SET orderby = '$order'
                    WHERE pi_name = '" . DB_escapeString($A['pi_name']) . "'";
                DB_query($sql, 1);
                if (DB_error()) {
                    COM_errorLog("smapConfig::reOrder() SQL error: $sql");
                    return false;
                }
                // Update the in-memory config array
                $_SMAP_MAPS[$A['pi_name']]['orderby'] = $order;
            }
            $order += $stepNumber;
        }
        return true;
    }


    /**
    *   Load all the sitemap configs into the config array.
    *   Updates the global array variable, no return value.
    *
    *   First loads all the configured sitemaps where the driver belongs
    *   to an installed plugin, then calls updateConfigs() to scan for
    *   additional plugins with drivers.
    */
    public static function loadConfigs()
    {
        global $_SMAP_MAPS, $_TABLES, $_PLUGINS;

        $_SMAP_MAPS = array();
        $sql = "SELECT * FROM {$_TABLES['smap_maps']}
                ORDER BY orderby ASC";
        $result = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("smapConfig::loadConfigs() SQL error: $sql");
            return;
        }

        // Only load configs for enabled plugins
        while ($A = DB_fetchArray($result, false)) {
            if (in_array($A['pi_name'], $_PLUGINS) || in_array($A['pi_name'], self::$local)) {
                $_SMAP_MAPS[$A['pi_name']] = $A;
            }
        }

        // Update configs for missing plugins, and optionally delete removed
        // plugins
        self::updateConfigs();
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
        global $_SMAP_CONF, $_TABLES, $_SMAP_MAPS;

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
            COM_errorLog("smapConfig::updatePriority() SQL error");
        } else {
            // Change the current object's value and Update the in-memory config
            $this->priority = $newvalue;
            $_SMAP_MAPS[$this->pi_name]['priority'] = $this->priority;
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
        global $LANG_SMAP, $_TABLES, $_SMAP_MAPS;

        $this->freq = $newfreq;
        // Make sure the new value is valid
        if (array_key_exists($newfreq, $LANG_SMAP['freqs'])) {
            DB_change($_TABLES['smap_maps'], 'freq', $newfreq,'pi_name', $this->pi_name);
            if (DB_error()) {
                // Log error and return the old value
                COM_errorLog("smapConfig::updateFreq error: $sql");
            } else {
                // Update the in-memory config and return the new value
                $this->freq = $newfreq;
                $_SMAP_MAPS[$this->pi_name]['freq'] = $this->freq;
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
            COM_errorLog("smapConfig::toggle() error: $sql");
            return $oldval;
        } else {
            return $newval;
        }
    }


    /**
    *   Clean up sitemap configs by removing uninstalled plugins
    *   and adding new ones. Calls Add() and Delete() without reordering
    *   and reloading until the end to avoid unnecessary DB activity.
    *
    *   Updates the $_SMAP_MAPS config table directly; no return value.
    */
    public static function updateConfigs()
    {
        global $_PLUGINS, $_PLUGIN_INFO, $_SMAP_MAPS, $_CONF, $_SMAP_CONF;

        $reload_maps = false;     // Flag to indicate maps need reloading

        // Get any enabled plugins that aren't already in the sitemap table
        // and add them, if so configured
        if ($_SMAP_CONF['auto_add_plugins']) {
            // Get all enabled plugins, plus check local maps
            $plugins = array_merge($_PLUGINS, self::$local);
            $values = array();
            foreach ($plugins as $pi_name) {
                if (!isset($_SMAP_MAPS[$pi_name])) {
                    // Plugin not in config table, see if there's a driver for it
                    if (in_array($pi_name, self::$local) ||
                            is_file(self::getClassPath($pi_name))) {
                        $values[] = $pi_name;
                    }
                }
            }
            if (!empty($values)) {
                self::Add($values);
                $reload_maps = true;
            }
        }

        // Now clean out entries for removed plugins, if any.
        // Ignore local drivers and just remove configs for uninstalled
        // plugins, e.g. not in the $_PLUGIN_INFO array, or those for
        // which a driver can't be found.
        $values = array();
        foreach ($_SMAP_MAPS as $pi_name=>$info) {
            if (in_array($pi_name, self::$local)) {
                continue;
            }
            if (!isset($_PLUGIN_INFO[$pi_name]) || !is_file(self::getClassPath($pi_name))) {
                $values[] = $pi_name;
            }
        }
        if (!empty($values)) {
            self::Delete($values, false);
            $reload_maps = true;
        }

        // If any updates were done, now reload the configs.
        // orderby values weren't changed, just added or removed, so no need
        // to reorder at this point.
        if ($reload_maps) {
            self::loadConfigs();
        }
    }


    /**
    *   Get the path to a sitemap driver.
    *   Checks the plugin directory for a class file, then checks the
    *   bundled ones.
    *
    *   @param  string  $pi_name    Name of plugin
    *   @return string      Path to driver file, or NULL if not found
    */
    public static function getClassPath($pi_name)
    {
        global $_CONF, $_SMAP_CONF;

        // Check first for a plugin-supplied driver, then look for bundled
        $dirs = array(
            $pi_name,
            'sitemap',
        );

        foreach ($dirs as $dir) {
            $path = $_CONF['path'] . '/plugins/' . $dir .
                    '/sitemap/' . $pi_name . '.class.php';
            if (is_file($path)) return $path;
        }
        return NULL;
    }

}

// Set the static variable to the names of non-plugin sitemap drivers
// included with the Sitemap plugin. These cannot be deleted.
smapConfig::$local = array('article');

?>
