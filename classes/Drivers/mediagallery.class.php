<?php
/**
 * Sitemap driver for the Mediagallery Plugin.
 *
 * @author     Mark R. Evans <mark@glfusion.org>
 * @copyright  Copyright (c) 2009-2015 Mark R. Evans <mark@glfusion.org>
 * @copyright  Copyright (c) 2007-2008 Mystral-kk <geeklog@mystral-kk.net>
 * @package    glfusion
 * @version    2.1.0
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Sitemap\Drivers;
use glFusion\Database\Database;
use glFusion\Log\Log;
use Sitemap\Models\Item;

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/**
 * Mediagallery sitemap driver.
 * @package sitemap
 */
class mediagallery extends BaseDriver
{
    protected $name = 'mediagallery';

    /**
     * Determine if the current user has access to this plugin's sitemap.
     *
     * @return  boolean     True if access is allowed, False if not
     */
    private function hasAccess()
    {
        global $_CONF, $_TABLES, $_MG_CONF;

        static $retval = null;
        static $loginRequired;

        $db = Database::getInstance();
        if (is_null($retval)) {
            $loginrequired = (int)$db->getItem(
                $_TABLES['mg_config'],
                'config_value',
                array('config_name' => 'loginrequired')
            );
            $retval = !($loginRequired && COM_isAnonUser());
        }
        return $retval;
    }


    /**
     * Get the friendly display name for this plugin.
     *
     * @return  string  Plugin Display Name
     */
    public function getDisplayName()
    {
        global $LANG_MG00;
        return $LANG_MG00['plugin'];
    }


    /**
     * Returns the location of index.php of each plugin.
     *
     * @return  string      Base URL to the plugin
     */
    public function getEntryPoint()
    {
        global $_MG_CONF;
        return $_MG_CONF['site_url'] . '/index.php';
    }


    /**
     * @param $pid int/string/boolean id of the parent category.  False means
     *        the top category (with no parent)
     * @return array(
     *   'id'        => $id (string),
     *   'pid'       => $pid (string: id of its parent)
     *   'title'     => $title (string),
     *   'uri'       => $uri (string),
     *   'date'      => $date (int: Unix timestamp),
     *   'image_uri' => $image_uri (string)
     *  )
     */
    public function getChildCategories($pid = false)
    {
        global $_CONF, $_TABLES, $_MG_CONF;

        $entries = array();

        if (!$this->hasAccess()) {
            return $entries;
        }

        if ($pid === false) {
            $pid = 0;
        }

        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('album_id', 'album_title', 'album_parent', 'last_update')
           ->from($_TABLES['mg_albums'])
           ->where('album_parent = :pid')
           ->setParameter('pid', $pid, Database::STRING)
           ->orderBy('album_order');
        if ($this->uid > 0) {
            $qb->andWhere($db->getPermSQL('', $this->uid))
               ->andWhere('hidden = 0');
        }

        try {
            $stmt = $qb->execute();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                $item = new Item;
                $item['id'] = $A['album_id'];
                $item['pid'] = $A['album_parent'];
                $item['title'] = $A['album_title'];
                $item['uri'] = $_MG_CONF['site_url'] . '/album.php?aid=' . $A['album_id'];
                $item['date'] = $A['last_update'];
                $entries[] = $item->toArray();
            }
        }
        return $entries;
    }


    /**
     * Get the items under a given category ID.
     *
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
        global $_CONF, $_TABLES, $_MG_CONF, $LANG_SMAP;

        $entries = array();
        $category = (int)$category;

        if (!$this->hasAccess()) {
            return $entries;
        }
        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('a.media_id', 'm.media_title', 'm.media_time')
           ->from($_TABLES['mg_media_albums'], 'a')
           ->leftJoin('a', $_TABLES['mg_media'], 'm', 'a.media_id = m.media_id')
           ->orderBy('a.media_order');
        if ($category > 0) {
            $qb->where('a.album_id = :category')
               ->setParameter('category', $category, Database::INTEGER);
        }

        try {
            $stmt = $qb->execute();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }

        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                if (empty($A['media_title'])) {
                    $A['media_title'] = $LANG_SMAP['untitled'];
                }
                $item = new Item;
                $item['id'] = $A['media_id'];
                $item['title'] = $A['media_title'];
                $item['uri'] = $_MG_CONF['site_url'] . '/media.php?s=' . urlencode($A['media_id']);
                $item['date'] = $A['media_time'];
                $entries[] = $item->toArray();
            }
        }
        return $entries;
    }

}

