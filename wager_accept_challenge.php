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
  if (isset($_GET['challenge_id'])) {
    $wager = new Wager();
  
    $sql = "SELECT *
		FROM wager_games WG, wager_challenges WC, games G 
		WHERE WC.USER_ID <>".$auth->getUserId()." and WC.USER2_ID is NULL
			AND WG.GAME_ID=WC.GAME_ID
			AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > NOW()
			AND WC.CHALLENGE_ID=".$_GET['challenge_id'];

    $db->query($sql);
    if ($row = $db->nextRow()) {
	// add to challenge
        unset($sdata);
        $sdata['USER2_ID'] = $auth->getUserId();
        $sdata['STATUS'] = 2;
        $sdata['DATE_ACCEPTED'] = "NOW()";
        $db->update("wager_challenges", $sdata, "CHALLENGE_ID=".$_GET['challenge_id']);
		// unfreeze credits
        $credits = new Credits();
        $credits->freezeCredits($auth->getUserId(), $row['STAKE']);

	$idata['CHALLENGE'] = 1;
	$idata['CHALLENGE_ID'] = $_GET['challenge_id'];
	$idata['SEASON_ID'] = $wager->tseason_id;
	$idata['USER_NAME'] = $auth->getUserName();
        $message['MSG'] = $langs['LANG_CHALLENGE_ACCEPTED_U'];
    } else {
        $error['MSG'] = $langs['LANG_ERROR_CHALLENGE_ACCEPT_U'];
    }
    $idata['CHALLENGE_ID'] = $_GET['challenge_id'];
    $idata['USER_CREDITS'] = $_SESSION['_user']['CREDIT'];
    $idata['FROZEN_CREDITS'] = $_SESSION['_user']['FROZEN_CREDITS'];

  }
}

  $smarty->assign("idata", $idata);
  if (isset($error))
    $smarty->assign("error", $error);
  if (isset($message))
    $smarty->assign("message", $message);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/bar_wager_challenge_accept.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/bar_wager_challenge_accept.smarty'.($stop-$start);

echo $content;
//close connections
include('class/db_close.inc.php');
?>