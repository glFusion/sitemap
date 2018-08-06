<?php
/**
*   Upgrade routines for the Sitemap plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017-2018 Lee Garner <lee@leegarner.com>
*   @package    sitemap
*   @version    2.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined('GVERSION')) {
    die('This file can not be used on its own.');
}

function sitemap_upgrade()
{
    global $_TABLES, $_CONF, $_PLUGINS, $_SMAP_CONF, $_DB_dbms, $_DB_table_prefix;

    $currentVersion = DB_getItem($_TABLES['plugins'],'pi_version',"pi_name='sitemap'");

    static $use_innodb = null;
    if ($use_innodb === null) {
        if (($_DB_dbms == 'mysql') &&
            (DB_getItem($_TABLES['vars'], 'value', "name = 'database_engine'") == 'InnoDB')) {
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
            DB_query($sql,1);

            // load default data
            $data = $_DATA['default_maps'];
            DB_query($data,1);

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
                    !in_array($pi_name, $_PLUGINS)) {
                    continue;
                }
                $pi_confs[] = $pi_name;
                $pi_conf = new smapConfig($pi_name);
                $pi_conf->Save(array(
                    'priority' => $conf['priority_' . $pi_name],
                    'freq' => $conf['freq_' . $pi_name],
                    'xml_enabled' => $conf['gsmap_' . $pi_name],
                    'html_enabled' => $conf['sitemap_' . $pi_name],
                    'orderby' => $conf['order_' . $pi_name],
                ));
            }
            // clean up configs for added and removed plugins
            smapConfig::updateConfigs();

            // remove old config table
            DB_query("DROP table {$_TABLES['smap_config']}",1);

            // fall through...
        case '2.0.0' :
            $configT = config::get_instance();
            $configT->add('schedule', $_SMAP_DEFAULT['schedule'], 'select', 0, 0, 5, 40, true, $_SMAP_CONF['pi_name']);
            // Add the change counter
            DB_query("INSERT INTO {$_TABLES['vars']} VALUES ('sitemap_changes', '0')",1);

        default:
            DB_query("UPDATE {$_TABLES['plugins']} SET pi_version='".$_SMAP_CONF['pi_version']."',pi_gl_version='".$_SMAP_CONF['gl_version']."' WHERE pi_name='sitemap' LIMIT 1");
            break;
    }

    CTL_clearCache();
    Sitemap\Cache::clear();
    _SITEMAP_remOldFiles();

    if ( DB_getItem($_TABLES['plugins'],'pi_version',"pi_name='sitemap'") == $_SMAP_CONF['pi_version']) {
        return true;
    } else {
        return false;
    }
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

?>
