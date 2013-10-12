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
// include common header
$content = '';

    $manager = new Manager($_GET['season_id']);
    // show selected players;
    $sql = "SELECT MM.*, SMP.* 
		FROM manager_market MM, solo_manager_players SMP
		WHERE SMP.season_id=".$_GET['season_id']."
			AND MM.SEASON_ID=SMP.SEASON_ID
			AND SMP.USER_ID=".$_GET['user_id']."
			AND MM.USER_ID=SMP.PLAYER_ID
			AND SMP.GAME_DAY < DATE_FORMAT(NOW(), '%Y-%m-%d')
		ORDER BY SMP.GAME_DAY";
//echo $sql;
    $db->query($sql);
    $players = array();
    while ($row = $db->nextRow()) {
      $player = $row;
      if (!empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
        $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
      else $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];

      $players[] = $player;
    }

    $smarty->assign('players', $players);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/solo_manager_user_team.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/solo_manager_user_team.smarty'.($stop-$start);

  include('inc/top_very_small.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');
?>