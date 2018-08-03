<?php
// +--------------------------------------------------------------------------+
// | Site Map Plugin for glFusion                                             |
// +--------------------------------------------------------------------------+
// | index.php                                                                |
// |                                                                          |
// | User Interface                                                           |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2008-2018 by the following authors:                        |
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

if (!in_array('sitemap', $_PLUGINS) || !SMAP_canView()) {
    COM_404();
    exit;
}

// Loads config
USES_sitemap_class_config();
smapConfig::loadConfigs();

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
*   Builds items belonging to a category
*
*   @param $driver  reference to driver object
*   @param $pid     id of parent category, may be false
*   @return         array of ( num_items, html )
*
*   @destroy        $T->var( 'items', 'item', 'item_list' )
*/
function SITEMAP_buildItems($driver, $pid)
{
    global $_CONF, $_SMAP_CONF, $T, $_USER;

    $html = '';

    $dt = new \Date('now',$_USER['tzid']);

    $T->clear_var('items');
    if ( isset($_SMAP_CONF['sp_except']) ) {
        $sp_except = explode(' ', $_SMAP_CONF['sp_except']);
    } else {
        $sp_except = array();
    }
    if (version_compare(GVERSION, '2.0.0', '>=')) {
        $c = \glFusion\Cache::getInstance();
        $key = $c->createKey('sitemap_' . $driver->getName() . '_' . $pid);
        if ($c->has($key)) {
            $items = $c->get($key);
        } else {
            $items = $driver->getItems($pid);
            $c->set($key, $items, array('sitemap', 'sitemap_' . $driver->getName()));
        }
    } else {
        $items = $driver->getItems($pid);
    }
    $num_items = count($items);
    if ($num_items > 0 && is_array($items)) {
        foreach ($items as $item) {
            $link = COM_createLink($driver->Escape($item['title']),
                    $item['uri'],
                    array('title'=> $driver->Escape($item['title'])) );
            $T->set_var('item', $link);
            if ($item['date'] !== false) {
                $dt->setTimestamp($item['date']);
                $date = $dt->format($_CONF['shortdate'],true);
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
*   @param $driver  reference to driver object
*   @param $cat     array of category data
*   @return         string HTML
*
*   @destroy        $T->var( 'child_categories', 'category', 'num_items' )
*/
function SITEMAP_buildCategory($driver, $cat)
{
    global $T, $LANG_SMAP;

    $num_total_items = 0;
    $temp = $T->get_var('child_categories');    // Push $T->var('child_categories')

    // Builds {child_categories}
    if (version_compare(GVERSION, '2.0.0', '>=')) {
        $key = 'sitemap_' . $driver->getName() . '_category_' . $cat['id'];
        $c = \glFusion\Cache::getInstance();
        if ($c->has($key)) {
            $child_categories = $c->get($key);
        } else {
            $child_categories = $driver->getChildCategories($cat['id']);
            $c->set($key, $child_categories, array('sitemap', 'sitemap_' . $driver->getName() . '_category'));
        }
    } else {
        $child_categories = $driver->getChildCategories($cat['id']);
    }
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
    }

    // Builds {category}
    if ($cat['title'] == '') {
        // If an empty category title comes in, default to 'Uncategorized'
        $cat['title'] = $LANG_SMAP['uncategorized'];
    }
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

// Make the sitemap base class available
USES_sitemap_class_base();

// Retrieves vars
$selected = 'all';
if (isset($_GET['type'])) {
    $selected = COM_applyFilter($_GET['type']);
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

// Load up an array containing all the enabled sitemap classes.
// Used below to write the sitemap and in the selection creation above.
// Ensures that only valid driver classfiles are used.
global $_SMAP_DRIVERS;
$_SMAP_DRIVERS = array();
foreach ($_SMAP_MAPS as $pi_name=>$pi_config) {
    if ($pi_config['html_enabled'] == 0) continue;
    $classfile = smapConfig::getClassPath($pi_name);
    if ($classfile) {
        include_once $classfile;
        $cls = 'sitemap_' . $pi_name;
        if (class_exists($cls)) {
            $_SMAP_DRIVERS[] = new $cls();
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

$display = COM_siteHeader()
            . $T->finish($T->get_var('output'))
            . COM_siteFooter();
echo $display;

?>
