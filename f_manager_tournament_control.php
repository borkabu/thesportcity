<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
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
include('class/manager.inc.php');
include('class/manager_user.inc.php');
include('class/manager_tournament.inc.php');
include('lib/manager_config.inc.php');
include('class/manager_tournamentbox.inc.php');
 $manager_tournamentbox = new ManagerTournamentBox($langs, $_SESSION["_lang"]);

// --- build content data -----------------------------------------------------

// include common header
$content = '';

  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 4);

  $manager = new Manager();
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
  $manager_tournament = new ManagerTournament();
  $current_tour = $manager->getCurrentTour();
  $manager_filter_box = $managerbox->getManagerFilterBox($manager->mseason_id);

 if ($auth->userOn()) {
   $manager_user = new ManagerUser($manager->mseason_id);
   $has_tournament = false;
   $tournament_id = -1;
   $created_date = "";
   $market_size = $manager->getMarketSize();
   $tours = 0;
   $participants = 0;
   $joined = 0;
   $can_create = 0;
   $can_update = 0;
   $can_invite = 0;
   $can_remove = 0;
   $can_force = 0;
   $can_delete = 0;

   $sql="SELECT count(tour_id) TOURS from manager_tours 
	where SEASON_ID=".$manager->mseason_id." and start_date > NOW()";
   $db->query($sql); 
   $row = $db->nextRow();
   $tours = $row['TOURS'];      
   if ($tours > 0)
     $can_create = 1;
  
   $sql= "SELECT ML.*
            FROM manager_tournament ML
           WHERE ML.USER_ID=".$auth->getUserId()." 
	       AND ML.STATUS in (0,1,2,3)
                AND ML.SEASON_ID = ".$manager->mseason_id; 
   $db->query($sql); 
   if ($row = $db->nextRow()) {
////   $has_league = true;
     $manager_tournament = new ManagerTournament($row['MT_ID']);
     $created_date = $row['CREATED_DATE'];
     $participants = $row['PARTICIPANTS'];
     $joined = $row['JOINED'];
     $logged = 1;

     if ($joined == 1) {
       $can_update = 1;
       $can_delete = 1;
     }

     if ($participants > $joined) {
       $can_invite = 1;
       $can_remove = 1;
     }

     if ($participants > $joined && $joined >= 4) // && $current_tour > 1)
       $can_force = 1;

   }

   $sql= "SELECT ML.*
            FROM manager_tournament ML
           WHERE ML.USER_ID=".$auth->getUserId()." 
	       AND ML.STATUS = 3
                AND ML.SEASON_ID = ".$manager->mseason_id; 
   $db->query($sql); 
   $past_tournaments = array();
   while ($row = $db->nextRow()) {
     $past_tournaments[] = $row; 
   }

   if (count($past_tournaments) > 0)
     $smarty->assign("past_tournaments", $past_tournaments);
}

