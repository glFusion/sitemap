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

if (!in_array('sitemap', $_PLUGINS) || !Sitemap\Sitemap::canView()) {
    COM_404();
    exit;
}
use Sitemap\Config;

/**
* Returns a selector to choose data source
*/
function SITEMAP_getSelectForm($selected = 'all')
{
    global $_CONF, $LANG_SMAP;

    $this_script = $_CONF['site_url'] . '/sitemap/index.php';
    $Drivers = Sitemap\Plugin::getDrivers();
    $LT = new Template($_CONF['path'] . '/plugins/' . Config::PI_NAME. '/templates');
    $LT->set_file('selector', 'selector.thtml');
    $LT->set_var(array(
        'action_url'    => $this_script,
        'all_sel'   => $selected == 'all' ? 'selected="selected"' : '',
    ) );
    $LT->set_block('selector', 'selectOpts', 'opts');
    foreach ($Drivers as $Driver) {
        $LT->set_var(array(
            'driver_name'   => $Driver->getName(),
            'driver_display_name' => $Driver->getDisplayName(),
            'selected' => $selected == $Driver->getName() ? 'selected="selected"' : '',
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
*   @param $Driver  reference to driver object
*   @param $pid     id of parent category, may be false
*   @return         array of ( num_items, html )
*
*   @destroy        $T->var( 'items', 'item', 'item_list' )
*/
function SITEMAP_buildItems($Driver, $pid)
{
    global $_CONF, $T, $_USER;

    $html = '';

    $dt = new \Date('now',$_USER['tzid']);

    $T->clear_var('items');
    $sp_except = Config::get('sp_except');
    if (is_string($sp_except)) {
        $sp_except = explode(' ', $sp_except);
    } else {
        $sp_except = array();
    }
    $key = $Driver->getName() . '_' . $pid;
    $items = Sitemap\Cache::get($key);
    if ($items === NULL) {
        $items = $Driver->getItems($pid);
        Sitemap\Cache::set($key, $items, array($Driver->getName(), 'plugin'));
    }
    $num_items = count($items);
    if ($num_items > 0 && is_array($items)) {
        foreach ($items as $item) {
            $link = COM_createLink($Driver->Escape($item['title']),
                    $item['uri'],
                    array('title'=> $Driver->Escape($item['title'])) );
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
 * Builds a category and items belonging to it.
 *
 * @param   object  $Driver Reference to driver object
 * @param   array   $cat    Array of category data
 * @return  array       Array of (num_items, HTML string)
 *
 * @destroy        $T->var( 'child_categories', 'category', 'num_items' )
 */
function SITEMAP_buildCategory(object $Driver, array $cat) : array
{
    global $T, $LANG_SMAP;

    $num_total_items = 0;
    $temp = $T->get_var('child_categories');    // Push $T->var('child_categories')

    // Builds {child_categories}
    $key = $Driver->getName() . '_category_' . $cat['id'];
    $child_categories = Sitemap\Cache::get($key);
    if ($child_categories === NULL) {
        $child_categories = $Driver->getChildCategories($cat['id']);
        Sitemap\Cache::set($key, $child_categories, array($Driver->getName(), 'plugin'));
    }

    if (count($child_categories) > 0) {
        $child_cats = '';

        foreach ($child_categories as $child_category) {
            list($num_child_category, $child_cat) = SITEMAP_buildCategory($Driver, $child_category);
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
    if ($cat['uri']) {
        $category_link = '<a href="' . $cat['uri'] . '">'
              . $Driver->escape($cat['title']) . '</a>';
    } else {
        $category_link = $Driver->escape($cat['title']);
    }

    // Builds {items}
    list($num_items, $items) = SITEMAP_buildItems($Driver, $cat['id']);
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

// Retrieves vars
$selected = 'all';
$Request = Sitemap\Models\Request::getInstance();
$selected = COM_applyFilter($Request->getString('type'));

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

$Drivers = Sitemap\Plugin::getDrivers();
foreach ($Drivers as $Driver) {
    $num_items = 0;

    // Only display enabled selected driver, or "all"
    if (
        !$Driver->html_enabled ||
        ($selected != 'all' && $selected != $Driver->getName())
    ) {
        continue;
    }

    $entry = $Driver->getEntryPoint();
    if ($entry == NULL) {   // some plugins may return false
        $entry = $Driver->getDisplayName();
    } else {
        $entry = '<a href="' . $entry . '">' . $Driver->getDisplayName()
               . '</a>';
    }
    $T->set_var('lang_data_source', $entry);

    $categories = $Driver->getChildCategories(false);
    if (count($categories) == 0) {
        list($num_items, $items) = SITEMAP_buildItems($Driver, false);
        $T->set_var('category_list', $items);
    } else {
        $cats = '';
        foreach ($categories as $category) {
            list($num_cat, $cat) = SITEMAP_buildCategory($Driver, $category);
            $cats .= $cat;
            $num_items += $num_cat;
        }

        $T->set_var('categories', $cats);
        $T->set_var('pi_name', $Driver->getName());
        $T->parse('category_list', 't_category_list');
    }
    if ($num_items == 0) continue;
    $T->set_var('num_items', $num_items);
    $T->set_var('pi_name', $Driver->getName());
    $T->parse('data_sources', 't_data_source', true);
}

$T->set_var('selector', SITEMAP_getSelectForm($selected));
$T->parse('output', 't_index');

$display = COM_siteHeader()
            . $T->finish($T->get_var('output'))
            . COM_siteFooter();
echo $display;

