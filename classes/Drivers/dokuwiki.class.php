<?php
// +--------------------------------------------------------------------------+
// | Sitemap driver for the Dokuwiki plugin
// +--------------------------------------------------------------------------+
// | dokuwiki.class.php                                                       |
// |                                                                          |
// | DokuWiki Plugin interface                                                |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2009-2017 by the following authors:                        |
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
namespace Sitemap\Drivers;

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

class dokuwiki extends BaseDriver
{
    protected $name = 'dokuwiki';

    /**
    * Returns the location of index.php of each plugin
    */
    public function getEntryPoint()
    {
        global $_CONF, $_DW_CONF;
        return $_CONF['site_url'] . $_DW_CONF['public_dir']     .'doku.php';
    }


    /**
    *   Get the friendly display name
    *
    *   @return string  Display name
    */
    public function getDisplayName()
    {
        global $LANG_DW00;
        return $LANG_DW00['menulabel'];
    }

    public function getChildCategories($pid = false)
    {
        global $conf, $LANG_DW00,$_CONF,$_DW_CONF, $_USER;

        require_once DOKU_INC.'inc/init.php';

        $dir = $conf['datadir'];
        if ( $pid == false ) $pid = '';
        $ns  = cleanID($pid);

        $data = array();
        $origns = $ns;

        $ns  = utf8_encodeFN(str_replace(':','/',$ns));

        search($data,$conf['datadir'],'search_index',array('ns' => $ns));

        $entries = array();

        foreach( $data AS $item) {
            if ( ((($origns == "" ) || ($item['type'] == 'd' && strstr($item['id'],$origns) !== false) )) && $origns != $item['id'] ) {
                if (auth_aclcheck($item['id'],'',array()) < AUTH_READ) {
                    continue;
                }
                $entry = array();
                $entry['id'] = $item['id'];
                $entry['pid'] = $pid;

                $entry['title'] = p_get_metadata($item['id'], 'title');
                if ( empty($entry['title']) ) $entry['title'] = $item['id'];
                switch ($conf['userewrite']) {
                    case 1: // URL rewrite - .htaccess
                        $entry['uri'] = $_CONF['site_url'].$_DW_CONF['public_dir'].$entry['id'];
                        break;
                    case 0: // URL rewrite - off
                        $entry['uri'] = $_CONF['site_url'] . $_DW_CONF['public_dir']
                        . 'doku.php?id=' . $entry['id'];
                        break;
                    case 2: // URL rewrite - internal
                        $entry['uri'] = $_CONF['site_url'] . $_DW_CONF['public_dir']
                        . 'doku.php/' . $entry['id'];
                        break;
                    default:
                        continue;
                }
                $entry['date']      = false;
                $entry['image_uri'] = false;
                $entries[] = $entry;
            }
        }
        return $entries;
    }


    /**
    * Returns an array of (
    *   'id'        => $id (string),
    *   'title'     => $title (string),
    *   'uri'       => $uri (string),
    *   'date'      => $date (int: Unix timestamp),
    *   'image_uri' => $image_uri (string)
    * )
    */
    public function getItems($category = false)
    {
        global $conf, $LANG_DW00,$_CONF,$_DW_CONF, $_USER;

        require_once (DOKU_INC.'inc/init.php');

        $dir = $conf['datadir'];

        $pages = array();
        $ns  = utf8_encodeFN(str_replace(':','/',$category));

        switch ($this->smap_type ) {
            case 'xml' :
                $opts = array(
                    'pagesonly' => true,
                    'listdirs' => true,
                    'listfiles' => true,
                    'sneakyacl' => $conf['sneaky_index'],
                    // Hacky, should rather use recmatch
                    'depth' => 0
                );
                search($pages,$conf['datadir'],'search_universal',$opts);
                break;
            default :
                search($pages,$conf['datadir'],'search_index',array('ns' => $ns));
                break;
        }

        $entries = array();
        foreach ( $pages AS $page ) {
            $id = $page['id'];

            if ( $id == $category ) continue;
            if ( $page['type'] == 'd' ) continue;

            if ( $category != false && stristr($id,$category) === false ) {
                continue;
            }
            //skip hidden, non existing and restricted files
            if (isHiddenPage($id)) {
                continue;
            }
            if (auth_aclcheck($id,'',array()) < AUTH_READ) {
                continue;
            }
            $id = trim($id);
            $date = @filemtime(wikiFN($id));
            if (!$date) {
                continue;
            }

            $entry = array();
            $entry['id']    = $id;
            $entry['title'] = p_get_metadata($id, 'title');
            if ( $entry['title'] == '' ) $entry['title'] = $id;
            $entry['date'] = $date;
            switch ($conf['userewrite']) {
                case 1: // URL rewrite - .htaccess
                    $entry['uri'] = $_CONF['site_url'].$_DW_CONF['public_dir'].$entry['id'];
                    break;

                case 0: // URL rewrite - off
                    $entry['uri'] = $_CONF['site_url'] . $_DW_CONF['public_dir']
                    . 'doku.php?id=' . $entry['id'];
                    break;

                case 2: // URL rewrite - internal
                    $entry['uri'] = $_CONF['site_url'] . $_DW_CONF['public_dir']
                    . 'doku.php/' . $entry['id'];
                    break;

                default:
                    break;
            }
            $entries[] = $entry;
        }
        return $entries;
    }
}
?>
