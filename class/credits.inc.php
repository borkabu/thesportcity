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

class Credits {

  function Credits() {
  }

  function updateCredits ($user_id, $credits, $refresh = true) {
    global $_SESSION;
    global $db;
    global $auth;
  
    $sql = "UPDATE users SET CREDIT=CREDIT+".$credits." WHERE USER_ID=".$user_id;
    $db->query($sql);
    unset($sdata);
    if ($refresh)
      $auth->refreshEssensials();
  }

  function updateRvsLeagueCredits ($league_id, $credits) {
    global $_SESSION;
    global $db;
    global $auth;
  
    $sql = "UPDATE rvs_manager_leagues SET PRIZE_FUND=PRIZE_FUND+".$credits." WHERE LEAGUE_ID=".$league_id;
    $db->query($sql);
  }

  function updateTournamentCredits ($tournament_id, $credits) {
    global $_SESSION;
    global $db;
    global $auth;
  
    $sql = "UPDATE manager_tournament SET PRIZE_FUND=PRIZE_FUND+".$credits." WHERE MT_ID=".$tournament_id;
    $db->query($sql);
  }

  function updateClanCredits ($clan_id, $credits) {
    global $_SESSION;
    global $db;
    global $auth;
  
    $sql = "UPDATE clans SET CLAN_FUND=CLAN_FUND+".$credits." WHERE CLAN_ID=".$clan_id;
    $db->query($sql);
  }
  
  function updateCreditsByEmail ($email, $credits) {
    global $db;
  
    $sql = "SELECT USER_ID from users WHERE LOWER(EMAIL)=LOWER('".$email."')";
    $db->query($sql);
  
    if ($row=$db->nextRow()) {
      $user_id = $row['USER_ID'];
      $sql = "UPDATE users SET CREDIT=CREDIT+".$credits." WHERE USER_ID=".$user_id;
      $db->query($sql);
      unset($sdata);
    
      $credit_log = new CreditsLog();
      $credit_log->logEvent ($user_id, 2, $credits);
    }  
  }  

  function freezeCredits($user_id, $credits) {
    global $db;
    global $_SESSION;
    global $auth;

    $sql = "UPDATE users SET CREDIT=CREDIT-".$credits.", 
		FROZEN_CREDITS=FROZEN_CREDITS+".$credits." WHERE USER_ID=".$user_id;
    $db->query($sql);
    if ($user_id == $auth->getUserId()) {
      $_SESSION['_user']['CREDIT'] = $_SESSION['_user']['CREDIT'] - $credits;
      $_SESSION['_user']['FROZEN_CREDITS'] = $_SESSION['_user']['FROZEN_CREDITS'] + $credits;
    }
    $credit_log = new CreditsLog();
    $credit_log->logEvent ($user_id, 12, $credits);

  }

  function unfreezeCredits($user_id, $credits) {
    global $db;
    global $_SESSION;
    global $auth;

    $sql = "UPDATE users SET CREDIT=CREDIT+".$credits.", 
		FROZEN_CREDITS=FROZEN_CREDITS-".$credits." WHERE USER_ID=".$user_id;
    $db->query($sql);
    if ($user_id == $auth->getUserId()) {
      $_SESSION['_user']['CREDIT'] = $_SESSION['_user']['CREDIT'] + $credits;
      $_SESSION['_user']['FROZEN_CREDITS'] = $_SESSION['_user']['FROZEN_CREDITS'] - $credits;
    }
    $credit_log = new CreditsLog();
    $credit_log->logEvent ($user_id, 13, $credits);

  }

  function transferCredit($user_from, $user_to, $amount, $fee_percent, $occasion, $fee_payer=true) {
// $ocasion 3 - transfer, 14 - challenge
    global $db;

    if ($fee_payer) {
      $sender_ammount = round($amount*(100+$fee_percent)/100, 2);
      $receiver_ammount = $amount;
    }
    else {
      $sender_ammount = $amount;
      $receiver_ammount = round($amount*(100-$fee_percent)/100, 2);
    }
    $db->query("start transaction");
    $credit_log = new CreditsLog();
    $this->updateCredits($user_to, $receiver_ammount); 
    $credit_log->logEvent ($user_to, $occasion, $receiver_ammount, $user_from);
    $this->updateCredits($user_from, -1*$sender_ammount, 2);
    $credit_log->logEvent ($user_from, $occasion+1, $sender_ammount, $user_to);
    $db->query("commit");
    return $receiver_ammount;
  }

  function transferCreditsClan($user_from, $clan_id, $amount) {
    
    $credit_log = new CreditsLog();
    $this->updateClanCredits($clan_id, $amount); 
//    $credit_log->logEvent ($user_to, $occasion, $receiver_ammount, $user_from);
    $this->updateCredits($user_from, -1*$amount, 2);
    $credit_log->logEvent ($user_from, 26, $amount);

    $clan_log = new ClanLog();
    $clan_log->logEvent ($clan_id, 6, $amount, $user_from, '');
    return $amount;
  }

  function transferCreditsRvsLeague($user_from, $league_id, $season_id, $amount) {
    
    $credit_log = new CreditsLog();
    $this->updateRvsLeagueCredits($league_id, $amount); 
//    $credit_log->logEvent ($user_to, $occasion, $receiver_ammount, $user_from);
    $this->updateCredits($user_from, -1*$amount, 2);
    $credit_log->logEvent ($user_from, 28, $amount);

    $rvs_league_log = new RvsManagerLog();
    $rvs_league_log->logEvent ($user_from, 13, $season_id, $league_id, '', '', '', '' , '' , $amount);
    return $amount;
  }

  function getCreditsBox() {
   global $smarty;
   global $auth;
   global $pagebox;

    $page_content = $pagebox->getPage(11);
    $smarty->assign("page", $page_content);
    if ($auth->userOn())
      $smarty->assign("user_id", $auth->getUserId());    

    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/get_credits.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/get_credits.smarty'.($stop-$start);
 
    return  $content;
  }     

}

?>