<?php
/**
* glFusion CMS
*
* Site Map Plugin for glFusion
*
* Plugin Information
*
* @license GNU General Public License version 2 or later
*     http://www.opensource.org/licenses/gpl-license.php
*
*  Copyright (C) 2009-2018 by the following authors:
*   Mark R. Evans   mark AT glfusion DOT org
*   Lee Garner      lee AT leegarner DOT com                          |
*
*  Based on the SiteMap Plugin
*  Copyright (C) 2007-2008 by the following authors:
*  Authors: mystral-kk - geeklog AT mystral-kk DOT net
*
*/

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_DB_table_prefix, $_TABLES;

// set Plugin Table Prefix the Same as glFusion

$_SMAP_table_prefix = $_DB_table_prefix;

// Add to $_TABLES array the tables your plugin uses

$_TABLES['smap_config'] = $_SMAP_table_prefix . 'smap_config';
$_TABLES['smap_maps']   = $_SMAP_table_prefix . 'smap_maps';

// Plugin info
$_SMAP_CONF['pi_name']          = 'sitemap';
$_SMAP_CONF['pi_display_name']  = 'SiteMap';
$_SMAP_CONF['pi_version']       = '2.0.2';
$_SMAP_CONF['gl_version']       = '1.7.0';
$_SMAP_CONF['pi_url']           = 'https://www.glfusion.org/';

$_SMAP_CONF['priorities'] = array(
    '1.0', '0.9', '0.8', '0.7', '0.6', '0.5', '0.4', '0.3', '0.2', '0.1', '0.0'
);

?>
