<?php
/**
*   Upgrade routines for the Sitemap plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee Garner <lee@leegarner.com>
*   @package    sitemap
*   @version    2.0.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined('GVERSION')) {
    die('This file can not be used on its own.');
}

// Required to get the ADVT_DEFAULTS config values
global $_CONF, $_SMAP_CONF, $_ADVT_DEFAULT, $_DB_dbms;

/** Include the default configuration values */
require_once dirname(__FILE__) . '/install_defaults.php';
/** Include the table creation strings */
require_once dirname(__FILE__) . "/sql/mysql_install.php";
/** Include the configuration class */
require_once $_CONF['path_system'] . 'classes/config.class.php';


/**
*   Perform the upgrade starting at the current version.
*   Only versions >= 2.0.0 are considered since previous updates only
*   dealt with the smap_config table which is being removed in 2.0.0
*
*   @return boolean     True on success, False on failure
*/
function sitemap_do_upgrade()
{
    global $_SMAP_CONF, $_PLUGIN_INFO;

    $pi_name = $_SMAP_CONF['pi_name'];
    if (isset($_PLUGIN_INFO[$pi_name])) {
        if (is_array($_PLUGIN_INFO[$pi_name])) {
            // glFusion >= 1.6.6
            $current_ver = $_PLUGIN_INFO[$pi_name]['pi_version'];
        } else {
            // legacy
            $current_ver = $_PLUGIN_INFO[$pi_name];
        }
    } else {
        return false;
    }
    $installed_ver = plugin_chkVersion_sitemap();

    if (!COM_checkVersion($current_ver, '2.0.0')) {
        if (!sitemap_upgrade_2_0_0()) return false;
        $current_ver = '2.0.0';
    }
    CTL_clearCache();

    COM_errorLog("Successfully updated the {$_SMAP_CONF['pi_display_name']} Plugin", 1);
    return true;
}


/**
*   Actually perform any sql updates
*
*   @return boolean         True on success, False on failure
*/
function SMAP_do_upgrade_sql($version)
{
    global $_TABLES, $_SMAP_CONF, $_SMAP_UPG_SQL;

    // If no sql statements passed in, return success
    if (!is_array($_SMAP_UPG_SQL[$version]))
        return true;

    // Execute SQL now to perform the upgrade
    COM_errorLOG("--Updating Sitemap SQL to version $version");
    foreach ($_SMAP_UPG_SQL[$version] as $s) {
        COM_errorLOG("Sitemap Plugin $version update: Executing SQL => $s");
        DB_query($s, 1);
        if (DB_error()) {
            COM_errorLog("SQL Error during Sitemap plugin update",1);
            return false;
        }
    }
    return true;
}


/**
*   Update the plugin version number in the database.
*   Called at each version upgrade to keep up to date with
*   successful upgrades.
*
*   @param  string  $ver    New version to set
*   @return boolean         True on success, False on failure
*/
function SMAP_do_set_version($ver)
{
    global $_TABLES, $_SMAP_CONF;

    // now update the current version number.
    $sql = "UPDATE {$_TABLES['plugins']} SET
            pi_version = '{$_SMAP_CONF['pi_version']}',
            pi_gl_version = '{$_SMAP_CONF['gl_version']}',
            pi_homepage = '{$_SMAP_CONF['pi_url']}'
        WHERE pi_name = '{$_SMAP_CONF['pi_name']}'";

    $res = DB_query($sql, 1);
    if (DB_error()) {
        COM_errorLog("Error updating the {$_SMAP_CONF['pi_display_name']} Plugin version",1);
        return false;
    } else {
        return true;
    }
}

function sitemap_upgrade_2_0_0()
{
    global $_SMAP_CONF, $_SMAP_DEFAULT, $_PLUGINS;
    require_once dirname(__FILE__) . '/classes/smapConfig.class.php';
    COM_errorLog('Updating the sitemap plugin to version 2.0.0');
    $conf = _SITEMAP_loadConfig();
    $xml_filenames = $conf['google_sitemap_name'];
    $view_access = $conf['anon_access'] ? 2 : 1;

    // Add new configuration items
    $c = config::get_instance();
    if (!$c->group_exists($_SMAP_CONF['pi_name'])) {
        $c->add('sg_main', NULL, 'subgroup', 0, 0, NULL, 0, true, $_SMAP_CONF['pi_name']);
        $c->add('fs_main', NULL, 'fieldset', 0, 0, NULL, 0, true, $_SMAP_CONF['pi_name']);
        $c->add('xml_filenames', $xml_filenames,
                'text', 0, 0, 0, 10, true, $_SMAP_CONF['pi_name']);
        $c->add('view_access', $view_access,
                'select', 0, 0, 4, 20, true, $_SMAP_CONF['pi_name']);
        $c->add('auto_add_plugins', $_SMAP_DEFAULT['auto_add_plugins'],
                'select', 0, 0, 3, 30, true, $_SMAP_CONF['pi_name']);
    }
    if (!SMAP_do_upgrade_sql('2.0.0')) return false;

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

    USES_sitemap_class_config();
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
    return SMAP_do_set_version('2.0.0') || false;
}

/**
* Loads vars from DB into $_SMAP_CONF[]
*/
function _SITEMAP_loadConfig() {
	global $_TABLES;

    $conf = array();

    if ( !DB_checkTableExists($_TABLES['smap_config']) ) return $conf;

	$sql = "SELECT * FROM {$_TABLES['smap_config']}";
	$result = DB_query($sql);
	if (DB_error()) {
		COM_errorLog('SITEMAP_loadConfig: cannot load config.');
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


?>
