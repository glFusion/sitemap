<?php
/**
 * Apply updates to Sitemap during development.
 * Calls upgrade function with "ignore_errors" set so repeated SQL statements
 * won't cause functions to abort.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner <lee@leegarner.com>
 * @package     sitemap
 * @version     v2.1.0
 * @since       v2.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
require_once '../../../lib-common.php';

// If plugin is not installed or not enabled, display an error and exit gracefully
if (
    !in_array('sitemap', $_PLUGINS) ||
    !SEC_inGroup('Root')
) {
    COM_404();
    exit;
}

if (function_exists('CACHE_clear')) {
    CACHE_clear();
}
\Sitemap\Cache::clear();

// Force the plugin version to the previous version and do the upgrade
$_PLUGIN_INFO['sitemap']['pi_version'] = '2.0.0';
plugin_upgrade_sitemap(true);

// need to clear the template cache so do it here
if (function_exists('CACHE_clear')) {
    CACHE_clear();
}
header('Location: '.$_CONF['site_admin_url'].'/plugins.php?msg=600');
exit;

