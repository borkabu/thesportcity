<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
index.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - first page of a portal

TABLES USED: 
  - BASKET.NEWS
  - BASKET.EVENTS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
// includes
include('class/conf.inc.php');
include('class/func.inc.php');
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


$content = '';
if ($auth->userOn()) {
  $submenu = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 16);

  $credits = new Credits();
  $auth->refreshEssensials();

  if (isset($_POST['transfer']) && $_POST['transfer'] == 'y' && isset($_POST['user_name']) && isset($_POST['credits'])) {
    if (($_SESSION['_user']['CREDIT']>=$_POST['credits'] + $_POST['credits']*5/100 && $_POST['fee_payer'] == 'Y') ||
	($_SESSION['_user']['CREDIT']>=$_POST['credits'] && $_POST['fee_payer'] != 'Y')) {
      $db->select('users', "USER_ID, CREDIT", "USER_NAME='".$_POST['user_name']."'");
    
      if ($row = $db->nextRow()) {
        if ($row['USER_ID'] != $auth->getUserId()) {
          if ($_POST['fee_payer'] == 'Y') {
  	    $receiver_amount = $credits->transferCredit($auth->getUserId(), $row['USER_ID'], $_POST['credits'], 5, 3, true); //sender
            $transfer['SUCCESS']['MSG'] = str_replace("%u", $_POST['user_name'], $langs['LANG_CREDITS_TRANSFERED_U']); 
            $transfer['SUCCESS']['MSG'] = str_replace("%c", $receiver_amount, $transfer['SUCCESS']['MSG']);  
          } else {
  	    $receiver_amount = $credits->transferCredit($auth->getUserId(), $row['USER_ID'], $_POST['credits'], 5, 16, false); //receiver
            $transfer['SUCCESS']['MSG'] = str_replace("%u", $_POST['user_name'], $langs['LANG_CREDITS_TRANSFERED_U']); 
            $transfer['SUCCESS']['MSG'] = str_replace("%c", $receiver_amount, $transfer['SUCCESS']['MSG']);  
          }
        } else $transfer['ERROR']['MSG'] = $langs['LANG_ERROR_NO_CREDITS_YOURSELF_U'];
      } 
      else $transfer['ERROR']['MSG'] = str_replace("%u", $_POST['user_name'], $langs['LANG_ERROR_USER_NOT_FOUND_U']); 
    } 
    else $transfer['ERROR']['MSG'] = $langs['LANG_ERROR_NOT_ENOUGH_CREDITS_U'];
  }

  $clan_id = $auth->isClanMember();
  if (isset($_POST['transfer_clan']) && $_POST['transfer_clan'] == 'y' && isset($_POST['credits']) && $clan_id > 0) {
    if ($_SESSION['_user']['CREDIT']>=$_POST['credits']) {
  	$amount = $credits->transferCreditsClan($auth->getUserId(), $clan_id, $_POST['credits'], 0); //sender
        $transfer_clan['SUCCESS']['MSG'] = str_replace("%c", $amount, $langs['LANG_CREDITS_TRANSFERED_CLAN_U']);  
    } 
    else $transfer_clan['ERROR']['MSG'] = $langs['LANG_ERROR_NOT_ENOUGH_CREDITS_U'];
  }


  if (isset($_POST['refund']) && $_POST['refund'] == 'y' && isset($_POST['season_id'])) {
      $db->select('manager_users', "if (REFUNDABLE < TRANSACTIONS, REFUNDABLE, TRANSACTIONS) as REFUNDABLE", "SEASON_ID=".$_POST['season_id']." AND user_id=".$auth->getUserId());   
      if ($row = $db->nextRow()) {
        if ($row['REFUNDABLE'] > 0) {
          $credit_log = new CreditsLog();
          $credits->updateCredits($auth->getUserId(), $row['REFUNDABLE']/3);
          $credit_log->logEvent ($auth->getUserId(), 7, $row['REFUNDABLE']/3);
          unset($sdata);
          $sdata['TRANSACTIONS'] = 'TRANSACTIONS-'.$row['REFUNDABLE'];
          $sdata['REFUNDABLE'] = 0;
	  $db->update('manager_users', $sdata, "SEASON_ID=".$_POST['season_id']." AND user_id=".$auth->getUserId());
        } 
      } 
  }

  if (isset($_POST['unfreeze']) && $_POST['unfreeze'] == 'y') {
    $db->query("start_transaction");
    $sql="select subt.USER_ID, sum(subt.stakes) as TOTAL from
	(SELECT user_id, sum(stake) as stakes  FROM `manager_challenges` WHERE `STATUS` = 1 AND `TYPE` = 2
               and user_id=".$auth->getUserId()."
	group by user_id
	union all
	SELECT user_id, sum(stake) as stakes  FROM `manager_challenges` WHERE `STATUS` = 2 AND `TYPE` = 2
               and user_id=".$auth->getUserId()."
	group by user_id
	union all
	SELECT user2_id as user_id, sum(stake) as stakes FROM `manager_challenges` WHERE `STATUS` = 2 AND `TYPE` = 2
               and user2_id=".$auth->getUserId()."
	group by user2_id
	union all
	SELECT user_id, sum(stake) as stakes  FROM `wager_challenges` WHERE `STATUS` = 1 
               and user_id=".$auth->getUserId()."
	group by user_id
	union all
	SELECT user_id, sum(stake) as stakes  FROM `wager_challenges` WHERE `STATUS` = 2 
               and user_id=".$auth->getUserId()."
	group by user_id
	union all
	SELECT user2_id as user_id, sum(stake) as stakes FROM `wager_challenges` WHERE `STATUS` = 2 
               and user2_id=".$auth->getUserId()."
	group by user2_id
	) subt
	group by user_id";
    $db->query($sql);  
//echo $sql;
//echo $_SESSION['_user']['FROZEN_CREDITS'];
    if ($row = $db->nextRow()) {
//echo ".".$row['TOTAL'];
      if ($_SESSION['_user']['FROZEN_CREDITS'] > $row['TOTAL'])
        $credits->unfreezeCredits($auth->getUserId(), $_SESSION['_user']['FROZEN_CREDITS'] - $row['TOTAL']);
    } else if ($_SESSION['_user']['FROZEN_CREDITS'] > 0) {
      $credits->unfreezeCredits($auth->getUserId(), $_SESSION['_user']['FROZEN_CREDITS']);
    }
    $db->query("commit");
  }

  $logbox = new LogBox($langs, $_SESSION["_lang"]);
  $perpage = 35;
  $credit_log = $logbox->getCreditLogBox($auth->getUserId(), isset($_GET['page']) ? $_GET['page'] : 1, $perpage);
  $credit_log_paging = $pagingbox->getPagingBox($logbox->getRows(), isset($_GET['page']) ? $_GET['page'] : 1);

  if ($_SESSION['_user']['CREDIT']>0) {
    $PRESET_VARS['fee_payer'] = 'Y';
    $transfer['ALLOW_TRANSFER']['SENDER'] = $frm->getInputWithValue(FORM_INPUT_RADIO, 'fee_payer', 
                                      array('value_force' => 'Y', 'class' => 'input'));
    $transfer['ALLOW_TRANSFER']['RECEIVER'] = $frm->getInputWithValue(FORM_INPUT_RADIO, 'fee_payer', 
                                    array('value_force' => 'N', 'class' => 'input'));

    $transfer_clan['ALLOW_TRANSFER'] = 1;

  } else {
    $transfer['DENY_TRANSFER']['MSG'] = $langs['LANG_ERROR_NOT_ENOUGH_CREDITS_U'];
    $transfer_clan['DENY_TRANSFER']['MSG'] = $langs['LANG_ERROR_NOT_ENOUGH_CREDITS_U'];
  }


  // refunds
  $sql= "SELECT MSD.SEASON_ID, MSD.SEASON_TITLE, 
		if (MU.REFUNDABLE < MU.TRANSACTIONS, MU.REFUNDABLE,MU.TRANSACTIONS) as REFUNDABLE
		 FROM manager_users MU, manager_seasons_details MSD 
		WHERE MU.USER_ID=".$auth->getUserId()."
		 AND MU.REFUNDABLE > 0
		AND MSD.SEASON_ID=MU.SEASON_ID
		AND MSD.LANG_ID=".$_SESSION['lang_id'];
  $db->query($sql);  
  $refunds = array();
  while ($row = $db->nextRow()) {
    $refunds[] = $row;
  }

