<?php
// +--------------------------------------------------------------------------+
// | Site Map Plugin for glFusion                                             |
// +--------------------------------------------------------------------------+
// | english_utf-8.php                                                        |
// |                                                                          |
// | English Language File (UTF-8 Version)                                    |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2009-2017 by the following authors:                        |
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
    'plugin'            => 'sitemap Plugin',
    'access_denied'     => 'Přístup odepřen',
    'access_denied_msg' => 'Přístup na tuto stránku má pouze root.  Tvé uživatelské jméno a IP adresa byly zaznamenány.',
    'admin'                => 'administrace sitemap Plugin',
    'admin_help'        => 'Zaškrtněte políčka pro změnu, zda se každý prvek objeví v souborech nebo online mapě stránek. Pomocí výběrů změníte frekvenci a prioritu a klikněte na šipky pro změnu pořadí, ve kterém se prvky zobrazují v mapách stránek. Změny se projeví okamžitě.
<p>Chcete-li okamžitě obnovit XML soubory, klikněte &quot;Aktualizovat nyní&quot;.
<br>Klikněte &quot;Vymazat mezipaměť&quot; pro vymazání všech dat a konfigurací z mezipaměti.',
    'error'             => 'Chyby Instalace',
    'install_header'    => 'Instalovat/odinstalovat plugin mapy stránek',
    'install_success'    => 'Instalace úspěšná',
    'install_fail'        => 'Instalace se nezdařila -- Podívejte se na protokol chyb a zjistěte proč.',
    'uninstall_success'    => 'Odinstalace byla úspěšná',
    'uninstall_fail'    => 'Instalace se nezdařila -- Podívejte se na protokol chyb a zjistěte proč.',
    'uninstall_msg'        => 'plugin sitemap byl úspěšně odinstalován.',
    'dataproxy_required' => 'Plugin Data Proxy musí být nainstalován a povolen před instalací Pluginu pro mapu webu',
    'version_required'  => 'Plugin mapy stránek vyžaduje glFusion v1.1.0 nebo novější',
    'menu_label'        => 'Mapa stránek',
    'sitemap'           => 'Mapa stránek',
    'submit'            => 'odeslat',
    'all'               => 'Vše',
    'article'           => 'Příběhy',
    'comments'          => 'Komentáře',
    'trackback'         => 'Trackbacky',
    'staticpages'       => 'Statické stránky',
    'calendar'          => 'Kalendář',
    'links'             => 'Odkazy',
    'polls'             => 'Ankety',
    'dokuwiki'          => 'DokuWiki',
    'forum'             => 'Forum',
    'filemgmt'          => 'Správa souborů',
    'faqman'            => 'Často kladené dotazy (FAQ)',
    'mediagallery'      => 'Galerie médií',
    'evlist'            => 'evList',
    'classifieds'       => 'Krátká reklama',
    'sitemap_setting'   => 'Konfigurace mapy stránek',
    'sitemap_setting_misc' => 'Nastavení zobrazení',
    'order'             => 'Pořadí zobrazení',
    'up'                => 'Nahoru',
    'down'              => 'Dolů',
    'anon_access'       => 'Umožňuje anonymním uživatelům přístup k mapě stránek',
    'sitemap_in_xhtml'  => 'Zobrazí mapu stránek v XHTML',
    'date_format'       => 'Formát datumu',
    'desc_date_format'  => 'Ve <strong>formátu datumu</strong>zadejte formátový řetězec používaný ve formátu parametru PHP <a href="http://www.php.net/manual/en/function.date.php">date() funkce</a>.',
    'sitemap_items'     => 'Položky, které mají být zahrnuty v mapě stránek',
    'gsmap_setting'     => 'Konfigurace Google sitemap',
    'file_creation'     => 'Nastavení vytváření nového souboru',
    'xml_filenames' => 'Název souboru: ',
    'time_zone'         => 'Časové pásmo: ',
    'update_now'        => 'Aktualizovat nyní!',
    'last_updated'      => 'Naposledy aktualizováno: ',
    'unknown'           => 'neznámý',
    'desc_filename'     => 'V <strong>názvusouboru</strong>zadejte název souboru (názvů) Google Sitemap. Můžete zadat více než jeden název  oddělený středníkem(;). Pro mobilní Sitemap, zadejte "mobile.xml".',
    'desc_time_zone'    => 'V <strong>časovém pásmu</strong>zadejte časové pásmo webového serveru, který jste nainstalovali glFusion ve formátu <a href="http://en.wikipedia.org/wiki/Iso8601">ISO 8601</a> ((+|-)hh:mm). např. +09:00(Tokio), +01:00(Paris), +01:00(Berlin), +00:00(London), -05:00(New York), -08:00(Angeles)',
    'gsmap_items'       => 'Položky, které mají být zahrnuty do Google sitemap',
    'item_name'         => 'Název položky',
    'freq'              => 'Frekvence',
    'always'            => 'vždy',
    'hourly'            => 'každou hodinu',
    'daily'             => 'každý den',
    'weekly'            => 'týdně',
    'monthly'           => 'měsíčně',
    'yearly'            => 'ročně',
    'never'             => 'nikdy',
    'priority'          => 'Priorita',
    'desc_freq'         => '<strong>Frekvence</strong> říká  službě Google, jak často bude položka pravděpodobně aktualizována. I když zvolíte "nikdy", Google crawlers někdy zkontroluje, zda je v položce nějaká aktualizace.',
    'desc_priority'     => 'Do nastavení <strong>priority</strong>zadejte hodnotu mezi <strong>0.0</strong> (nejnižší) a <strong>1.</strong> (nejvyšší). Výchozí hodnota je <strong>0.5</strong>.',
    'common_setting'    => 'Běžná nastavení',
    'back_to_top'       => 'Zpět nahoru',
    'freqs' => array(
        'always'    => 'Vždy',
        'hourly'    => 'Každou hodinu',
        'daily'     => 'Denně',
        'weekly'    => 'Týdně',
        'monthly'   => 'Měsíčně',
        'yearly'    => 'Ročně',
        'never'     => 'Nikdy',
    ),
    'xml_enabled' => 'XML povoleno?',
    'html_enabled' => 'HTML povoleno?',
    'smap_updated' => '%1$s %2$s sitemap byla %3$s.',
    'freq_updated' => '%1$s Frekvence mapy stránek je nyní %2$s',
    'prio_updated' => '%1$s Priorita mapy stránek je nyní %2$s',
    'enabled'   => 'povolena',
    'disabled'  => 'vypnuto',
    'uncategorized' => 'Nezařazené',
    'untitled' => 'Bez názvu',
    'xml_sitemap_error' => 'Nelze vytvořit XML mapu stránek kvůli PHP konfiguraci: \'short_open_tag\' musí být nastaveno na Vypnuto',
    'clear_cache' => 'Vyčistit cache',
);

