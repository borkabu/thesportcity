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

// include common header
$content = '';

  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 6);

  $wager = new Wager(isset($_GET['league_id']) ? $_GET['league_id'] : "");
  $wagerbox = new WagerBox($langs, $_SESSION["_lang"]);
  if ($auth->userOn())
    $wager_user = new WagerUser($wager->tseason_id);

  $wager_filter_box = $wagerbox->getWagerFilterBox($wager->getSeason());

 $has_league = false;
 $league_id = -1;

 $can_invite = false;
 $can_update = false;
 $can_update2 = false;
 $can_delete = false;
 $can_force = false;
 $can_remove = false;

//$db->showquery=true;
if ($auth->userOn()) {
  if (isset($_GET['league_id']))
    $where = " AND ML.LEAGUE_ID=".$_GET['league_id'];
  else $where = " AND ML.STATUS in (1, 2) ";

  $sql= "SELECT ML.LEAGUE_ID
          FROM wager_leagues ML
         WHERE ML.USER_ID=".$auth->getUserId()." 
               and ML.STATUS in (1,2)
		".$where."
               AND ML.SEASON_ID=".$wager->tseason_id; 
 $db->query($sql); 
 if ($row = $db->nextRow()) {
//   $has_league = true;
   $league_id = $row['LEAGUE_ID'];

   $wleague = new League("wager", $league_id);
   $wleague->getLeagueInfo();
   $can_invite = false;
   $can_update = false;
   $can_update2 = false;
   $can_delete = false;
   $can_force = false;
   $can_remove = false;
  
   if ($wleague->league_info['JOINED'] == 1) {
     $can_update = true;
     $can_delete = true;
   }

   if (($wleague->league_info['PARTICIPANTS']==0 || $wleague->league_info['PARTICIPANTS'] > $wleague->league_info['JOINED']) 
		&& $wleague->league_info['JOINED'] >= 2
                && $wleague->league_info['STATUS'] == 1)
     $can_force = 1;
  
   if ($wleague->league_info['STATUS']==1 && ($wleague->league_info['PARTICIPANTS']==0 || $wleague->league_info['PARTICIPANTS'] > $wleague->league_info['JOINED'])) {
     $can_invite = 1;
     $can_remove = 1;
     $can_update2 = 1;
   }
 }

   $sql= "SELECT ML.*
            FROM wager_leagues ML
           WHERE ML.USER_ID=".$auth->getUserId()." 
                AND ML.SEASON_ID = ".$wager->tseason_id; 
   $db->query($sql); 
   $past_leagues = array();
   while ($row = $db->nextRow()) {
     $all_leagues[] = $row; 
   }

   if (count($all_leagues) > 0)
     $smarty->assign("all_leagues", $all_leagues);

}