// frozen credits

  $sql="select subt.USER_ID, sum(subt.stakes) as TOTAL, u.FROZEN_CREDITS from
	(SELECT user_id, sum(stake) as stakes  FROM `manager_challenges` WHERE `STATUS` = 1 AND `TYPE` = 2
               and user_id=".$auth->getUserId()."
	group by user_id
	union all
	SELECT user_id, sum(stake) as stakes  FROM `manager_challenges` WHERE `STATUS` = 2 AND `TYPE` = 2
               and user_id=".$auth->getUserId()."
	group by user_id
	union all
	SELECT user2_id as user_id, sum(stake) as stakes FROM `manager_challenges` WHERE `STATUS` = 2 AND `TYPE` = 2
               and user2_id=".$auth->getUserId()."
	group by user2_id
	union all
	SELECT user_id, sum(stake) as stakes  FROM `wager_challenges` WHERE `STATUS` = 1 
               and user_id=".$auth->getUserId()."
	group by user_id
	union all
	SELECT user_id, sum(stake) as stakes  FROM `wager_challenges` WHERE `STATUS` = 2 
               and user_id=".$auth->getUserId()."
	group by user_id
	union all
	SELECT user2_id as user_id, sum(stake) as stakes FROM `wager_challenges` WHERE `STATUS` = 2 
               and user2_id=".$auth->getUserId()."
	group by user2_id
	) subt, users u
	where subt.user_id=u.user_id 
	group by user_id";
