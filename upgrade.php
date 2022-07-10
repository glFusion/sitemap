<?php
/**
*   Upgrade routines for the Sitemap plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017-2022 Lee Garner <lee@leegarner.com>
*   @package    sitemap
*   @version    2.1.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
use glFusion\Database\Database;
use glFusion\Log\Log;

if (!defined('GVERSION')) {
    die('This file can not be used on its own.');
}

function sitemap_upgrade() : bool
{
    global $_TABLES, $_CONF, $_PLUGIN_INFO, $_SMAP_CONF, $_DB_dbms;

    $pi_name = $_SMAP_CONF['pi_name'];
    if (isset($_PLUGIN_INFO[$pi_name])) {
        $current_ver = $_PLUGIN_INFO[$pi_name]['pi_version'];
    } else {
        return false;
    }
    $installed_ver = plugin_chkVersion_sitemap();
    $db = Database::getInstance();
    $currentVersion = $db->getItem($_TABLES['plugins'], 'pi_version', array('pi_name' => 'sitemap'));

    static $use_innodb = null;
    if ($use_innodb === null) {
        if (
            ($_DB_dbms == 'mysql') &&
            ($db->getItem($_TABLES['vars'], 'value', array('name' => 'database_engine')) == 'InnoDB')
        ) {
            $use_innodb = true;
        } else {
            $use_innodb = false;
        }
    }

    switch ($currentVersion) {
        case '1.0':
            require_once $_CONF['path'].'plugins/sitemap/sql/mysql_update-1.0_1.0.1.php';
            foreach ($VALUES_100_TO_101 as $table => $sqls) {
                COM_errorLog("Inserting default data into $table table", 1);
                foreach ($sqls as $sql) {
                    DB_query($sql, 1);
                }
            }
            // fall through
        case '1.0.1':
        case '1.0.2':
        case '1.0.3':
        case '1.1.0':
        case '1.1.1':
        case '1.1.2':
        case '1.1.3':
            require_once $_CONF['path'].'plugins/sitemap/sql/mysql_update-1.0.1_1.1.4.php';
            COM_errorLog("Inserting default data into table", 1);
            foreach ($DATA_101_TO_114 as $sql) {
                DB_query($sql, 1);
            }
            // fall through
        case '1.1.4' :
        case '1.1.5' :
        case '1.1.6' :
        case '1.1.7' :
            // v2.0 moves configuration over to glFusion's config table

            require_once $_CONF['path'].'plugins/sitemap/sql/mysql_install.php';
            require_once $_CONF['path'].'plugins/sitemap/classes/smapConfig.class.php';
            require_once $_CONF['path'].'plugins/sitemap/install_defaults.php';

            // load original config data
            $conf = _SITEMAP_loadConfig();
            $_SMAP_DEFAULT['xml_filenames'] = $conf['google_sitemap_name'];
            $_SMAP_DEFAULT['view_access'] = $conf['anon_access'] ? 2 : 1;

            // install new configuration settings
            plugin_initconfig_sitemap();

            // reload config
            $configT = config::get_instance();
            $_SMAP_CONF = $configT->get_config('sitemap');
            include __DIR__ . '/sitemap.php';

            // do database updates
            // $_SQL is set in mysql_install.php

            $sql = $_SQL['smap_maps'];
            if ($use_innodb) {
                $sql = str_replace('MyISAM', 'InnoDB', $sql);
            } else {
                $sql = $sql;
            }
            try {
                $db->conn->executeStatement($sql);
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            }

            // load default data
            $data = $_DATA['default_maps'];
            try {
                $db->conn->executeStatement($data);
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            }

            // now get the current sitemap configs and put them in the new "maps" table.
            // seed with "sitemap" to block including the sitemap plugin
            $pi_confs = array('sitemap');
            // known configs to be ignored here
            $excludes = array(
                'google_sitemap_name',
                'time_zone',
                'sp_type',
                'sp_except',
                'sitemap_in_xhtml',
                'anon_access',
                'date_format',
            );
            $internal = array('article');

            foreach ($conf as $key=>$value) {
                // exclude known globa configs
                if (in_array($key, $excludes)) continue;
                $parts = explode('_', $key);
                if (!isset($parts[1])) continue;
                $pi_name = $parts[1];
                // already have this one
                if (in_array($pi_name, $pi_confs)) continue;
                // crude method to see if $key refers to a plugin
                if (!in_array($pi_name, $internal) &&
                    !array_key_exists($pi_name, $_PLUGIN_INFO)
                ) {
                    continue;
                }
                $pi_confs[] = $pi_name;
                $pi_conf = new \Sitemap\Config($pi_name);
                $pi_conf->Save(array(
                    'priority' => $conf['priority_' . $pi_name],
                    'freq' => $conf['freq_' . $pi_name],
                    'xml_enabled' => $conf['gsmap_' . $pi_name],
                    'html_enabled' => $conf['sitemap_' . $pi_name],
                    'orderby' => $conf['order_' . $pi_name],
                ));
            }
            // clean up configs for added and removed plugins
            \Sitemap\Config::updateConfigs();

            // remove old config table
            try {
                $db->conn->executeStatement("DROP table {$_TABLES['smap_config']}");
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            }

            // fall through...
        case '2.0.0' :
            // Add the change counter
            try {
                $db->conn->insert(
                    $_TABLES['vars'],
                    array('sitemap_changes' => '0'),
                    array(Database::STRING)
                );
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $k) {
                // Do nothing
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            }

        case '2.0.1' :
            // no changes

        case '2.0.2' :
            // no changes

        case '2.0.3' :
            // no changes

        case '2.0.4' :
            // no changes

        default:
            break;
    }

    _SITEMAP_remOldFiles();
    Sitemap\Cache::clear();
    CTL_clearCache();
    SMAP_update_config();
    if ($current_ver != $installed_ver) {
        if (!SMAP_do_set_version($installed_ver)) {
            return false;
        }
        $current_ver = $installed_ver;
    }
    return true;
}


/**
* Loads vars from DB into $_SMAP_CONF[]
*/
function _SITEMAP_loadConfig()
{
    global $_TABLES;

    $conf = array();

    if ( !DB_checkTableExists('smap_config') ) return $conf;

    $sql = "SELECT * FROM {$_TABLES['smap_config']}";
    $result = DB_query($sql);
    if (DB_error()) {
        COM_errorLog('_SITEMAP_loadConfig: cannot load config.');
        exit;
    }

    while (($A = DB_fetchArray($result)) !== FALSE) {
        list($name, $value) = $A;
        if ($value == 'true') {
            $value = true;
        } else if ($value == 'false') {
            $value = false;
        }

        if ($name == 'date_format') {
            $value = substr($value, 1, -1);
        } else if (substr($name, 0, 6) == 'order_') {
            $value = (int) $value;
        }

        $conf[$name] = $value;
    }
    return $conf;
}