if ($auth->userOn() && isset($_POST['set_start_date'])) {
    unset($udata);
    $udata['START_DATE'] = "'".$_POST['start_date']."'";
    $db->update('wager_leagues', $udata, 'LEAGUE_ID='.$league_id);  
    $wager->generateTours($league_id, $wleague->league_info['DURATION'], $wleague->league_info['TOUR_DURATION'], $_POST['start_date']);
}
if ($auth->userOn() && isset($_POST['remove_user']) && isset($_POST['user_id']) && isset($_POST['league_id'])) {
  $udata['STATUS'] = 4;  
  $udata['END_DATE'] = "NOW()";
  $db->update('wager_leagues_members', $udata, 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$_POST['league_id']);  
  unset($udata);
}
else if ($auth->userOn() && isset($_POST['remove_user2']) && isset($_POST['user_id']) && isset($_POST['league_id'])) {
  $db->delete('wager_leagues_members', 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$_POST['league_id']);  
  unset($udata);
}
else if ($auth->userOn() && isset($_POST['tag_user']) && isset($_POST['user_id']) && isset($_POST['tag']) && isset($_POST['league_id'])) {
  $udata['TAG'] = "'".$_POST['tag']."'";  
  $db->update('wager_leagues_members', $udata, 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$_POST['league_id']);  
  unset($udata);
}
else if ($auth->userOn() && $league_id>0 && isset($_POST['force_start'])) { 
  unset($udata);
  $udata['INVITE_TYPE'] = 0;
  $udata['RECRUITMENT_ACTIVE'] = "'N'";
  $udata['PARTICIPANTS'] = "JOINED";
  $udata['STATUS'] = 2;
  $db->update('wager_leagues', $udata, 'LEAGUE_ID='.$league_id);  
  $db->delete('wager_leagues_members', 'STATUS=3 AND LEAGUE_ID='.$league_id);  
  unset($udata);
  $can_force = false;
}
else if ($auth->userOn() && isset($_POST['set_rules']) && isset($_POST['league_id'])) {

  if ($_POST['rules'] == "" && isset($_POST['simple_text']))
    $_POST['rules'] = $_POST['simple_text'];

  $s_fields = array('rules');
  $d_fields = '';
  $c_fields = array('real_prizes', 'recruitment_active');

  $error = FALSE;
  // check for password matching

  if ($can_update) {
    $i_fields = array('entry_fee', 'country', 'participants', 'tour_duration', 'invite_type', 'duration', 'point_type');
    if ($_POST['entry_fee'] < 0) {
      $error = TRUE;
      $conf_error['ERROR_RSV_LEAGUE_ENTRY_FEE'] = 1;
    }
  }

  if (!$can_update && $can_update2) {
    $i_fields = array('country', 'participants', 'invite_type');
    if ($_POST['entry_fee'] < 0) {
      $error = TRUE;
      $conf_error['ERROR_RSV_LEAGUE_ENTRY_FEE'] = 1;
    }
  }

  if (isset($conf_error))
    $smarty->assign("conf_error", $conf_error);
  if (!$error) {
    $udata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);

    $db->update('wager_leagues', $udata, 'LEAGUE_ID='.$_POST['league_id']);  
    unset($udata);
//exit;
  }

}

if ($auth->userOn() && isset($_POST['create_league']) && isset($_POST['title']) && !empty($_POST['title'])) {
   $sql= "SELECT ML.LEAGUE_ID
          FROM wager_leagues ML, wager_leagues_members MLM
         WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
	       AND MLM.USER_ID=".$auth->getUserId()." 
               AND ML.SEASON_ID=".$wager->tseason_id." 
	       AND ML.STATUS in (1,2)
               AND MLM.STATUS=1"; 
   $db->query($sql); 
   if (!$row = $db->nextRow()) {    
     $sdata['SEASON_ID'] = $wager->tseason_id;
     $sdata['STATUS'] = 1;
     $sdata['TITLE'] = "'".$_POST['title']."'";
     $sdata['USER_ID'] = $auth->getUserId();
     $db->insert('wager_leagues', $sdata);
     $can_update = true;
     unset($sdata);
     $db->select("wager_leagues", "LEAGUE_ID", "USER_ID=".$auth->getUserId()." AND SEASON_ID=".$wager->tseason_id." AND STATUS=1");   
     if ($row = $db->nextRow()) {
       $sdata['LEAGUE_ID'] = $row['LEAGUE_ID'];
       $sdata['USER_ID'] = $auth->getUserId();
       $sdata['START_DATE'] = "NOW()";
       $sdata['STATUS'] = 1;
       $db->insert('wager_leagues_members', $sdata);     
       unset($sdata);
     }
   }
}

