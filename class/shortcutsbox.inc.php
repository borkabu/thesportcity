<?php
/*
===============================================================================
events.inc.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - generate event list data

TABLES USED: 
  - BASKET.EVENTS

STATUS:
  - [STAT:INPRGS] in progress

TODO:
  - [TODO:PUBVAR] main functionality
===============================================================================
*/

class ShortcutsBox extends Box{

  function ShortcutsBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getShortcutsBox() {
    global $db;
    global $_SESSION;
    global $smarty;
    global $clients;

    $sports =  "";
    if (isset($_SESSION['external_user']) && isset($clients[$_SESSION['external_user']['SOURCE']]['sports']))
      $sports = " AND MSS.SPORT_ID IN (".$clients[$_SESSION['external_user']['SOURCE']]['sports'].")";
    $smarty->setCaching(Smarty::CACHING_LIFETIME_SAVED);
    $smarty->setCacheLifetime(1000);
    if (!$smarty->isCached('smarty_tpl/bar_shortcuts.smarty', 'bar_shortcuts'."_lang_id".$_SESSION['lang_id'].$sports)) {
         $sql = "SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, 'MANAGER' as TYPE, MSS.ALLOW_RVS_LEAGUES, MSS.ALLOW_SOLO
		           FROM manager_seasons MSS
			left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
		  WHERE MSS.START_DATE < NOW() 
			AND DATE_ADD( MSS.END_DATE, INTERVAL 6 DAY ) > NOW()
			AND MSS.PUBLISH='Y'".$sports;

          $db->query($sql);     
          $mshortcuts = array();
          while ($row = $db->nextRow()) {
            $mshortcuts[] = $row;   
          }

         $sql = "SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE as SEASON_TITLE, 'WAGER' as TYPE
	           FROM wager_seasons MSS
			left JOIN wager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
	           WHERE MSS.START_DATE < NOW( )
			AND DATE_ADD( MSS.END_DATE, INTERVAL 6 DAY ) > NOW()	
			AND MSS.PUBLISH='Y'

		UNION 

		  SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE as SEASON_TITLE, 'ARRANGER' as TYPE
	           FROM bracket_seasons MSS
			left JOIN bracket_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
	           WHERE MSS.START_DATE < NOW( )
			AND DATE_ADD( MSS.END_DATE, INTERVAL 6 DAY ) > NOW()	
			AND MSS.PUBLISH='Y'
		";
          $db->query($sql);     
          $shortcuts = array();
          while ($row = $db->nextRow()) {
            $shortcuts[$row['TYPE']][] = $row;   
          }
         if (count($shortcuts) > 0)
           $smarty->assign("shortcuts", $shortcuts);
         if (count($mshortcuts) > 0)
           $smarty->assign("mshortcuts", $mshortcuts);

    }
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_shortcuts.smarty', 'bar_shortcuts'."_lang_id".$_SESSION['lang_id'].$sports);
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_shortcuts.smarty'.($stop-$start);
    $smarty->caching= false;
    return $output;

  }
}   
?>