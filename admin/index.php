<?php
// +--------------------------------------------------------------------------+
// | Site Map Plugin for glFusion                                             |
// +--------------------------------------------------------------------------+
// | index.php                                                                |
// |                                                                          |
// | Administrative Interface                                                 |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2014-2015 by the following authors:                        |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// |                                                                          |
// | Based on the Data Proxy Plugin                                           |
// | Copyright (C) 2007-2008 by the following authors:                        |
// |                                                                          |
// | Authors: mystral-kk        - geeklog AT mystral-kk DOT net               |
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

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';
use Sitemap\FieldList;
use Sitemap\Sitemap;
use Sitemap\Config;
use Sitemap\Plugin;
use Sitemap\Models\Request;

if (!in_array('sitemap', $_PLUGINS)) {
    COM_404();
    exit;
}

// Only let admin users access this page
if (!SEC_hasRights('sitemap.admin')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to access the sitemap Admin page without proper permissions.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: {$_SERVER['REMOTE_ADDR']}", 1);
    COM_404();
    exit;
}


/**
 * Callback function to display each field.
 *
 * @param   string  $fieldname  Name of field from header array
 * @param   mixed   $fieldvalue Field's value
 * @param   array   $A          Array of all fieldname=>value pairs
 * @param   array   $icon_arr   Array of icons (not used)
 */
function SMAP_adminField($fieldname, $fieldvalue, $A, $icon_arr, $extra)
{
    global $_CONF, $LANG_ACCESS, $LANG_SMAP;

    $pi_name = $A['pi_name'];
    $retval = '';
    switch($fieldname) {
    case 'action':
        // Change the order
        if ($A['orderby'] > 10) {
            $retval .= FieldList::up(array(
                'url' => $_CONF['site_admin_url'] . '/plugins/sitemap/index.php?move=up&id=' . $A['pi_name'],
            ) );
        } else {
            $retval .= FieldList::blank(array());
        }
        if ($A['orderby'] < $extra['map_count'] * 10) {
            $retval .= FieldList::down(array(
                'url' => $_CONF['site_admin_url'] . '/plugins/sitemap/index.php?move=down&id=' . $A['pi_name'],
            ) );
        }
        break;

    case 'xml_enabled':
    case 'html_enabled':
        list($fldid, $trash) = explode('_', $fieldname);
        $chk = $fieldvalue == 1 ? 'checked="checked"' : '';
        $retval = FieldList::checkbox(array(
            'name' => $fieldname . '[' . $pi_name . ']',
            'id' => $fldid . '_ena_' . $pi_name,
            'value' => 1,
            'checked' => $fieldvalue == 1,
            'onclick' => "SMAP_toggleEnabled(this, '{$pi_name}', '{$fldid}');",
        ) );
         break;

    case 'freq':
        $retval = FieldList::select(array(
            'id' => "freqsel_{$pi_name}",
            'name' => "freq[{$A['pi_name']}]",
            'onchange' => "SMAP_updateFreq('{$pi_name}', this.value);",
            'option_list' => Sitemap::getFreqOptions($fieldvalue),
        ) );
        break;

    case 'priority':
        $retval = FieldList::select(array(
            'id' => "priosel_{$pi_name}",
            'name' => "priority[{$pi_name}]",
            'onchange' => "SMAP_updatePriority('{$pi_name}', this.value);",
            'option_list' => Sitemap::getPriorityOptions($fieldvalue),
        ) );
        break;

    case 'pi_name':
        $retval = $pi_name;
        if ($A['pi_status'] == 0) {
            $retval .= " ({$LANG_SMAP['disabled']})";
        };
        break;

    default:
        $retval = $fieldvalue;
        break;
    }
    return $retval;
}


/**
 * Uses lib-admin to list the form results.
 *
 * @param   string  $frm_id         ID of form
 * @param   string  $instance_id    Optional form instance ID
 * @return  string          HTML for the list
 */
function SMAP_adminList()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_SMAP;

    $retval = '';

    $header_arr = array(
        array(  'text'  => $LANG_SMAP['order'],
                'field' => 'action',
                'sort'  => false,
        ),
        array(  'text'  => $LANG_SMAP['item_name'],
                'field' => 'pi_name',
                'sort'  => true,
        ),
        array(  'text'  => $LANG_SMAP['xml_enabled'],
                'field' => 'xml_enabled',
                'sort'  => false,
                'align' => 'center',
        ),
        array(  'text'  => $LANG_SMAP['html_enabled'],
                'field' => 'html_enabled',
                'sort' => false,
                'align' => 'center',
        ),
        array(  'text'  => $LANG_SMAP['freq'],
                'field' => 'freq',
                'sort' => false,
        ),
        array(  'text'  => $LANG_SMAP['priority'],
                'field' => 'priority',
                'sort' => false,
        ),
    );
    $configs = Plugin::getAll();
    foreach ($configs as $pi_name=>$config) {
        // Hack to indicate any plugins that are installed but disabled.
        $configs[$pi_name]['pi_status'] = Plugin::piEnabled($pi_name);
    }
    $defsort_arr = array('field' => 'orderby', 'direction' => 'asc');
    $extra = array(
        'map_count' => count($configs),
    );
    $retval .= ADMIN_listArray('simpleList', 'SMAP_adminField',
                $header_arr, '',
                $configs, $defsort_arr, '', $extra,
                '', NULL);

    $T = new Template($_CONF['path'] . '/plugins/sitemap/templates');
    $T->set_file('update', 'updatemap.thtml');
    $sitemaps = explode(';', Config::get('xml_filenames'));
    $last_updated = @filemtime($_CONF['path_rss'] . trim($sitemaps[0]));
    $D = new Date($last_updated, $_CONF['timezone']);
    if ($last_updated === false) {
        $last_updated = $LANG_SMAP['unknown'];
    } else {
        $last_updated = $D->format($_CONF['date'], true);
    }
    $T->set_var('last_updated', Sitemap::escape($last_updated));
    $T->parse('output', 'update');
    $retval .= $T->finish($T->get_var('output'));
    return $retval;
}


//=====================================
//  Main
//=====================================

USES_lib_admin();

$expected = array(
    'move', 'updatenow', 'clearcache',
);
$Request = Request::getInstance();
list($action, $actionval) = $Request->getAction($expected);

switch ($action) {
case 'move':
    Plugin::Move($Request->getString('id'), $actionval);
    break;
case 'updatenow':
    $st = ini_get('short_open_tag');
    if( $st ) {
        COM_setMsg($LANG_SMAP['xml_sitemap_error'],'error');
    } else {
        Sitemap::createGoogle();
    }
    break;
case 'clearcache':
    Sitemap\Cache::clear();
    break;
}

$header = '';
$menu_arr = array(
    array(
        'url' => $_CONF['site_admin_url'],
        'text' => $LANG_ADMIN['admin_home'],
    ),
    array(
        'url' => $_CONF['site_admin_url'] . '/plugins/sitemap/index.php?clearcache=x',
        'text' => $LANG_SMAP['clear_cache'],
    ),
);

$header .= COM_startBlock(
    $LANG_SMAP['admin'] . ' v' . Config::get('pi_version'),
    '',
    COM_getBlockTemplate('_admin_block', 'header')
);
$header .= ADMIN_createMenu($menu_arr, $LANG_SMAP['admin_help']);
$header .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

// Displays
$display = COM_siteHeader();
$display .= $header;
$display .= SMAP_adminList();
$display .= COM_siteFooter();
echo $display;
exit;