if ($auth->userOn() && isset($_POST['invite']) && isset($_POST['user_name']) && isset($_POST['league_id']) && isset($_POST['season_id'])) {
   $sql = "SELECT U.USER_ID, MU.USER_ID ISIN, MU.IGNORE_LEAGUES 
             FROM users U LEFT JOIN wager_users MU ON U.USER_ID=MU.USER_ID AND MU.SEASON_ID=".$_POST['season_id']."
            WHERE USER_NAME='".$_POST['user_name']."'";
  
   $db->query($sql);
   if ($row = $db->nextRow()) {
     if ($row['IGNORE_LEAGUES'] == 'Y') {
       $invite['INVITE_ERROR']['USERIGNORE'] = 1;
     } 
     else if (isset($row['ISIN'])) {
       $sdata['LEAGUE_ID'] = $_POST['league_id'];
       $sdata['USER_ID'] = $row['USER_ID'];
       $sdata['STATUS'] = 3;
       // check that it is not already there
       $db->select("wager_leagues_members", "USER_ID", "STATUS IN (2, 3) AND USER_ID='".$sdata['USER_ID']."' AND LEAGUE_ID=".$sdata['LEAGUE_ID']);       
       if ($row = $db->nextRow()) {
         $invite['INVITE_ERROR']['USERDOUBLE'] = 1;
       } 
       else {
         $db->select("wager_leagues_members", "USER_ID", "STATUS IN (4, 5) AND USER_ID='".$sdata['USER_ID']."' AND LEAGUE_ID=".$sdata['LEAGUE_ID']);       
         if ($row = $db->nextRow()) {
           $db->update('wager_leagues_members', "STATUS=3", 'USER_ID='.$sdata['USER_ID'].' AND LEAGUE_ID='.$sdata['LEAGUE_ID']);
         }
         else $db->insert('wager_leagues_members', $sdata);     
         unset($sdata);
       }
     }
     else {
      $invite['INVITE_ERROR']['NOACCOUNT'] = 1;
     }
   }
   else {
      $invite['INVITE_ERROR']['NOUSER'] = 1;
   }
}


