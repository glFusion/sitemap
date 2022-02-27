<?php
// +--------------------------------------------------------------------------+
// | Site Map Plugin for glFusion                                             |
// +--------------------------------------------------------------------------+
// | german_utf-8.php                                                         |
// |                                                                          |
// | German Language File (UTF-8 Version)                                     |
// | Modifiziert: August 09 Tony Kluever                                      |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2009-2015 by the following authors:                        |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// |                                                                          |
// | Based on the Site Map Plugin                                             |
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

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

$LANG_SMAP = array (
    'plugin'            => 'Sitemap-Plugin',
    'access_denied'     => 'Zugriff verweigert',
    'access_denied_msg' => 'Nur Root-Benutzer haben Zugriff auf diese Seite. Dein Benutzername und IP wurden aufgezeichnet.',
    'admin'                => 'Sitemap-Plugin-Admin',
    'admin_help'        => 'Check the boxes to change whether each element appears in the files or online sitemap. Use the selections to change the frequency and priority, and click on the arrows to change order in which the elements appear in the sitemaps. Changes take effect immediately.
<p>To immediately recreate the sitemap XML files, click &quot;Update now&quot;.',
    'error'             => 'Installationsfehler',
    'install_header'    => 'Sitemap-Plugin - Installation/Deinstallation',
    'install_success'    => 'Installation erfolgreich',
    'install_fail'        => 'Installation fehlgeschlagen -- Schau in die Datei error.log f�r mehr Infos.',
    'uninstall_success'    => 'Deinstallation erfolgreich',
    'uninstall_fail'    => 'Deinstallation fehlgeschlagen -- Schau in die Datei error.log f�r mehr Infos.',
    'uninstall_msg'        => 'Sitemap-Plugin wurde erfolgreich installiert.',
    'dataproxy_required' => 'Das Dataproxy-Plugin muss installiert und aktiviert sein, bevor das Sitemap-Plugin installiert wird.',
    'version_required'  => 'Das Sitemap-Plugin ben�tigt glFusion v1.1.0 oder neuer',
    'menu_label'        => 'Sitemap',
    'sitemap'           => 'Sitemap',
    'submit'            => 'Senden',
    'all'               => 'Alle',
    'article'           => 'Artikel',
    'comments'          => 'Kommentare',
    'trackback'         => 'Trackbacks',
    'staticpages'       => 'Stat. Seiten',
    'calendar'          => 'Kalender',
    'links'             => 'Links',
    'polls'             => 'Umfragen',
    'dokuwiki'          => 'DokuWiki',
    'forum'             => 'Forum',
    'filemgmt'          => 'Dateiverwaltung (FileMgmt)',
    'faqman'            => 'FAQ',
    'mediagallery'      => 'Mediengalerie',
    'evlist'            => 'evList',
    'classifieds'       => 'Classified Ads',
    'sitemap_setting'   => 'Sitemap-Konfiguration',
    'sitemap_setting_misc' => 'Anzeigeeinstellungen',
    'order'             => 'Sortierung',
    'up'                => 'Hoch',
    'down'              => 'Runter',
    'anon_access'       => 'Erlaube G�sten auf die Sitemap zuzugreifen',
    'sitemap_in_xhtml'  => 'Zeigt Sitemap in XHTML',
    'date_format'       => 'Datumsformat',
    'desc_date_format'  => 'Bei <strong>Datumsformat</strong>, gib den Formatierungsstring ein, so wie er auch in PHP \' <a href="http://www.php.net/manual/en/function.date.php">date() function</a> verwendet wird.',
    'sitemap_items'     => 'Zu verwendene Objekte in Sitemap',
    'gsmap_setting'     => 'Google-Sitemap - Konfiguration',
    'file_creation'     => 'Einstellungen zur Dateierstellung',
    'xml_filenames' => 'Dateiname: ',
    'time_zone'         => 'Zeitzone: ',
    'update_now'        => 'Jetzt aktualisieren!',
    'last_updated'      => 'Zuletzt aktualisiert: ',
    'unknown'           => 'unbekannt',
    'desc_filename'     => 'Bei <strong>Dateiname</strong>, gib den Dateinamen der Google-Sitemap ein. Du kannst mehr als einen Dateinamen angeben, getrennt durch Semikolon(;). F�r Mobiltelefon-Sitemap, gib "mobile.xml" ein.',
    'desc_time_zone'    => 'Bei <strong>Zeitzone</strong>, gib die Zeitzone des Servers ein, auf dem glFusion installiert ist. Verwende <a href="http://en.wikipedia.org/wiki/Iso8601">ISO 8601</a> Format ((+|-)hh:mm).  e.g. +09:00(Tokyo), +01:00(Paris), +01:00(Berlin), +00:00(London), -05:00(New York), -08:00(Los Angeles)',
    'gsmap_items'       => 'Zu verwendene Objekte in Google-Sitemap',
    'item_name'         => 'Objektname',
    'freq'              => 'H�ufigkeit',
    'always'            => 'immer',
    'hourly'            => 'st�ndlich',
    'daily'             => 't�glich',
    'weekly'            => 'w�chentlich',
    'monthly'           => 'monatlich',
    'yearly'            => 'j�hrlich',
    'never'             => 'niemals',
    'priority'          => 'Priorit�t',
    'desc_freq'         => '<strong>H�ufigkeit</strong> teilt den Google-Webcrawlern mit, wir oft das Objekt vorraussichtlich aktualisert wird. Auch wenn Du "niemals" w�hlst, �berpr�fen die Google-Crawler irgendwann, ob das Objekt aktualisiert wurde.',
    'desc_priority'     => 'Bei <strong>Priorit�t</strong>, gib einen Wert zwischen <strong>0.0</strong> (niedrigster) und <strong>1.0</strong> (h�chster) ein. Der Standardwert ist <strong>0.5</strong>.',
    'common_setting'    => 'Allgemeine Einstellungen',
    'back_to_top'       => 'Zur�ck nach oben',
    'freqs' => array(
        'always'    => 'Always',
        'hourly'    => 'Hourly',
        'daily'     => 'Daily',
        'weekly'    => 'Weekly',
        'monthly'   => 'Monthly',
        'yearly'    => 'Yearly',
        'never'     => 'Never',
    ),
    'xml_enabled' => 'XML Enabled?',
    'html_enabled' => 'HTML Enabled?',
    'smap_updated' => '%1$s %2$s sitemap has been %3$s.',
    'freq_updated' => '%1$s Sitemap Frequency is now %2$s',
    'prio_updated' => '%1$s Sitemap Priority is now %2$s',
    'enabled'   => 'enabled',
    'disabled'  => 'disabled',
    'uncategorized' => 'Uncategorized',
    'untitled' => 'Untitled',
    'xml_sitemap_error' => 'Unable to create XML sitemap due to PHP configuration: \'short_open_tag\' must be set to Off',
    'clear_cache' => 'Clear Cache',
);

// Localization of the Admin Configuration UI
$LANG_configsections['sitemap'] = array(
    'label' => 'Sitemap',
    'title' => 'Sitemap Configuration',
);

$LANG_confignames['sitemap'] = array(
    'xml_filenames' => 'Sitemap filename(s)',
    'view_access' => 'Users who can view the sitemap',
    'auto_add_plugins' => 'Automatically add new plugins?',
    'schedule' => 'When to Recreate XML Sitemaps',
);
$LANG_configsubgroups['sitemap'] = array(
    'sg_main' => 'Main Settings',
);

$LANG_fs['sitemap'] = array(
    'fs_main' => 'Main Sitemap Settings',
);
// Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
$LANG_configSelect['sitemap'] = array(
    0 => array(1 => 'True', 0 => 'False'),
    1 => array(true => 'True', false => 'False'),
    3 => array(1 => 'Yes', 0 => 'No'),
    4 => array(0 => 'No Access', 1 => 'Logged-In Only', 2 => 'All Users'),
    5 => array(0 => 'Always', 1 => 'If Content Changes', 2 => 'Manually'),
);

?>
