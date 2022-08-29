<?php
/**
 * Sitemap driver for the Static Pages.
 *
 * @author      Mark R. Evans <mark@glfusion.org>
 * @copyright   Copyright (c) 2008 Mark R. Evans <mark@glfusion.org>
 * @copyright   Copyright (c) 2007-2008 Mystral-kk <geeklog@mystral-kk.net>
 * @package     glfusion
 * @version     v2.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
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
 * Sitemap driver for the Staticpages plugin.
 * @package sitemap
 */
class staticpages extends BaseDriver
{
    protected $name = 'staticpages';

    public function getDisplayName()
    {
        global $LANG_STATIC;
        return $LANG_STATIC['staticpages'];
    }


    /**
    *   @param  mixed   $tid    Topic or Category ID, not used
    *   @return array of (
    *   'id'        => $id (string),
    *   'title'     => $title (string),
    *   'uri'       => $uri (string),
    *   'date'      => $date (int: Unix timestamp),
    *   'image_uri' => $image_uri (string)
    * )
    */
    public function getItems($tid = false)
    {
        global $_CONF, $_SP_CONF, $_TABLES;

        $retval = array();

        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('sp_id', 'sp_title', 'UNIX_TIMESTAMP(sp_date) AS day')
           ->from($_TABLES['staticpage'])
           ->where('sp_search = 1')
           ->andWhere('sp_status = 1')
           ->andWhere('sp_date <= NOW()');
        if ($this->uid > 0) {
            $qb->andWhere($db->getPermSQL('', $this->uid));
            if ($this->all_langs === false) {
                $qb->andWhere($db->getLangSQL('sid', ''));
            }
        }
        if (in_array($_SP_CONF['sort_by'], array('id', 'title', 'date'))) {
            $crit = $_SP_CONF['sort_by'];
        } else {
            $crit = 'id';
        }
        $qb->orderBy('sp_' . $crit);

        try {
            $stmt = $qb->execute();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                $Item = new Item;
                $Item->withItemId($A['sp_id'])
                     ->withTitle($A['sp_title'])
                     ->withUrl(COM_buildUrl($_CONF['site_url'] . '/page.php?page=' . $A['sp_id']))
                     ->withDate($A['day']);
                $retval[] = $Item->toArray();
            }
        }
        return $retval;
    }

}