$has_league = false;
if ($auth->userOn()) {
// initialize user team

  if (isset($_GET['league_id']))
    $where = " AND ML.LEAGUE_ID=".$_GET['league_id'];
  else $where = " AND ML.STATUS in (1, 2) ";
  $sql= "SELECT ML.*, ML.START_DATE as LEAGUE_START_DATE, 
		U.USER_ID, WS.END_DATE, WS.START_DATE, U.USER_NAME,
		DATEDIFF(WS.END_DATE, WS.START_DATE) SEASON_LENGTH, DATEDIFF(WS.END_DATE, NOW()) DAYS_LEFT
          FROM wager_leagues ML, wager_leagues_members MLM,
               users U, wager_seasons WS
         WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
	       AND MLM.USER_ID=".$auth->getUserId()." 
	       AND MLM.USER_ID=U.USER_ID
               AND ML.SEASON_ID=".$wager->tseason_id." 
               AND WS.SEASON_ID=ML.SEASON_ID
               AND MLM.STATUS=1 ".$where; 
  $db->query($sql); 
  if (!$row = $db->nextRow()) {
    $create_league_offer = 1;
  }
  else $has_league = true;


if ($has_league) {
  $league  = $row;
//print_r($league);
  if ($row['TOUR_DURATION'] == 0)
    $row['TOUR_DURATION'] = 7;
  if ($row['SEASON_LENGTH'] > $row['DAYS_LEFT'])
    $max_duration = ceil($row['DAYS_LEFT']/$row['TOUR_DURATION']);
  else 
    $max_duration = ceil($row['SEASON_LENGTH']/$row['TOUR_DURATION']);

  if ($row['SEASON_LENGTH'] > $row['DAYS_LEFT'])
    $league['LEAGUE_LENGTH'] = $row['DAYS_LEFT'];
  else $league['LEAGUE_LENGTH'] = $row['SEASON_LENGTH'];

  if ($row['DAYS_LEFT'] > 30)
    $max_tour_duration = 30;
  else $max_tour_duration = $row['DAYS_LEFT'];

  $league['MAX_DURATION'] = $max_duration;
  $league['MAX_TOUR_DURATION'] = $max_tour_duration;
  $league['OWNER'] = $row['USER_NAME'];
  $league['DESCR'] = $row['RULES'];
  $league_id=$row['LEAGUE_ID'];
  $owner = true;
  $db->free();

  if (!$can_update)
    $league['READONLY'] = 1;

  $league['COUNTRY'] = inputCountries('country', $row['COUNTRY']);
  $league['INVITE_TYPE'] = inputLeagueInviteTypes('invite_type', $row['INVITE_TYPE']);
  if ($row['INVITE_TYPE'] == 1 && empty($row['INVITE_CODE'])) {
    unset($sdata);
    $sdata['INVITE_CODE'] = "'".gen_rand_string(0, 8)."'";
    $db->update("wager_leagues", $sdata, "LEAGUE_ID=".$row['LEAGUE_ID']);
    $league['INVITE_CODE'] = $sdata['INVITE_CODE'];
  } else if ($row['INVITE_TYPE'] == 1 && !empty($row['INVITE_CODE'])) {
    $league['INVITE_CODE'] = $row['INVITE_CODE'];
  } else {
    unset($league['INVITE_CODE']);
  }

  $league['POINT_TYPE'] = inputWagerLeaguePointTypes('point_type', $row['POINT_TYPE']);
  $league['POINT_TYPE_DESCR'] = $wager_league_point_types[$row['POINT_TYPE']];
  $league['DATE']['UTC'] = $auth->getUserTimezoneName();

  // get members
  $sql= "SELECT MLM.*, U.USER_NAME, MS.WEALTH, MS.PLACE, 0 as POINTS
          FROM wager_leagues_members MLM, users U
               LEFT JOIN wager_standings MS ON MS.USER_ID=U.USER_ID AND MS.SEASON_ID=".$wager->tseason_id."
         WHERE MLM.LEAGUE_ID=".$row['LEAGUE_ID']."
               AND MLM.USER_ID=U.USER_ID"; 
  $db->query($sql); 
  $c = 0;
  while ($row = $db->nextRow()) {
    if ($row['STATUS'] == 1) {
      $league_members['OWNER'] = $row;
    }
    else if ($row['STATUS'] == 2) {
      $current_member = $row;
      if ($can_remove) {
        $current_member['CAN_REMOVE'] = $row; 
      }
      $league_members['CURRENT_MEMBERS'][] = $current_member;
    }
    else if ($row['STATUS'] == 3) {
      $league_members['INVITED_MEMBERS'][] = $row;
    }
    else if ($row['STATUS'] == 4) {
      $league_members['FORMER_MEMBERS'][] = $row;
    }
    else if ($row['STATUS'] == 5) {
      $league_members['DECLINE_MEMBERS'][] = $row;
    }
  }

  if ($owner && $can_invite) {
    // create invitation form
    $invite['LEAGUE_ID'] = $league_id;
    $invite['SEASON_ID'] = $wager->tseason_id;
    $invite['OWNER'] = $auth->getUserId();
    $smarty->assign("invite_form", $invite);
  }

  if ($owner && $can_force) {
    $smarty->assign("force_form", 1);
  }
 
  if ($owner && empty($league['LEAGUE_START_DATE']) && $league['STATUS']== 2) {
    $smarty->assign("set_date_form", 1);
  }

 }

 if (isset($league))
   $smarty->assign("league", $league);

 if (isset($league_members))
   $smarty->assign("league_members", $league_members);

  if ($auth->userOn())
    $smarty->assign("logged", $auth->userOn());

} else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_WAGER_LOGIN');
}

  $smarty->assign("wager_filter_box", $wager_filter_box);
//  if (isset($error))
//    $smarty->assign("error", $error);
  if (isset($create_league_offer))
    $smarty->assign("create_league_offer", $create_league_offer);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/wager_league_control.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/wager_league_control.smarty'.($stop-$start);

// ----------------------------------------------------------------------------

// include common header
  define("WAGER", 1);
//include('inc/top.inc.php');
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_wager.inc.php');

// close connections
include('class/db_close.inc.php');
?>