/**
 * Remove deprecated files
 */
function _SITEMAP_remOldFiles()
{
    global $_CONF, $_SMAP_CONF;

    $paths = array(
        // private/plugins/sitemap
        __DIR__ => array(
            // 2.0.2
            'classes/smapConfig.class.php',
            'sitemap/article.class.php',
            'sitemap/calendar.class.php',
            'sitemap/dokuwiki.class.php',
            'sitemap/filemgmt.class.php',
            'sitemap/forum.class.php',
            'sitemap/links.class.php',
            'sitemap/mediagallery.class.php',
            'sitemap/polls.class.php',
            'sitemap/README.md',
            'sitemap/staticpages.class.php',
        ),
        // public_html/sitemap
        $_CONF['path_html'] . $_SMAP_CONF['pi_name'] => array(
        ),
        // admin/plugins/sitemap
        $_CONF['path_html'] . 'admin/plugins/' . $_SMAP_CONF['pi_name'] => array(
        ),
    );

    foreach ($paths as $path=>$files) {
        foreach ($files as $file) {
            @unlink("$path/$file");
        }
    }
    // Remove old driver directory (2.0.2)
    if (is_dir(__DIR__ . '/sitemap')) @rmdir(__DIR__ . '/sitemap');
}


/**
 * Update the plugin configuration
 */
function SMAP_update_config()
{
    USES_lib_install();

    require_once __DIR__ . '/install_defaults.php';
    _update_config('sitemap', $smapConfigData);
}


/**
 * Update the plugin version number in the database.
 * Called at each version upgrade to keep up to date with
 * successful upgrades.
 *
 * @param   string  $ver    New version to set
 * @return  boolean         True on success, False on failure
 */
function SMAP_do_set_version($ver)
{
    global $_TABLES, $_SMAP_CONF, $_PLUGIN_INFO;

    $db = Database::getInstance();
    try {
        $db->conn->update(
            $_TABLES['plugins'],
            array(
                'pi_version' => $ver,
                'pi_gl_version' => $_SMAP_CONF['gl_version'],
                'pi_homepage' => $_SMAP_CONF['pi_url'],
            ),
            array('pi_name' => $_SMAP_CONF['pi_name']),
            array(Database::STRING, Database::STRING, Database::STRING, Database::STRING)
        );
        Log::write('system', Log::DEBUG, "{$_SMAP_CONF['pi_display_name']} version set to $ver");
        $_SMAP_CONF['pi_version'] = $ver;
        $_PLUGIN_INFO[$_SMAP_CONF['pi_name']]['pi_version'] = $ver;
        return true;
     } catch (\Throwable $e) {
         Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
         return false;
    }
}

