<?php
ini_set('display_errors', 1);
error_reporting (E_ALL & ~E_NOTICE);

//includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
include('class/update.inc.php');

//classes
include('class/db.class.php');
include('class/template.class.php');
include('class/language.class.php');
include('class/form.class.php');

//connections
include('class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

include('include.php');
include('class/user_session.inc.php');
//http header
include('class/headers.inc.php');
//page requirements
include('class/inputs.inc.php');
include('class/wager.inc.php');
include('class/wager_user.inc.php');
//--- build content data -----------------------------------------------------
//----------------------------------------------------------------------------

//include common header
$content = '';

if (!$auth->userOn()) {
   $error['MSG'] = $langs['LANG_ERROR_NOT_LOGED_IN_U'];
}
else {
  if (isset($_GET['game_id'])) {
    $wager = new Wager();
  
    $sql = "SELECT WG.GAME_ID, WC.CHALLENGE_ID, WC.STAKE,
                IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2
	    FROM wager_games WG, wager_challenges WC, seasons S, games G
	              left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
			LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
	              left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
	    WHERE WC.USER_ID =".$auth->getUserId()." and WC.USER2_ID is NULL
		AND WG.GAME_ID=WC.GAME_ID
	        AND WC.GAME_ID=G.GAME_ID
    	        AND S.SEASON_ID=G.SEASON_ID
        	AND WG.GAME_ID=".$_GET['game_id'];
//echo $sql;
    $db->query($sql);
    if ($row = $db->nextRow()) {
	$idata = $row;
	// add to challenge
        $db->delete("wager_challenges", "CHALLENGE_ID=".$row['CHALLENGE_ID']);
		// unfreeze credits
        $credits = new Credits();
        $credits->unfreezeCredits($auth->getUserId(), $row['STAKE']);

	$idata['CHALLENGE'] = 1;
	$idata['SEASON_ID'] = $wager->tseason_id;
    } else {
        $error['MSG'] = $langs['LANG_ERROR_CHALLENGE_REMOVE_U'];
    }
    $idata['GAME_ID'] = $_GET['game_id'];
    $idata['USER_CREDITS'] = $_SESSION['_user']['CREDIT'];
    $idata['FROZEN_CREDITS'] = $_SESSION['_user']['FROZEN_CREDITS'];

  }
}

  $smarty->assign("idata", $idata);
  if (isset($error))
    $smarty->assign("error", $error);
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/bar_wager_challenge_withdraw.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/bar_wager_challenge_withdraw.smarty'.($stop-$start);

echo $content;
//close connections
include('class/db_close.inc.php');
?>