// Localization of the Admin Configuration UI
$LANG_configsections['sitemap'] = array(
    'label' => 'Mapa stránek',
    'title' => 'Konfigurace mapy stránek',
);

$LANG_confignames['sitemap'] = array(
    'xml_filenames' => 'Název (názvy) mapy stránek',
    'view_access' => 'Uživatelé, kteří mohou zobrazit mapu stránek',
    'auto_add_plugins' => 'Automaticky přidat nové pluginy?',
    'schedule' => 'Kdy obnovit XML Mapy stránek',
);
$LANG_configsubgroups['sitemap'] = array(
    'sg_main' => 'Hlavní nastavení',
);

$LANG_fs['sitemap'] = array(
    'fs_main' => 'Hlavní nastavení mapy stránek',
);
// Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
$LANG_configSelect['sitemap'] = array(
    0 => array(1 => 'Ano', 0 => 'Ne'),
    1 => array(true => 'Ano', false => 'Ne'),
    3 => array(1 => 'Ano', 0 => 'Ne'),
    4 => array(0 => 'Bez přístupu', 1 => 'Pouze pro přihlášené', 2 => 'Všichni uživatelé'),
    5 => array(0 => 'Vždy', 1 => 'Pokud se změní obsah', 2 => 'Ručně'),
);

?>
