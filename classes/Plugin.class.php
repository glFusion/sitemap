<?php
/**
 * Class to handle plugin drivers for sitemaps.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2018 Lee Garner <lee@leegarner.com>
 * @package     sitemap
 * @version     v2.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Sitemap;
use glFusion\Database\Database;
use glFusion\Log\Log;


/**
 * Class for sitemap drivers.
 */
class Plugin
{
    public const TAG = 'smap_configs';
    private $isNew;
    private $properties = array();
    public static $local = array('article');
    private static $_TAGS = array(self::TAG, 'plugin'); // tag applied for caching

    /**
    *   Constructor.  Sets the local properties using the array $item.
    *
    *   @param  string  $pi_name    Plugin name to read. Optional.
    */
    public function __construct($pi_name = '')
    {
        global $_USER;

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
     * Read this field definition from the database.
     *
     * @see     Plugin::SetVars
     * @param   string  $pi_name    Plugin name
     * @return  boolean     Status from SetVars()
     */
    public function Read(string $pi_name='') : bool
    {
        global $_TABLES;

        if ($pi_name != '') {
            $this->pi_name = $pi_name;
        }
        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT * FROM {$_TABLES['smap_maps']}
                WHERE pi_name = ?",
                array($this->pi_name),
                array(Database::STRING)
            )->fetchAssociative();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return false;
        }
        if (is_array($data)) {
            $this->SetVars($data);
            return true;
        }
    }


    /**
     * Set a value into a property.
     *
     * @param   string  $name       Name of property
     * @param   mixed   $value      Value to set
     */
    public function __set(string $name, $value) : void
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
     * Get a property's value.
     *
     * @param   string  $name       Name of property
     * @return  mixed       Value of property, or empty string if undefined
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->properties)) {
           return $this->properties[$name];
        } else {
            return '';
        }
    }


    /**
     * Set all variables for this field.
     * Data is expected to be from $_POST or a database record
     *
     * @param   array   $item   Array of fields for this item
     * @param   boolean $fromdb Indicate whether this is read from the DB
     */
    public function SetVars(array $A) : self
    {
        if (is_array($A)) {
            $this->orderby = $A['orderby'];
            $this->xml_enabled = $A['xml_enabled'];
            $this->html_enabled = $A['html_enabled'];
            $this->priority = $A['priority'];
            $this->freq = $A['freq'];
        }
        return $this;
    }


    /**
     * Save the field definition to the database.
     *
     * @param   mixed   $val    Value to save
     * @return  string          Error message, or empty string for success
     */
    public function Save(?array $A = NULL)
    {
        global $_TABLES, $_CONF_FRM;

        $sql1 = '';
        $sql2 = '';
        $sql3 = '';

        if (is_array($A)) {
            $this->SetVars($A);
        }
        $db = Database::getInstance();
        $values = array(
            'orderby' => $this->orderby,
            'xml_enabled' => $this->xml_enabled,
            'html_enabled' => $this->html_enabled,
            'priority' =>$this->priority,
            'freq' => $this->freq,
        );
        $types = array(
            Database::INTEGER,
            Database::INTEGER,
            Database::INTEGER,
            Database::INTEGER,
            Database::STRING,
            Database::STRING,
        );

        try {
            if ($this->isNew) {
                $values['pi_name'] = $this->pi_name;
                $db->conn->insert($_TABLES['smap_maps'], $values, $types);
            } else {
                $where = array('pi_name' => $this->pi_name);
                $db->conn->insert($_TABLES['smap_maps'], $values, $where, $types);
            }
            self::reOrder();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return false;
        }
        return true;
    }


    /**
     * Delete a sitemap plugin item from the configuration.
     * Used to remove un-installed plugins.
     *
     * @param   mixed   $pi_names   Single name or array of names
     * @return  boolean     True on success, False on failure
     */
    public static function Delete($pi_names) : bool
    {
        global $_TABLES;

        if (!is_array($pi_names)) {
            $pi_names = array($pi_names);
        }
        $values = array();
        foreach ($pi_names as $pi_name) {
            // Skip non-plugin sitemaps such as articles
            if (!in_array($pi_name, self::$local)) {
                $values[] = $pi_name;
                Cache::clear($pi_name);
            }
        }
        if (!empty($values)) {
            $db = Database::getInstance();
            try {
                $db->conn->executeStatement(
                    "DELETE FROM {$_TABLES['smap_maps']}
                    WHERE pi_name IN (?)",
                    array($values),
                    array(Database::PARAM_STR_ARRAY)
                );
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                return false;
            }
        }
        Cache::clear(self::TAG);
        return true;
    }


    /**
     * Add one or more sitemap configs to the config table.
     * Used to add newly-installed plugins
     *
     * @param   mixed   $pi_names   Single name or array of names
     * @param   boolean $relaod     True to reorder and reload, False to skip
     * @return  boolean     True on success, False on failure
     */
    public static function Add($pi_names, bool $clear_cache = true) : bool
    {
        global $_TABLES;

        if (!is_array($pi_names)) {
            $pi_names = array($pi_names);
        }

        $db = Database::getInstance();
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
            try {
                $db->conn->insert(
                    $_TABLES['smap_maps'],
                    array(
                        'pi_name' => $pi_name,
                        'html_enabled' => (int)$driver->html_enabled,
                        'xml_enabled' => (int)$driver->xml_enabled,
                        'orderby' => 9900,
                        'priority' => (float)$driver->priority,
                    ),
                    array(
                        Database::STRING,
                        Database::INTEGER,
                        Database::INTEGER,
                        Database::STRING,
                    )
                );
                Cache::clear(self::TAG);
                self::reOrder();
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $k) {
                // Do nothing
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                // just keep going, may only be a key conflict
            }
        }
        return true;
    }


    /**
     * Move a sitemap item up or down in the list.
     * The order field is incremented by 10, so this adds or subtracts 11
     * to change the order, then reorders the fields.
     *
     * @uses    Config::reOrder()
     * @param   string  $pi_name    Item to move
     * @param   string  $where      Direction to move ('up' or 'down')
     * @return  boolean     True on success, False on error
     */
    public static function Move(string $pi_name, string $where) : bool
    {
        global $_CONF, $_TABLES, $LANG21;

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

        $db = Database::getInstance();
        try {
            $db->conn->executeUpdate(
                "UPDATE {$_TABLES['smap_maps']}
                SET orderby = orderby $sign 11
                WHERE pi_name = ?",
                array($pi_name),
                array(Database::STRING)
            );
            return self::reOrder();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Reorder the sitemap items.
     * Updates the database, and also the in-memory config array.
     */
    public static function reOrder() : void
    {
        global $_TABLES;

        $db = Database::getInstance();
        try {
            $stmt = $db->conn->executeQuery(
                "SELECT pi_name, orderby FROM {$_TABLES['smap_maps']}
                ORDER BY orderby ASC"
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if (!$stmt) {
            return;
        }

        $order = 10;
        $stepNumber = 10;
        $clear_cache = false;
        while ($A = $stmt->fetchAssociative()) {
            if ($A['orderby'] != $order) {  // only update incorrect ones
                try {
                    $db->conn->update(
                        $_TABLES['smap_maps'],
                        array('orderby' => $order),
                        array('pi_name' => $A['pi_name']),
                        array(Database::INTEGER, Database::STRING)
                    );
                } catch (\Throwable $e) {
                    Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                    // Just keep going
                }
                // Update the in-memory config array
                $clear_cache = true;
            }
            $order += $stepNumber;
        }
        // Clear the cache of all configs and sitemaps since the order has
        // changed.
        if ($clear_cache) {
            Cache::clear();
        }
    }


    /**
     * Update the priority of a sitemap element.
     * The valid priorities are defined in sitemap.php.
     *
     * @param   string  $newvalue   New priority to set
     * @return  float       New value, or old value on error
     */
    public function updatePriority(float $newvalue) : float
    {
        global $_TABLES;

        // Ensure that the new value is a valid priority. If not,
        // return the original value.
        $good = false;
        foreach ((array)Config::get('priorities') as $prio) {
            if ($newvalue == $prio) {
                $good = true;
                break;
            }
        }
        if (!$good) return $this->priority;

        $db = Database::getInstance();
        try {
            $db->conn->update(
                $_TABLES['smap_maps'],
                array('priority' => $newvalue),
                array('pi_name' => $this->pi_name),
                array(Database::STRING, Database::STRING)
            );
            $this->priority = $newvalue;
            Cache::clear($this->name);
            Cache::clear(self::TAG);
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }
        return $this->priority;
    }


    /**
     * Update the frequency for the current config item.
     * Called via Ajax from the admin screen.
     *
     * @param  string  $newfreq    New frequency value
     * @return string      New value, or old value on error
     */
    public function updateFreq(string $newfreq) : string
    {
        global $LANG_SMAP, $_TABLES;

        // Make sure the new value is valid
        if (array_key_exists($newfreq, $LANG_SMAP['freqs'])) {
            $db = Database::getInstance();
            try {
                $db->conn->update(
                    $_TABLES['smap_maps'],
                    array('freq' => $newfreq),
                    array('pi_name' => $this->pi_name),
                    array(Database::STRING, Database::STRING)
                );
                // Update the in-memory config and return the new value
                $this->freq = $newfreq;
                Cache::clear(array(self::TAG, $this->name));
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            }
        }
        return $this->freq;
    }


    /**
     * Toggle the Enabled state for sitemap types.
     *
     * @param   string  $pi_name    Plugin (driver) name to update
     * @param   string  $type       Sitemap type (xml or html)
     * @param   integer $oldtype    Current value, 1 or 0
     * @return  integer     New value, or old value on error
     */
    public static function toggleEnabled(string $pi_name, string $type, int $oldval) : int
    {
        global $_TABLES;

        // Sanitize and set values
        $oldval = $oldval == 1 ? 1 : 0;
        $newval = $oldval == 0 ? 1 : 0;

        $db = Database::getInstance();
        try {
            $db->conn->update(
                $_TABLES['smap_maps'],
                array($type . '_enabled' => $newval),
                array('pi_name' => $pi_name),
                array(Database::INTEGER, Database::STRING)
            );
            Cache::clear($pi_name);
            Cache::clear(self::TAG);
            return $newval;
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return $oldval;
        }
    }


    /**
     * Clean up sitemap configs by removing uninstalled plugins
     * and adding new ones. Calls Add() and Delete() without reordering
     * and reloading until the end to avoid unnecessary DB activity.
     *
     * Calls self::getAll(false) to reload the configs only if any have changed.
     *
     * @uses   self::getAll()
     * @param  array   $configs    Array of configs, NULL if not loaded yet
     */
    public static function updateConfigs(?array &$configs = NULL) : void
    {
        global $_PLUGINS, $_PLUGIN_INFO, $_CONF;

        if ($configs === NULL) {
            // prevent looping since updateConfigs is called by getAll()
            $configs = self::getAll();
        }
        $have_updates = false;    // Change to true if any changes are made

        // Get any enabled plugins that aren't already in the sitemap table
        // and add them, if so configured
        if (Config::get('auto_add_plugins')) {
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
     * Get the path to a sitemap driver.
     * Checks the plugin directory for a class file, then checks the
     * bundled ones.
     * Checks the legacy pi_name/sitemap directory by default when checking
     * if a driver exists, but not during autoloading to avoid returing an
     * invalid result to BaseDriver::getDriver().
     *
     * @param  string  $pi_name    Name of plugin
     * @return string      Path to driver file, or NULL if not found
     */
    public static function getDriverPath(string $pi_name) : ?string
    {
        global $_CONF;
        static $paths = array();

        // Check first for a plugin-supplied driver, then look for bundled.
        // The autoloader fixes the path to pi_name/sitemap, so make sure
        // that directory is not checked here.
        $dirs = array(
            $pi_name . '/classes/Sitemap/',     // New namespaced driver
            $pi_name . '/sitemap/',             // Legacy, no namespace
            'sitemap/classes/Drivers/',         // Bundled driver if no others found
        );

        if (!array_key_exists($pi_name, $paths)) {
            $paths[$pi_name] = NULL;
            foreach ($dirs as $dir) {
                $path = $_CONF['path'] . 'plugins/' . $dir .
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
    public static function getAll(bool $do_update = true) : array
    {
        global $_TABLES, $_PLUGINS;
        static $configs = NULL;

        if (!$do_update) {
            $configs = NULL;   // force re-reading
        }
        if ($configs === NULL) {
            $cache_key = 'smap_configs';
            $configs = Cache::get($cache_key);
            if ($configs === NULL) {
                $configs = array();
                $db = Database::getInstance();
                try {
                    $data = $db->conn->executeQuery(
                        "SELECT * FROM {$_TABLES['smap_maps']}
                        ORDER BY orderby ASC"
                    )->fetchAllAssociative();
                } catch (\Throwable $e) {
                    Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                    return array();
                }
                if (is_array($data)) {
                    // Only load configs for enabled plugins
                    foreach ($data as $A) {
                        $configs[$A['pi_name']] = $A;
                        //$configs[$A['pi_name']]['driver_path'] = self::getDriverPath($A['pi_name']);
                    }
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
    public static function getDrivers() : array
    {
        static $drivers = NULL;

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
        }
        return $drivers;
    }


    /**
     * Check if a plugin should be included in the sitemaps.
     * Checks that the plugin is enabled or local, and that there is a 
     * sitemap driver for it.
     *
     * @param   string  $pi_name    Name of plugin to check
     * @return  booolean    True if plugin is enabled or local
     */
    public static function piEnabled(string $pi_name) : bool
    {
        global $_PLUGINS;

        // Cache paths for repetitive calls.
        static $plugins = array();

        if (!isset($plugins[$pi_name])) {
            if (
                (!in_array($pi_name, $_PLUGINS) && !in_array($pi_name, self::$local)) ||
                !is_file(self::getDriverPath($pi_name))
            ) {
                $plugins[$pi_name] = false;
            } else {
                $plugins[$pi_name] = true;
            }
        }
        return $plugins[$pi_name];
    }
 
}

