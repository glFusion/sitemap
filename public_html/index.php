<?php
// +--------------------------------------------------------------------------+
// | Site Map Plugin for glFusion                                             |
// +--------------------------------------------------------------------------+
// | index.php                                                                |
// |                                                                          |
// | User Interface                                                           |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2008-2015 by the following authors:                        |
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

require_once '../lib-common.php';

if (!in_array('sitemap', $_PLUGINS)) {
    COM_404();
    exit;
}

// Loads config
//SITEMAP_loadConfig();
USES_sitemap_class_config();
smapConfig::loadConfigs();

if (!in_array('sitemap', $_PLUGINS) ||
    (COM_isAnonUser() && ($_SMAP_CONF['anon_access'] == 0 || $_CONF['loginrequired'] == 1))) {
    COM_404();
    exit;
}


//===========================
//  Functions
//===========================

/**
* Returns a selector to choose data source
*/
function SITEMAP_getSelectForm($selected = 'all')
{
    global $_CONF, $_SMAP_CONF, $LANG_SMAP, $_SMAP_MAPS, $_SMAP_DRIVERS;

    $this_script = $_CONF['site_url'] . '/sitemap/index.php';
    $num_drivers = count($_SMAP_MAPS);
    $LT = new Template($_CONF['path'] . '/plugins/' . $_SMAP_CONF['pi_name'] . '/templates');
    $LT->set_file('selector', 'selector.thtml');
    $LT->set_var(array(
        'action_url'    => $this_script,
        'all_sel'   => $selected == 'all' ? 'selected="selected"' : '',
        'num_drivers' => $num_drivers,
    ) );
    $LT->set_block('selector', 'selectOpts', 'opts');
    foreach ($_SMAP_DRIVERS as $driver) {
        $LT->set_var(array(
            'driver_name'   => $driver->getName(),
            'driver_display_name' => $driver->getDisplayName(),
            'selected' => $selected == $driver->getName() ? 'selected="selected"' : '',
        ) );

        $LT->parse('opts', 'selectOpts', true);
    }
    $LT->parse('output', 'selector');
    $retval = $LT->finish($LT->get_var('output'));
    return $retval;
}


/**
* Builds items belonging to a category
*
* @param $T       reference to Template object
* @param $driver  reference to driver object
* @param $pid     id of parent category
* @return         array of ( num_items, html )
*
* @destroy        $T->var( 'items', 'item', 'item_list' )
*/
function SITEMAP_buildItems(&$driver, $pid)
{
    global $_SMAP_CONF, $T;

    $html = '';

    $T->clear_var('items');
    if ( isset($_SMAP_CONF['sp_except']) ) {
        $sp_except = explode(' ', $_SMAP_CONF['sp_except']);
    } else {
        $sp_except = array();
    }

    $items = $driver->getItems($pid);
    $num_items = count($items);
    if ($num_items > 0 && is_array($items)) {
        foreach ($items as $item) {
            // Static pages
            /*if ($driver->getName() == 'staticpages') {
                if (in_array($item['id'], $sp_except)) {
                    $num_items --;
                continue;
                }
                $temp = $driver->getItemById($item['id']);
                $raw  = $temp['raw_data'];
                if ( $raw['sp_centerblock'] == 1 || $raw['sp_search'] != 1) {
                    $num_items --;
                    continue;
                }
            }*/
            $link = COM_createLink($driver->Escape($item['title']),
                    $item['uri'],
                    array('title'=> $driver->Escape($item['title'])) );
            $T->set_var('item', $link);
            if ($item['date'] !== false) {
                $date = date($_SMAP_CONF['date_format'], $item['date']);
                $T->set_var('date', $date);
            }
            $T->parse('items', 't_item', true);
        }

        $T->parse('item_list', 't_item_list');

        $html = $T->finish($T->get_var('item_list'));
    }

    return array($num_items, $html);
}


