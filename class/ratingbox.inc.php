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

class RatingBox extends Box{

  function RatingBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getRatingBox() {
    global $db;
    global $smarty;

    $smarty->setCaching(3600);
    $rating = '';
    if (!$smarty->isCached('smarty_tpl/bar_manager_rating.smarty', 'bar_manager_rating'."_lang_id".$_SESSION['lang_id'])) {

      $sql="SELECT U.USER_NAME, U.USER_ID, MS.POINTS as KOEFF, MS.PLACE
	          FROM users U, manager_ratings MS 
                   WHERE MS.USER_ID = U.USER_ID 
			AND MS.SPORT_ID=0
			AND MS.TOURNAMENT_ID=0
          ORDER BY MS.PLACE ASC, U.USER_NAME 
	  LIMIT 3";
//echo $sql;
      $db->query($sql);
      while ($row = $db->nextRow()) {
        $rating['USERS'][] = $row;
      }
      $smarty->assign("rating", $rating);
    }
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_manager_rating.smarty', 'bar_manager_rating'."_lang_id".$_SESSION['lang_id']);
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_rating.smarty'.($stop-$start);
    $smarty->caching= false;
    return $output;

  }
   
}   
?>