if ($auth->userOn() && $can_remove && isset($_POST['remove_user']) && isset($_POST['user_id'])) {
//$db->showquery=true;
  $db->delete('manager_tournament_users', 'USER_ID='.$_POST['user_id'].' AND MT_ID='.$manager_tournament->mt_id);  
  unset($udata);
  $udata['STATUS'] = 4;  
  $udata['END_DATE'] = "NOW()";
  $db->update('manager_tournament_members', $udata, 'USER_ID='.$_POST['user_id'].' AND MT_ID='.$manager_tournament->mt_id);  
  unset($udata);
  $db->select('manager_tournament_members', "*", 'USER_ID='.$_POST['user_id'].' AND MT_ID='.$manager_tournament->mt_id);
  $row= $db->nextRow();
  $manager_tournament_log = new ManagerTournamentLog();
  $manager_tournament_log->logEvent ($_POST['user_id'], 6, '', '', $manager_tournament->mt_id);
  $credits = new Credits();
  $credit_log = new CreditsLog();
  $credits->updateTournamentCredits($manager_tournament->mt_id, -1*$manager_tournament->fee);
  $credits->updateCredits($_POST['user_id'], $manager_tournament->fee); 
  $credit_log->logEvent ($_POST['user_id'], 19, $manager_tournament->fee);

  // update start tour and end tour
  unset($sdata);
  $udata['JOINED'] = 'JOINED-1';  
  $db->update('manager_tournament', $udata, 'MT_ID='.$manager_tournament->mt_id);  

}
else if ($auth->userOn() && $manager_tournament->mt_id > 0 && isset($_POST['remove_user2']) && isset($_POST['user_id'])) {
  $db->delete('manager_tournament_members', 'USER_ID='.$_POST['user_id'].' AND MT_ID='.$manager_tournament->mt_id);  
  unset($udata);
}
else if ($auth->userOn() && $manager_tournament->mt_id > 0 && isset($_POST['cancel_all_invites'])) {
  $db->delete('manager_tournament_members', 'STATUS=3 AND MT_ID='.$manager_tournament->mt_id);  
  unset($udata);
}
else if ($auth->userOn() && $can_delete && isset($_POST['delete_tournament'])) { 
  $db->delete('manager_tournament', 'MT_ID='.$manager_tournament->mt_id);  
  $db->delete('manager_tournament_members', 'STATUS IN (1,3) AND MT_ID='.$manager_tournament->mt_id);  
  header("Location: f_manager_tournament_control.php");
}
else if ($auth->userOn() && $can_force && isset($_POST['force_start'])) { 
  unset($udata);
  $udata['PARTICIPANTS'] = "JOINED";
  $udata['STATUS'] = 2;
  $db->update('manager_tournament', $udata, 'MT_ID='.$manager_tournament->mt_id);  
  $db->delete('manager_tournament_members', 'STATUS=3 AND MT_ID='.$manager_tournament->mt_id);  
  unset($udata);
  $manager_tournament_log = new ManagerTournamentLog();
  $manager_tournament_log->logEvent($auth->getUserId(), 7, 0, 0, $manager_tournament->mt_id);
  $manager_tournament = new ManagerTournament($manager_tournament->mt_id);
  $manager = new Manager($manager_tournament->mseason_id);
  $manager_tournament->setTours();
  $can_force = false;
}
else if ($auth->userOn() && $manager_tournament->mt_id > 0 && isset($_POST['tag_user']) && isset($_POST['user_id']) && isset($_POST['tag'])) {
  $udata['TAG'] = "'".$_POST['tag']."'";  
  $db->update('manager_tournament_members', $udata, 'USER_ID='.$_POST['user_id'].' AND MT_ID='.$manager_tournament->mt_id);  
  unset($udata);
}
else if ($auth->userOn() && $manager_tournament->mt_id > 0 && isset($_POST['set_rules'])) {
  if ($_POST['rules'] == "" && isset($_POST['simple_text']))
    $_POST['rules'] = $_POST['simple_text'];

  $_POST['publish'] = 'Y';
  if ($manager_tournament->status == 0)
    $_POST['status'] = 1;
  else $_POST['status'] = $manager_tournament->status;
  $i_fields = array('country', 'invite_type', 'tournament_type', 'duration');
  $s_fields = array('rules');
  $d_fields = '';
  $c_fields = array('real_prizes', 'publish');
  
  $error = FALSE;
  // check for password matching
//echo $can_update;
  if ($can_update) {
    $i_fields = array('entry_fee', 'country', 'participants', 'invite_type', 'status', 'tournament_type', 'duration');
    if ($_POST['entry_fee'] < 0) {
      $error = TRUE;
      $conf_error['ERROR_MANAGER_TOURNAMENT_ENTRY_FEE'] = 1;
    }

/*    if (($_POST['participants'] < 3 || $_POST['participants']*$manager_team_sizes[$manager->sport_id] > $market_size/2)  && empty($draft_date)) {
      $error = TRUE;
//      $conf_error['ERROR_MANAGER_TOURNAMENT_PARTICIPANTS']['MSG'] = str_replace("%p", round($market_size/(2*$_POST['team_size'])), $langs['LANG_ERROR_RSV_LEAGUE_PARTICIPANTS_U']);
    }*/
  }

  if (isset($conf_error))
    $smarty->assign("conf_error", $conf_error);
  if (!$error) {
    $udata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
//$db->showquery=true;
    $db->update('manager_tournament', $udata, 'MT_ID='.$manager_tournament->mt_id);  
    unset($udata);
    header('Location: f_manager_tournament_control.php'); 
  }
}

