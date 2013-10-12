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
// --- build content data -----------------------------------------------------

// include common header
$content = '';

  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 8);

  $manager = new Manager();
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
  if ($auth->userOn())
    $manager_user = new ManagerUser($manager->mseason_id);

  $manager_filter_box = $managerbox->getSoloManagerFilterBox($manager->mseason_id);

 $has_league = false;
 $can_delete = false;
 $can_invite = false;
 $can_remove = false;
 $league_members = array();
 $league_id = -1;
 $sql= "SELECT ML.LEAGUE_ID
          FROM solo_manager_leagues ML
         WHERE ML.USER_ID=".$auth->getUserId()." 
               AND ML.SEASON_ID=".$manager->mseason_id; 
 $db->query($sql); 
 if ($row = $db->nextRow()) {
//   $has_league = true;
   $league_id = $row['LEAGUE_ID'];
   $solo_league = new League("solo_manager", $league_id);
   $solo_league->getLeagueInfo();
   $participants = $solo_league->league_info['PARTICIPANTS'];
   $joined = $solo_league->league_info['JOINED'];
   if ($solo_league->league_info['JOINED'] == 1 && $solo_league->league_info['STATUS'] == 0) {
     $can_delete = 1;
   }

   if ($participants > $joined) {
     $can_invite = 1;
     $can_remove = 1;
   }

//print_r($league->league_info);
 }


if ($auth->userOn() && $league_id > 0 && isset($_POST['remove_user']) && isset($_POST['user_id'])) {
  $db->select('solo_manager_leagues_members', "STATUS", 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$league_id);  
  if ($row = $db->nextRow()) {
    unset($udata);
    $udata['STATUS'] = 4;  
    $udata['END_DATE'] = "NOW()";
    $db->update('solo_manager_leagues_members', $udata, 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$league_id);  
    unset($udata);
    $manager_user_log = new ManagerUserLog();
    $manager_user_log->logEvent($_POST['user_id'], 12, 0, $manager->mseason_id, '', $auth->getUserId());
    $manager_user_log->logEvent($auth->getUserId(), 13, 0, $manager->mseason_id, '', $_POST['user_id']);
    if ($row['STATUS'] == 2) {
      $udata['JOINED'] = "JOINED-1";   
      $db->update('solo_manager_leagues', $udata, 'LEAGUE_ID='.$league_id);
      unset($udata);
    }

  }
}
else if ($auth->userOn() && $league_id > 0 && isset($_POST['remove_user2']) && isset($_POST['user_id'])) {
    $db->delete('solo_manager_leagues_members', 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$league_id);  
}
else if ($auth->userOn() && $league_id > 0 && isset($_POST['cancel_all_invites'])) {
  $league->cancelAllInvites();
}
else if ($auth->userOn() && $league_id > 0 && isset($_POST['tag_user']) && isset($_POST['user_id']) && isset($_POST['tag'])) {
  $udata['TAG'] = "'".$_POST['tag']."'";  
  $db->update('solo_manager_leagues_members', $udata, 'USER_ID='.$_POST['user_id'].' AND LEAGUE_ID='.$league_id);  
  unset($udata);
}
else if ($auth->userOn() && $league_id > 0 && isset($_POST['set_rules'])) {
  $s_fields = array('rules');
  $d_fields = '';
  $c_fields = array('recruitment_active', 'accept_newbies', 'real_prizes');
  $i_fields = array('entry_fee', 'country', 'participants', 'type', 'invite_type');
 
  $udata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);

  $db->update('solo_manager_leagues', $udata, 'LEAGUE_ID='.$league_id);  
  unset($udata);
}
else if ($auth->userOn() && $can_delete && $league_id > 0 && isset($_POST['delete_league'])) { 
//$db->showquery=true;
  $db->delete('solo_manager_leagues', 'LEAGUE_ID='.$league_id." AND STATUS=0");  
  $db->delete('solo_manager_leagues_members', 'STATUS IN (1,3) AND LEAGUE_ID='.$league_id);  
  header("Location: solo_manager_league_control.php");
}

if ($auth->userOn() && isset($_POST['create_league']) && isset($_POST['title']) && !empty($_POST['title'])) {
   $sql= "SELECT ML.LEAGUE_ID
          FROM solo_manager_leagues ML, solo_manager_leagues_members MLM
         WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
	       AND MLM.USER_ID=".$auth->getUserId()." 
               AND ML.SEASON_ID=".$manager->mseason_id." 
               AND MLM.STATUS=1"; 
   $db->query($sql); 
   if (!$row = $db->nextRow()) {    
     $sdata['SEASON_ID'] = $manager->mseason_id;
     $sdata['TITLE'] = "'".$_POST['title']."'";
     $sdata['USER_ID'] = $auth->getUserId();
     $db->insert('solo_manager_leagues', $sdata);
     unset($sdata);
     $db->select("solo_manager_leagues", "LEAGUE_ID", "USER_ID=".$auth->getUserId()." AND SEASON_ID=".$manager->mseason_id);   
     if ($row = $db->nextRow()) {
       $sdata['LEAGUE_ID'] = $row['LEAGUE_ID'];
       $sdata['USER_ID'] = $auth->getUserId();
       $sdata['START_DATE'] = "NOW()";
       $sdata['STATUS'] = 1;
       $db->insert('solo_manager_leagues_members', $sdata);     
       unset($sdata);
     }
   }
}

