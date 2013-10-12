<?php
//return '';

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

// user session
include('class/ss_const.inc.php');

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/manager.inc.php');
include('class/manager_user.inc.php');

// --- build content data -----------------------------------------------------

 $content = '';

//$db->showquery=true;
  $manager = new Manager($_GET['season_id']);
  $sql = "SELECT NOW() > ".$_GET['day']." as CAN_VIEW";

  $db->query($sql);   
  $row = $db->nextRow();
  if ($row['CAN_VIEW'] == 1) {
    $sql="SELECT count(SMP.user_id) CNT, SMP.PLAYER_ID, MM.FIRST_NAME, MM.LAST_NAME, SMP.KOEFF,
		IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME, MM.POSITION_ID1, MM.POSITION_ID2
		FROM solo_manager_players SMP, 
		     manager_market MM, teams T 
			LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
		where SMP.season_id=".$_GET['season_id']."	
		and MM.user_id=SMP.player_id
		and MM.season_id=SMP.season_id
 	        AND T.TEAM_ID=MM.TEAM_ID
		and SMP.GAME_DAY = '".$_GET['day']."'
		group by SMP.player_id
		order by cnt desc ";
    $db->query($sql);   
    $players = array();
    while ($row = $db->nextRow()) {
      $player = $row;
      if (!empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
        $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
      else $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];

      $players[] = $player;
    }
 
  } else {
    $smarty->assign("noaccess", 1);
  }
  $smarty->assign("market", $players);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/solo_manager_day_stats.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/solo_manager_day_stats.smarty'.($stop-$start);


// ----------------------------------------------------------------------------
// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');

?>