if ($auth->userOn() && isset($_POST['create_tournament']) && !empty($_POST['title'])
    && $auth->getCredits() >= 0 && $can_create) {
   $sql= "SELECT ML.MT_ID
           FROM manager_tournament ML, manager_tournament_members MLM
          WHERE ML.MT_ID=MLM.MT_ID
	       AND MLM.USER_ID=".$auth->getUserId()." 
               AND ML.SEASON_ID=".$manager->mseason_id." 
               AND ML.STATUS in (0,1,2,3)
               AND MLM.STATUS=1"; 
   $db->query($sql); 
   if (!$row = $db->nextRow()) {    
     $sql="SELECT count(tour_id) TOURS from manager_tours 
		where SEASON_ID=".$manager->mseason_id." and start_date > now()";
     $db->query($sql); 
     $row = $db->nextRow();
     $sdata['SEASON_ID'] = $manager->mseason_id;
     $sdata['TITLE'] = "'".$_POST['title']."'";
     $sdata['USER_ID'] = $auth->getUserId();
     $sdata['STATUS'] = 0;
     $sdata['CREATED_DATE'] = "NOW()";
     $db->insert('manager_tournament', $sdata);
     $mt_id = $db->id();
     unset($sdata);
     $sdata['MT_ID'] = $mt_id;
     $sdata['USER_ID'] = $auth->getUserId();
     $sdata['START_DATE'] = "NOW()";
     $sdata['STATUS'] = 1;
     $db->insert('manager_tournament_members', $sdata);     
     unset($sdata);
     $sdata['MT_ID'] = $mt_id;
     $sdata['USER_ID'] = $auth->getUserId();
     $sdata['TOUR'] = 0;
     $db->insert('manager_tournament_users', $sdata);     

     unset($sdata);    

     $manager_tournament_log = new ManagerTournamentLog();
     $manager_tournament_log->logEvent($auth->getUserId(), 2, 0, 0, $mt_id);

/*    $credits = new Credits();
    $credit_log = new CreditsLog();
    $credits->updateCredits($auth->getUserId(), -3);
    $credit_log->logEvent ($auth->getUserId(), 9, 3);*/
   }
   header('Location: f_manager_tournament_control.php');
}

if ($auth->userOn() && $manager_tournament->mt_id > 0 && isset($_POST['invite']) && isset($_POST['user_name']) && isset($_POST['mseason_id'])) {
   $sql = "SELECT U.USER_ID, MU.USER_ID ISIN, MU.IGNORE_LEAGUES 
             FROM users U LEFT JOIN manager_users MU ON U.USER_ID=MU.USER_ID AND MU.SEASON_ID=".$_POST['mseason_id']."
            WHERE USER_NAME='".$_POST['user_name']."'";
   $db->query($sql);
   if ($row = $db->nextRow()) {
     if (empty($row['IGNORE_LEAGUES'])) {
       $invite['INVITE_ERROR']['NOTEAM'] = 1;
     }
     else if ($row['IGNORE_LEAGUES'] == 'Y') {
       $invite['INVITE_ERROR']['USERIGNORE'] = 1;
     } 
     else if (!empty($row['ISIN']) && $row['ISIN'] != "") {
       $sdata['MT_ID'] = $manager_tournament->mt_id;
       $sdata['USER_ID'] = $row['USER_ID'];
       $sdata['STATUS'] = 3;
       // check that it is not already there
       $db->select("manager_tournament_members", "USER_ID", "STATUS IN (2, 3) AND USER_ID='".$sdata['USER_ID']."' AND MT_ID=".$manager_tournament->mt_id);       
       if ($row = $db->nextRow()) {
         $invite['INVITE_ERROR']['USERDOUBLE'] = 1;
       } 
       else {
         $db->select("manager_tournament_members", "USER_ID", "STATUS IN (4, 5) AND USER_ID='".$sdata['USER_ID']."' AND MT_ID=".$manager_tournament->mt_id);
         if ($row = $db->nextRow()) {
           $db->update('manager_tournament_members', "STATUS=3", 'USER_ID='.$sdata['USER_ID'].' AND MT_ID='.$manager_tournament->mt_id);
         }
         else $db->insert('manager_tournament_members', $sdata);     
         unset($sdata);
       }
     }
     else {
      $invite['INVITE_ERROR']['NOTEAM'] = 1;
     }
   }
   else {
      $invite['INVITE_ERROR']['NOUSER'] = 1;
   }
}

