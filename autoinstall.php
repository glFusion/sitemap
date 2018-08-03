<?php
// +--------------------------------------------------------------------------+
// | Site Map Plugin for glFusion                                             |
// +--------------------------------------------------------------------------+
// | autoinstall.php                                                          |
// |                                                                          |
// | glFusion Auto Installer module                                           |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2009-2017 by the following authors:                        |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | This program is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU General Public License              |
// | as published by the Free Software Foundation; either version 2           |
// | of the License, or (at your option) any later version.                   |
// |                                                                          |
// | This program is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with this program; if not, write to the Free Software Foundation,  |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.          |
// |                                                                          |
// +--------------------------------------------------------------------------+

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_DB_dbms, $_SMAP_CONF;

require_once __DIR__ . '/sitemap.php';
require_once __DIR__ . '/sql/mysql_install.php';

// +--------------------------------------------------------------------------+
// | Plugin installation options                                              |
// +--------------------------------------------------------------------------+

$INSTALL_plugin['sitemap'] = array(
    'installer' => array(   'type' => 'installer',
        'version' => '1',
        'mode' => 'install',
    ),
    'plugin'        => array(  'type' => 'plugin',
        'name'      => $_SMAP_CONF['pi_name'],
        'ver'       => $_SMAP_CONF['pi_version'],
        'gl_ver'    => $_SMAP_CONF['gl_version'],
        'url'       => $_SMAP_CONF['pi_url'],
        'display'   => $_SMAP_CONF['pi_name'],
    ),
    array(  'type'  => 'table',
            'table' => $_TABLES['smap_maps'],
            'sql'   => $_SQL['smap_maps'],
    ),
    array(
            'type'  => 'sql',
            'sql' => $_DATA,
    ),
    array(  'type'      => 'group',
            'group'     => 'sitemap Admin',
            'desc'      => 'Moderators of the SiteMap Plugin',
            'variable'  => 'admin_group_id',
            'addroot'   => true,
            'admin'     => true,
    ),
    array(  'type'      => 'feature',
            'feature'   => 'sitemap.admin',
            'desc'      => 'Administer the SiteMap Plugin',
            'variable'  => 'admin_feature_id',
    ),
    array(  'type'      => 'mapping',
            'group'     => 'admin_group_id',
            'feature'   => 'admin_feature_id',
            'log'       => 'Adding SiteMap feature to the SiteMap admin group',
    ),
);


/**
*   Puts the datastructures for this plugin into the glFusion database
*
*   @return   boolean True if successful False otherwise
*/
function plugin_install_sitemap()
{
    global $INSTALL_plugin, $_SMAP_CONF;

    $pi_name            = $_SMAP_CONF['pi_name'];
    $pi_display_name    = $_SMAP_CONF['pi_display_name'];
    $pi_version         = $_SMAP_CONF['pi_version'];

    COM_errorLog("Attempting to install the $pi_display_name plugin", 1);

    $ret = INSTALLER_install($INSTALL_plugin[$pi_name]);
    return $ret == 0 ? true : false;
}


/**
*   Automatic uninstall function for plugins
*
*   @return   array
*
*   This code is automatically uninstalling the plugin.
*   It passes an array to the core code function that removes
*   tables, groups, features and php blocks from the tables.
*   Additionally, this code can perform special actions that cannot be
*   foreseen by the core code (interactions with other plugins for example)
*/
function plugin_autouninstall_sitemap()
{
    Sitemap\Cache:clear();

    $out = array (
        /* give the name of the tables, without $_TABLES[] */
        'tables' => array('smap_maps'),
        /* give the full name of the group, as in the db */
        'groups' => array('sitemap Admin'),
        /* give the full name of the feature, as in the db */
        'features' => array('sitemap.admin'),
        /* give the full name of the block, including 'phpblock_', etc */
        'php_blocks' => array(),
        /* give all vars with their name */
        'vars'=> array('sitemap_changes')
    );
    return $out;
}


/**
*   Perform post-installation functions specific to this plugin
*/
function plugin_postinstall_sitemap()
{
    global $_CONF, $_TABLES, $_SMAP_CONF;
    require_once __DIR__ . '/functions.inc';

    // Add the change counter
    DB_query("INSERT INTO {$_TABLES['vars']} VALUES ('sitemap_changes', '0')",1);

    // Add the current plugins.
    // Configs haven't been loaded at this point, so fake them.
    $_SMAP_CONF['auto_add_plugins'] = 1;
    Sitemap\smapConfig::updateConfigs();
}


/**
*   Loads the configuration records for the Online Config Manager
*
*   @return boolean     True = proceed with install, False = an error occured
*/
function plugin_load_configuration_sitemap()
{
    global $_CONF, $_SMAP_CONF, $_TABLES;

    require_once __DIR__ . '/install_defaults.php';
    return plugin_initconfig_sitemap();
}

?>