//echo $sql;
  $db->query($sql);  
  $frozen_refund = "";
  if ($row = $db->nextRow()) {
    if ($row['TOTAL'] <> $row['FROZEN_CREDITS']) {
      $frozen_refund = $row;
      $smarty->assign("frozen_refund", $frozen_refund);
    }
  } else if ($_SESSION['_user']['FROZEN_CREDITS'] > 0) {
    $frozen_refund['TOTAL'] = 0;
    $frozen_refund['FROZEN_CREDITS'] = $_SESSION['_user']['FROZEN_CREDITS'];
    $smarty->assign("frozen_refund", $frozen_refund);
  }

  $sql="select sum(subt.stakes) as TOTAL, subt.TAG from
	(SELECT user_id, sum(stake) as stakes, 'MANAGER_CHALLENGES' as tag FROM `manager_challenges` WHERE `STATUS` = 1 AND `TYPE` = 2
               and user_id=".$auth->getUserId()."
	group by user_id
	union all
	SELECT user_id, sum(stake) as stakes, 'MANAGER_CHALLENGES' as tag FROM `manager_challenges` WHERE `STATUS` = 2 AND `TYPE` = 2
               and user_id=".$auth->getUserId()."
	group by user_id
	union all
	SELECT user2_id as user_id, sum(stake) as stakes, 'MANAGER_CHALLENGES' as tag FROM `manager_challenges` WHERE `STATUS` = 2 AND `TYPE` = 2
               and user2_id=".$auth->getUserId()."
	group by user2_id
	union all
	SELECT user_id, sum(stake) as stakes, 'WAGER_CHALLENGES' as tag FROM `wager_challenges` WHERE `STATUS` = 1 
               and user_id=".$auth->getUserId()."
	group by user_id
	union all
	SELECT user_id, sum(stake) as stakes, 'WAGER_CHALLENGES' as tag FROM `wager_challenges` WHERE `STATUS` = 2 
               and user_id=".$auth->getUserId()."
	group by user_id
	union all
	SELECT user2_id as user_id, sum(stake) as stakes, 'WAGER_CHALLENGES' as tag FROM `wager_challenges` WHERE `STATUS` = 2 
               and user2_id=".$auth->getUserId()."
	group by user2_id) subt
	group by subt.tag";
//echo $sql;
  $db->query($sql);  
  $real_frozen_credits = array();
  while ($row = $db->nextRow()) {
    $real_frozen_credits[] = $row;
  }

  if ($clan_id > 0) {
    $smarty->assign("clan_member", true);
    $smarty->assign("transfer_clan",  $transfer_clan);
  }

  $smarty->assign("real_frozen_credits", $real_frozen_credits);
  $smarty->assign("frozen_credits",  $_SESSION['_user']['FROZEN_CREDITS']);
  $smarty->assign("get_credits",  $credits->getCreditsBox());
  $smarty->assign("transfer",  $transfer);
  $smarty->assign("refunds",  $refunds);
  $smarty->assign("credit_log",  $credit_log);
  $smarty->assign("credit_log_paging",  $credit_log_paging);
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/user_credits_manager.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/user_credits_manager.smarty'.($stop-$start);

}
else {
  $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
  $errorbox1 = $errorbox->getErrorBox('ERROR_NOT_LOGGED_IN');
}

  // include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>