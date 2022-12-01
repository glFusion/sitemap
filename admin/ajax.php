<?php
/**
 * Common AJAX functions.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2022 Lee Garner <lee@leegarner.com>
 * @package     sitemap
 * @version     v2.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/**
*  Include required glFusion common functions
*/
require_once '../../../lib-common.php';
use Sitemap\Plugin;
use Sitemap\Models\Request;

if (!in_array('sitemap', $_PLUGINS) ||
    !SEC_hasRights('sitemap.admin')) {
    COM_404();
    exit;
}
$Request = Request::getInstance();
$id = $Request->getString('id');
switch ($Request->getString('action')) {
case 'toggleEnabled':
    $type = $Request->getString('type');
    $oldval = $Request->getInt('oldval');
    switch ($type) {
    case 'html':
    case 'xml':
        $newval = Plugin::toggleEnabled($id, $type, $oldval);
        $newval_txt = $newval == 1 ? $LANG_SMAP['enabled'] : $LANG_SMAP['disabled'];
        break;

    default:
        exit;
    }
    $result = array(
        'newval' => $newval,
        'id' => $id,
        'type' => $type,
        'statusMessage' => sprintf(
            $LANG_SMAP['smap_updated'],
            strtoupper($type), ucwords($id), $newval_txt
        ),
    );
    break;

case 'updatefreq':
    $M = new Plugin($id);
    $newfreq = $M->updateFreq($Request->getString('newfreq'));
    $result = array(
        'pi_name'   => $id,
        'newfreq'   => $newfreq,
        'statusMessage' => sprintf($LANG_SMAP['freq_updated'],
                ucwords($id), $LANG_SMAP['freqs'][$newfreq]),
    );
    break;

case 'updatepriority':
    $M = new Plugin($id);
    $newpriority = $M->updatePriority($Request->getFloat('newpriority'));
    $result = array(
        'pi_name'   => $id,
        'newpriority'   => $newpriority,
        'statusMessage' => sprintf($LANG_SMAP['prio_updated'],
                ucwords($id), $newpriority),
    );
    break;
}

$result = json_encode($result);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
//A date in the past
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
echo $result;