if ($auth->userOn()) {
  // initialize user team
    $muser = $manager->getUser($auth->getUserId());
    if ($muser != '') {
      $sql= "SELECT ML.*, U.USER_NAME
            FROM manager_tournament ML, manager_tournament_members MLM,
                 users U
           WHERE ML.MT_ID=MLM.MT_ID
	       AND MLM.USER_ID=".$auth->getUserId()." 
	       AND MLM.USER_ID=U.USER_ID
                 AND ML.SEASON_ID=".$manager->mseason_id." 
	       AND ML.STATUS in (0,1,2,3)
                 AND MLM.STATUS=1"; 
    // echo $sql;
      $db->query($sql); 
      if (!$row = $db->nextRow()) {
        if ($can_create) {
          $smarty->assign("create_tournament_offer", true);
        } else {
          $error['MSG'] = $langs['LANG_MANAGER_CREATE_TOO_LATE_U'];
        }
      }
      else $has_tournament = true;
    } else  {
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_TOURNAMENT_NO_TEAM');
    }

if ($has_tournament) {
  $tournament  = $row;
  $PRESET_VARS['rules'] = $row['RULES'];
  if (!$can_update)
    $tournament['READONLY'] = 1;
  $tournament['COUNTRY'] = inputCountries('country', $row['COUNTRY']);

  $opt['class'] = 'input';
  $opt['options'] = array(4=>4, 
			  8=>8, 
			  16=>16, 
			  32=>32, 
			  64=>64,
			  128=>128); //7
  if (!$can_update)
    $tournament['PARTICIPANTS'] = $row['PARTICIPANTS'];
  else $tournament['PARTICIPANTS'] = $frm->getInput(FORM_INPUT_SELECT, 'participants', $row['PARTICIPANTS'], $opt, $row['PARTICIPANTS']);
  $tournament['INVITE_TYPE'] = inputLeagueInviteTypes('invite_type', $row['INVITE_TYPE']);
  $tournament['INVITE_TYPE_ID'] = $row['INVITE_TYPE'];
  if ($row['INVITE_TYPE'] == 1 && empty($row['INVITE_CODE'])) {
    unset($sdata);
    $sdata['INVITE_CODE'] = "'".gen_rand_string(0, 8)."'";
    $db->update("manager_tournament", $sdata, "MT_ID=".$row['MT_ID']);
    $tournament['INVITE_CODE'] = $sdata['INVITE_CODE'];
  } else if ($row['INVITE_TYPE'] == 1 && !empty($row['INVITE_CODE'])) {
    $tournament['INVITE_CODE'] = $row['INVITE_CODE'];
  }
  $tournament['TOURNAMENT_TYPE_ID'] = $row['TOURNAMENT_TYPE'];
  $tournament['TOURNAMENT_TYPE_NAME'] = $tournament_type[$row['TOURNAMENT_TYPE']];
  $tournament['TOURNAMENT_TYPE'] = inputTournamentTypes('tournament_type', $row['TOURNAMENT_TYPE']);
  
  foreach ($row as $key => $val) {
    $PRESET_VARS[strtolower($key)] = $val;
  }

  $owner = true;
  $db->free();
  // get members
  $sql= "SELECT MLM.*, U.USER_NAME, MS.POINTS, MS.PLACE
          FROM users U, manager_tournament_members MLM
		left join manager_standings MS
			ON MS.MSEASON_ID=".$manager->mseason_id."
			AND MS.USER_ID=MLM.USER_ID
         WHERE MLM.MT_ID=".$manager_tournament->mt_id."
               AND MLM.USER_ID=U.USER_ID"; 
  $db->query($sql); 
  $c = 0;
  while ($row = $db->nextRow()) {
   if ($row['STATUS'] == 1) {
     $tournament_members['OWNER'] = $row;
   }
   else if ($row['STATUS'] == 2) {
     $current_member = $row;
     if ($can_remove) {
       $current_member['CAN_REMOVE'] = $row; 
     }
     $tournament_members['CURRENT_MEMBERS'][] = $current_member;
   }
   else if ($row['STATUS'] == 3) {
     $tournament_members['INVITED_MEMBERS'][] = $row;
   }
   else if ($row['STATUS'] == 4) {
     $tournament_members['FORMER_MEMBERS'][] = $row;
   }
   else if ($row['STATUS'] == 5) {
     $tournament_members['DECLINE_MEMBERS'][] = $row;
   }

   $c++;
  }
  $db->free();

  if ($owner && $can_invite) {
    // create invitation form
    $invite['MT_ID'] = $manager_tournament->mt_id;
    $invite['SEASON_ID'] = $manager->mseason_id;
    $invite['OWNER'] = $auth->getUserId();
    $smarty->assign("invite_form", $invite);
  }
  if ($owner && $can_delete) {
    $smarty->assign("delete_form", 1);
  }

  if ($owner && $can_force) {
    $smarty->assign("force_form", 1);
  }


  $smarty->assign("tournament", $tournament);
  $smarty->assign("tournament_members", $tournament_members);
 }

  if (isset($logged))
    $smarty->assign("logged", $logged);

} else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_TOURNAMENT_LOGIN');
}

  $smarty->assign("manager_filter_box", $manager_filter_box);
  if (isset($error))
    $smarty->assign("error", $error);
  if (isset($create_tournament_offer))
    $smarty->assign("create_tournament_offer", $create_tournament_offer);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_tournament_control.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_tournament_control.smarty'.($stop-$start);
  
// ----------------------------------------------------------------------------

// include common header
  define("FANTASY_TOURNAMENT", 1);
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager_tournament.inc.php');

// close connections
include('class/db_close.inc.php');
?>