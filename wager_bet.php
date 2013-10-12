<?php
/*
===============================================================================
help.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows help item
  = shows help topic index

TABLES USED: 
  - BASKET.HELP

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
include('class/wager.inc.php');
include('class/wager_user.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
//  $wager = new Wager($_GET['season_id']);
//$db->showquery = true;
  $data['WAGER_ID'] = $_POST['wager_id'];
  $credits = $_POST['credits'];
//print_r($_POST);
  if ($auth->userOn()) {
    // get wager info
    $sql="SELECT WG.*, WV.*, DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < NOW() EXPIRED,
		 S.SPORT_ID 
		from games G, seasons S, wager_games WG
		left join wager_votes WV ON WV.WAGER_ID=WG.WAGER_ID and WV.USER_ID=".$auth->getUserId()."
		WHERE WG.wager_id=".$_POST['wager_id']."
			AND G.SEASON_ID=S.SEASON_ID
			AND WG.GAME_ID=G.GAME_ID";
    $db->query($sql);    
//echo $sql;
    if ($row = $db->nextRow()) {
      $wager = new Wager($row['WSEASON_ID']);
      $wager_user = new WagerUser($wager->tseason_id);

      if ($row['EXPIRED'] == 0) {
        $status = $wager_user->makeBet($row['VOTE_ID'], $_POST['wager_id'],
				   $credits, 
				   isset($_POST['wager_'.$_POST['wager_id'].'_host_score']) ? $_POST['wager_'.$_POST['wager_id'].'_host_score'] : '',
				   isset($_POST['wager_'.$_POST['wager_id'].'_visitor_score']) ? $_POST['wager_'.$_POST['wager_id'].'_visitor_score'] : '',
				  $row['STAKE'], $row['HOST_SCORE'], $row['VISITOR_SCORE'], $row['SPORT_ID']);

//echo $status;
        // show last bet
        $game = $wager_user->getWager($_POST['wager_id']);
        $wager->setStatus($status, $game);
        $smarty->assign("game", $game);
        $smarty->assign("credits", $auth->getCredits());
      }
      else {
	$data['EXPIRED_GAME'] = 1;
      }
    }
    $smarty->assign("money", $wager_user->getMoney());
    $smarty->assign("wealth", $wager_user->getWealth());

  } else {
    $smarty->assign("money", 0);
    $smarty->assign("wealth", 0);
    $game['WAGER_ID'] = $_POST['wager_id'];
    $game['ERROR']['MSG'] = $langs['LANG_ERROR_WAGER_LOGIN_U'];
    $smarty->assign("game", $game);
  }

    $start = getmicrotime();
    $content .= $smarty->fetch('smarty_tpl/bar_wager_bet.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_wager_bet.smarty'.($stop-$start);

  echo $content;
// close connections
include('class/db_close.inc.php');
?>