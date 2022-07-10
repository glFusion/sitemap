<?php
/**
 * Sitemap driver for glFusion articles.
 *
 * @author     Mark R. Evans <mark@glfusion.org>
 * @copyright  Copyright (c) 2008 Mark R. Evans <mark@glfusion.org>
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
 * Article sitemap driver class.
 * @package sitemap
 */
class article extends BaseDriver
{
    protected $name = 'article';

    public function getName()
    {
        return 'article';
    }

    public function getDisplayName()
    {
        global $LANG33;
        return $LANG33[55];
    }

    public function getChildCategories($pid = false) : array
    {
        global $_CONF, $_TABLES;

        $retval = array();
        if ($pid !== false) {
            return $retval;     // Only one level of categories supported
        }

        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('tid', 'topic', 'imageurl')
           ->from($_TABLES['topics'])
           ->where('1=1');
        if (!COM_isAnonUser()) {
            $tids = $db->getItem($_TABLES['userindex'], 'tids', array('uid' => $this->uid));
            if (!empty($tids)) {
                $tids = explode(' ', $tids);
                $qb->andWhere('tid NOT IN (:tids')
                    ->setParameter('tids', $tids, Database::PARAM_STR_ARRAY);
            }
        }

        // Adds permission check.  When uid is 0, then it means access as Root
        if ($this->uid > 0) {
            $qb->andWhere($db->getPermSql('', $this->uid));
        }

        // Adds lang id. When uid is 0, then it means access as Root
        if ($this->uid > 0 && $this->all_langs === false) {
            $qb->andWhere($db->getLangSQL('tid', ''));
        }

        if ($_CONF['sortmethod'] == 'alpha') {
            $qb->orderBy('topic', 'ASC');
        } else {
            $qb->orderBy('sortnum', 'ASC');
        }

        try {
            $stmt = $qb->execute();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                $entry = new Item;
                $entry['id']        = $A['tid'];
                $entry['title']     = $A['topic'];
                $entry['uri']       = $_CONF['site_url'] . '/index.php?topic=' . $entry['id'];
                $entry['image_uri'] = $A['imageurl'];
                $retval[] = $entry->toArray();
            }
        }
        return $retval;
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
    public function getItems($tid = false)
    {
        global $_CONF, $_TABLES;

        $retval = array();
        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('sid', 'title', 'UNIX_TIMESTAMP(date) AS day')
           ->from($_TABLES['stories'])
           ->where('draft_flag = 0')
           ->andWhere('date <= NOW()')
           ->orderBy('date', 'DESC');
        if ($tid !== false) {
            $qb->andWhere('tid = :tid')
               ->setParameter('tid', $tid, Database::STRING);
        }
        if ($this->uid > 0) {
            $qb->andWhere($db->getTopicSql('', $this->uid))
               ->andWhere($db->getPermSql('', $this->uid));
            if ($this->all_langs === false) {
                $qb->andWhere($db->getLangSQL('sid', ''));
            }
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
                $item['id'] = $A['sid'];
                $item['title'] = $A['title'];
                $item['uri'] = COM_buildUrl($_CONF['site_url'] . '/article.php?story=' . $A['sid']);
                $item['date'] = $A['day'];
                $retval[] = $item->toArray();
            }
        }
        return $retval;
    }

}