/**
*   Builds a category and items belonging to it
*
*   @param $T       reference to Template object
*   @param $driver  reference to driver object
*   @param $cat     array of category data
*   @return         string HTML
*
*   @destroy        $T->var( 'child_categories', 'category', 'num_items' )
*/
function SITEMAP_buildCategory(&$driver, $cat)
{
    global $T;

    $num_total_items = 0;
    $temp = $T->get_var('child_categories');    // Push $T->var('child_categories')

    // Builds {child_categories}
    $child_categories = $driver->getChildCategories($cat['id']);

    if (count($child_categories) > 0) {
        $child_cats = '';

        foreach ($child_categories as $child_category) {
            list($num_child_category, $child_cat) = SITEMAP_buildCategory($driver, $child_category);
            $num_total_items += $num_child_category;
            $child_cats      .= $child_cat;
        }

        $T->set_var('categories', $child_cats);
        $T->parse('temp', 't_category_list');
        $child_cats = $T->get_var('temp');
        $T->set_var(
            'child_categories', $child_cats
        );
            //'<br />' . $child_cats . '<br />'
    }

    // Builds {category}
    if ($cat['uri'] !== false) {
        $category_link = '<a href="' . $cat['uri'] . '">'
              . $driver->escape($cat['title']) . '</a>';
    } else {
        $category_link = $driver->escape($cat['title']);
    }

    // Builds {items}
    list($num_items, $items) = SITEMAP_buildItems($driver, $cat['id']);
    $num_total_items += $num_items;
    $T->set_var('num_items', $num_items);
    if (!empty($items)) {
        $T->set_var(
            'items', $items);
//            '<br />' . $items . '<br />'
//        );
    }


    $T->set_var('category', $category_link);
    $T->parse('category', 't_category');
    $retval = $T->finish($T->get_var('category'));

    $T->set_var('child_categories', $temp);        // Pop $T->var('child_categories')
    return array($num_total_items, $retval);
}


//=====================================
//  Main
//=====================================

// Retrieves vars
$selected = 'all';
if (isset($_POST['type'])) {
    $selected = COM_applyFilter($_POST['type']);
}

$T = new Template($_CONF['path'] . 'plugins/sitemap/templates');
$T->set_file(array(
    't_index'         => 'index.thtml',
    't_data_source'   => 'data_source.thtml',
    't_category_list' => 'category_list.thtml',
    't_category'      => 'category.thtml',
    't_item_list'     => 'item_list.thtml',
    't_item'          => 'item.thtml',
) );
$T->set_file('t_category_list','category_list.thtml');
$T->set_file('t_item_list','item_list.thtml');

// Load up an array containing all the sitemap classes.
// Used below to write the sitemap and in the selection creation above.
global $_SMAP_DRIVERS;
$_SMAP_DRIVERS = array();
foreach ($_SMAP_MAPS as $pi_name=>$pi_config) {
    if ($pi_config['html_enabled'] == 0) continue;
    $classfile = smapConfig::getClassPath($pi_name);
    if ($classfile) {
        include_once $classfile;
        $cls = 'sitemap_' . $pi_name;
        if (class_exists($cls)) {
            $_SMAP_DRIVERS[] = new $cls($pi_name, $pi_config);
        }
    }
}

foreach ($_SMAP_DRIVERS as $driver) {
    $num_items = 0;

    // Only display selected driver, or "all"
    if ($selected != 'all' && $selected != $driver->getName()) {
        continue;
    }

    $entry = $driver->getEntryPoint();
    if ($entry === false) {
        $entry = $driver->getDisplayName();
    } else {
        $entry = '<a href="' . $entry . '">' . $driver->getDisplayName()
               . '</a>';
    }
    $T->set_var('lang_data_source', $entry);

    $categories = $driver->getChildCategories(false);
    if (count($categories) == 0) {
        list($num_items, $items) = SITEMAP_buildItems($driver, false);
        $T->set_var('category_list', $items);
    } else {
        $cats = '';
        foreach ($categories as $category) {
            list($num_cat, $cat) = SITEMAP_buildCategory($driver, $category);
            $cats .= $cat;
            $num_items += $num_cat;
        }

        $T->set_var('categories', $cats);
        $T->parse('category_list', 't_category_list');
    }
    if ($num_items == 0) continue;
    $T->set_var('num_items', $num_items);
    $T->parse('data_sources', 't_data_source', true);
}

$T->set_var('selector', SITEMAP_getSelectForm($selected));
$T->parse('output', 't_index');
$page = $T->finish($T->get_var('output'));

$display = COM_siteHeader()
         . $page
         . COM_siteFooter();

echo $display;

?>
