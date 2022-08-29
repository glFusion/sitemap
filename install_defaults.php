<?php
/**
 * Installation Defaults used when loading the online configuration.
 * These settings are only used during the initial installation
 * and upgrade not referenced any more once the plugin is installed.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2019 Lee Garner <lee@leegarner.com>
 * @package     sitemap
 * @version     v2.0.3
 * @since       v2.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined('GVERSION')) {
    die('This file can not be used on its own!');
}

/*
 * Sitemap default settings.
 * @global  array
 */
global $smapConfigData;
$smapConfigData = array(
    array(
        'name' => 'sg_main',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'sitemap',
    ),
    array(
        'name' => 'fs_main',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'sitemap',
    ),
    array(
        'name' => 'xml_filenames',
        'default_value' => 'sitemap.xml;mobile.xml',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'sitemap',
    ),
    array(
        'name' => 'view_access',
        'default_value' => 2,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 4,
        'sort' => 30,
        'set' => true,
        'group' => 'sitemap',
    ),
    array(
        'name' => 'auto_add_plugins',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 3,
        'sort' => 40,
        'set' => true,
        'group' => 'sitemap',
    ),
    array(
        'name' => 'schedule',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 5,
        'sort' => 50,
        'set' => true,
        'group' => 'sitemap',
    ),
);

/**
 * Initialize Sitemap plugin configuration
 *
 * @return  boolean     true: success; false: an error occurred
 */
function plugin_initconfig_sitemap()
{
    global $smapConfigData;

    $c = config::get_instance();
    if (!$c->group_exists('sitemap')) {
        USES_lib_install();
        foreach ($smapConfigData AS $cfgItem) {
            _addConfigItem($cfgItem);
        }
    } else {
        COM_errorLog('initconfig error: Sitemap config group already exists');
    }
    return true;
}

?>