if ($auth->userOn() && $league_id > 0 && isset($_POST['invite']) && isset($_POST['user_name']) && isset($_POST['mseason_id'])) {
   $sql = "SELECT U.USER_ID, MU.USER_ID ISIN, MU.IGNORE_LEAGUES 
             FROM users U LEFT JOIN solo_manager_users MU ON U.USER_ID=MU.USER_ID AND MU.SEASON_ID=".$_POST['mseason_id']."
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
       $sdata['LEAGUE_ID'] = $league_id;
       $sdata['USER_ID'] = $row['USER_ID'];
       $sdata['STATUS'] = 3;
       // check that it is not already there
       $db->select("solo_manager_leagues_members", "USER_ID", "STATUS IN (2, 3) AND USER_ID='".$sdata['USER_ID']."' AND LEAGUE_ID=".$league_id);       
       if ($row = $db->nextRow()) {
         $invite['INVITE_ERROR']['USERDOUBLE'] = 1;
       } 
       else {
         $db->select("solo_manager_leagues_members", "USER_ID", "STATUS IN (4, 5) AND USER_ID='".$sdata['USER_ID']."' AND LEAGUE_ID=".$league_id);
         if ($row = $db->nextRow()) {
           $db->update('solo_manager_leagues_members', "STATUS=3", 'USER_ID='.$sdata['USER_ID'].' AND LEAGUE_ID='.$league_id);
         }
         else $db->insert('solo_manager_leagues_members', $sdata);     
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
 if ($manager_user->solo_inited) {
   $sql= "SELECT ML.*, U.USER_NAME
          FROM solo_manager_leagues ML, solo_manager_leagues_members MLM, users U 
         WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
	       AND MLM.USER_ID=".$auth->getUserId()." 
	       AND MLM.USER_ID=U.USER_ID
               AND ML.SEASON_ID=".$manager->mseason_id." 
               AND MLM.STATUS=1"; 
//echo $sql;
   $db->query($sql); 
   if (!$row = $db->nextRow()) {
      $smarty->assign("create_league_offer", true);
   }
   else $has_league = true;
 }
 else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_NO_TEAM');
 }

if ($has_league) {
  $league  = $row;
  $league['TITLE'] = $row['TITLE'];
  $league['OWNER'] = $row['USER_NAME'];
  $league['LEAGUE_ID'] = $row['LEAGUE_ID'];
  $league['RULES'] = $row['RULES'];
 //echo $row['RULES'];
  $league['DESCR'] = $row['RULES'];
  $league['ENTRY_FEE'] = $row['ENTRY_FEE'];
  $league['PARTICIPANTS'] = $row['PARTICIPANTS'];
  $PRESET_VARS['rules'] = $row['RULES'];
  $league['COUNTRY'] = inputCountries('country', $row['COUNTRY']);
  $league['INVITE_TYPE'] = inputLeagueInviteTypes('invite_type', $row['INVITE_TYPE']);
  if ($row['INVITE_TYPE'] == 1 && empty($row['INVITE_CODE'])) {
    unset($sdata);
    $invite_code = gen_rand_string(0, 8);
    $sdata['INVITE_CODE'] = "'".$invite_code."'";
    $db->update("solo_manager_leagues", $sdata, "LEAGUE_ID=".$row['LEAGUE_ID']);
    $league['INVITE_CODE'] = $sdata['INVITE_CODE'];
  } else if ($row['INVITE_TYPE'] == 1 && !empty($row['INVITE_CODE'])) {
    $league['INVITE_CODE'] = $row['INVITE_CODE'];
  }
  
  if ($row['JOINED'] == 1 && $row['STATUS'] == 0) {
    $can_delete = 1;
  }

  foreach ($row as $key => $val) {
    $PRESET_VARS[strtolower($key)] = $val;
  }

//print_r($PRESET_VARS);

  $league_id=$row['LEAGUE_ID'];
  $owner = true;
  $db->free();
  // get members
  $sql= "SELECT MLM.*, U.USER_NAME, MS.POINTS, MS.PLACE
          FROM solo_manager_leagues_members MLM, users U
               LEFT JOIN solo_manager_standings MS ON MS.USER_ID=U.USER_ID AND MS.SEASON_ID=".$manager->mseason_id."
         WHERE MLM.LEAGUE_ID=".$league_id."
               AND MLM.USER_ID=U.USER_ID"; 
  $db->query($sql); 
  while ($row = $db->nextRow()) {
   if ($row['STATUS'] ==1) {
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
  $db->free();

  if ($owner && $can_delete) {
    $smarty->assign("delete_form", 1);
  }

  if ($owner && $can_invite) {
    // create invitation form
    $invite['LEAGUE_ID'] = $league_id;
    $invite['SEASON_ID'] = $manager->mseason_id;
    $invite['OWNER'] = $auth->getUserId();
    $smarty->assign("invite_form", $invite);
  }
  $smarty->assign("league", $league);
  $smarty->assign("league_members", $league_members);

 }

 if ($auth->userOn())
   $smarty->assign("logged", $auth->userOn());

} else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_LOGIN');
}

  $smarty->assign("manager_filter_box", $manager_filter_box);
  if (isset($error))
    $smarty->assign("error", $error);
  if (isset($create_league_offer))
    $smarty->assign("create_league_offer", $create_league_offer);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/solo_manager_league_control.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/solo_manager_league_control.smarty'.($stop-$start);

// ----------------------------------------------------------------------------

  define("SOLO_MANAGER", 1);
// include common header
//include('inc/top.inc.php');
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager.inc.php');

// close connections
include('class/db_close.inc.php');
?>