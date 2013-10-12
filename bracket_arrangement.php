<?php
/*
===============================================================================
toto.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows totalizator result archive

TABLES USED: 
  - BASKET.totalizators
  - BASKET.totalizator_votes
  - BASKET.users
  - BASKET.games

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
include('class/update.inc.php');

// classes
include('class/db.class.php');
include('class/template.class.php');
include('class/language.class.php');
include('class/form.class.php');

// connections
include('class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/bracket.inc.php');
include('class/bracket_user.inc.php');

// --- build content data -----------------------------------------------------
$content = '';
if (isset($_GET['user_id'])) {
//$db->showquery=true;
  $bracket = new Bracket();

  $arranger_race_filter_box = inputBracketRaces("prev_race_id", $bracket->tseason_id, 0);
  $allow_all = false;
  $db->select("bracket_seasons", "*", "START_DATE < NOW() AND END_DATE > NOW()");
  if (!$row = $db->nextRow()) {
    $allow_all = true;
  }

  if (isset($_POST['prev_race_id']) && ($allow_all || $auth->hasSupporter())) {
       $prev_race_id = $_POST['prev_race_id'];
  } else {
       $prev_race_id = $bracket->getPrevRaceID();
       if ($prev_race_id == 0) 
         $next_race_id = $bracket->getNextRaceID();
  }

  $sql = "SELECT U.USER_NAME, BU.ALLOW_VIEW FROM users U, bracket_users BU
		WHERE U.USER_ID=BU.USER_ID AND U.USER_ID=".$_GET['user_id'];
  $db->query($sql);
  $row = $db->nextRow();
  $user_name=$row['USER_NAME'];
  $allow_view = ($row['ALLOW_VIEW'] == 1);

  if ($prev_race_id>0) {  
    // get latest tour
   //verify that it is in the past;
   $sql = "SELECT * FROM games_races G, bracket_tours BT
			WHERE G.GAME_ID=".$prev_race_id."
				AND BT.START_DATE < G.START_DATE
				AND BT.END_DATE > G.START_DATE
				AND BT.START_DATE < NOW()";
   $db->query($sql);
   if ($row = $db->nextRow()) {

     $sql="SELECT DISTINCT MM.LAST_NAME, MM.FIRST_NAME, MT.PLACE
          FROM bracket_arrangements MT, busers MM 
         WHERE MT.USER_ID=".$_GET['user_id']." 
               AND MT.SEASON_ID=".$bracket->tseason_id."
	       AND MT.PILOT_ID = MM.USER_ID 
	       AND MT.SEASON_ID=".$bracket->tseason_id."
	       AND MT.GAME_ID=".$prev_race_id."
         ORDER BY MT.PLACE ASC";
      $db->query($sql);
  
      $c = 0;
      $players = array();
      while ($row = $db->nextRow()) {
         $player = $row;
         $player['NUMBER'] = $c+1;
  
         $c++;
         $players[]=$player;
       }
      $db->free();
  
      $smarty->assign("players", $players);
    }
  }

  $smarty->assign("user_name", $user_name);
  if ($allow_all || $auth->hasSupporter() || $allow_view) {
      $smarty->assign("can_view", 1);
  }
  $smarty->assign("arranger_race_filter_box", $arranger_race_filter_box);
}

$start = getmicrotime();
$content = $smarty->fetch('smarty_tpl/bracket_arrangement.smarty');    
$stop = getmicrotime();
if (isset($_GET['debugphp']))
  echo 'smarty_tpl/bracket_arrangement.smarty'.($stop-$start);

// ----------------------------------------------------------------------------

// content
// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

include('inc/bot_small.inc.php');
// close connections
include('class/db_close.inc.php');

?>