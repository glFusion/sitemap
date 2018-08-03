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

//=====================================
//  Functions
//=====================================

/**
*   Returns options list for a frequency selection
*
*   @param  string  $selected   Optional value to mark selected
*   @return string      Options to be placed between <select> tags
*/
function SITEMAP_getFreqOptions($selected='')
{
    global $LANG_SMAP;

    $retval = '';

    foreach ($LANG_SMAP['freqs'] as $key=>$text) {
        $sel = $key == $selected ? 'selected="selected"' : '';
        $retval .= '<option value="' . $key . '" ' . $sel . '>' .
                $text . '</option>' . LB;
    }
    return $retval;
}


/**
*   Returns options list for a priority selection
*
*   @param  string  $selected   Optional value to mark selected
*   @return string      Options to be placed between <select> tags
*/
function SITEMAP_getPriorityOptions($selected='')
{
    global $_SMAP_CONF;

    $retval = '';

    foreach ($_SMAP_CONF['priorities'] as $value) {
        $sel = $value == $selected ? 'selected="selected"' : '';
        $retval .= '<option value="' . $value. '" ' . $sel . '>' .
                $value . '</option>' . LB;
    }
    return $retval;
}


/**
*   Callback function to display each field.
*
*   @param  string  $fieldname  Name of field from header array
*   @param  mixed   $fieldvalue Field's value
*   @param  array   $A          Array of all fieldname=>value pairs
*   @param  array   $icon_arr   Array of icons (not used)
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
            $retval .= COM_createLink(
                '<img src="' . $_CONF['layout_url'] .
                '/images/up.png" height="16" width="16" border="0" />',
                $_CONF['site_admin_url'] . '/plugins/sitemap/index.php?move=up&id=' . $A['pi_name']
                );
        } else {
            $retval .= '<img src="' . $_CONF['layout_url'] .
                '/images/blank.gif" height="16" width="16" border="0" />';
        }
        if ($A['orderby'] < $extra['map_count'] * 10) {
            $retval .= COM_createLink(
                '<img src="' . $_CONF['layout_url'] .
                    '/images/down.png" height="16" width="16" border="0" />',
                $_CONF['site_admin_url'] . '/plugins/sitemap/index.php?move=down&id=' . $A['pi_name']
                );
        }
        break;

    case 'xml_enabled':
    case 'html_enabled':
        list($fldid, $trash) = explode('_', $fieldname);
        $chk = $fieldvalue == 1 ? 'checked="checked"' : '';
        $retval = "<input id=\"{$fldid}_ena_{$pi_name}\" type=\"checkbox\" name=\"{$fieldname}[{$A['pi_name']}]\" value=\"1\" $chk onclick='SMAP_toggleEnabled(this, \"{$A['pi_name']}\", \"{$fldid}\");' />" . LB;
         break;

    case 'freq':
        $retval = "<select id=\"freqsel_{$pi_name}\" name=\"freq[{$A['pi_name']}]\"
            onchange='SMAP_updateFreq(\"{$pi_name}\", this.value);'>" . LB;
        $retval .= SITEMAP_getFreqOptions($fieldvalue);
        $retval .= '</select>' . LB;
        break;

    case 'priority':
        $retval = "<select id=\"priosel_{$pi_name}\" name=\"priority[{$A['pi_name']}]\"
            onchange='SMAP_updatePriority(\"{$pi_name}\", this.value);'>" . LB;
        $retval .= SITEMAP_getPriorityOptions($fieldvalue);
        $retval .= '</select>' . LB;
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
*   Uses lib-admin to list the form results.
*
*   @param  string  $frm_id         ID of form
*   @param  string  $instance_id    Optional form instance ID
*   @return string          HTML for the list
*/
function SMAP_adminList()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $_SMAP_CONF, $LANG_SMAP;

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
    $configs = Sitemap\Config::getAll();
    foreach ($configs as $pi_name=>$config) {
        // Hack to indicate any plugins that are installed but disabled.
        $configs[$pi_name]['pi_status'] = Sitemap\Config::piEnabled($pi_name);
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
    $sitemaps = explode(';', $_SMAP_CONF['xml_filenames']);
    $last_updated = @filemtime($_CONF['path_html'] . trim($sitemaps[0]));
    $D = new Date($last_updated, $_CONF['timezone']);
    if ($last_updated === false) {
        $last_updated = $LANG_SMAP['unknown'];
    } else {
        $last_updated = $D->format($_CONF['date'], true);
    }
    $T->set_var('last_updated', SITEMAP_escape($last_updated));
    $T->parse('output', 'update');
    $retval .= $T->finish($T->get_var('output'));
    return $retval;
}


//=====================================
//  Main
//=====================================

USES_lib_admin();

$action = '';

$expected = array(
    'move', 'updatenow',
);
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}

switch ($action) {
case 'move':
    Sitemap\Config::Move($_GET['id'], $actionval);
    break;
case 'updatenow':
    $st = ini_get('short_open_tag');
    if( $st ) {
        COM_setMsg($LANG_SMAP['xml_sitemap_error'],'error');
    } else {
        SITEMAP_createGoogleSitemap();
    }
    break;
}

$header = '';
$menu_arr = array (
    array('url' => $_CONF['site_admin_url'],
          'text' => $LANG_ADMIN['admin_home'])
);

$header .= COM_startBlock($LANG_SMAP['admin'], '',
                          COM_getBlockTemplate('_admin_block', 'header'));
$header .= ADMIN_createMenu($menu_arr, $LANG_SMAP['admin_help'], $_CONF['site_url'] . '/sitemap/images/sitemap.png');
$header .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

// Displays
$display = COM_siteHeader();
$display .= $header;
$display .= SMAP_adminList();
$display .= COM_siteFooter();
echo $display;
exit;

?>
