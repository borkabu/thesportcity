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
include('class/manager.inc.php');
include('class/rvs_manager_user.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
//$db->showquery=true; 

  if ($auth->userOn()) {
   $sql="SELECT ML.SEASON_ID, ML.LEAGUE_ID, ML.TITLE, ML.ENTRY_FEE, ML.USER_ID, ML.SEASON_ID, ML.PARTICIPANTS
             FROM rvs_manager_leagues ML, rvs_manager_leagues_members MLM
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
	       AND ML.LEAGUE_ID=".$_GET['league_id']."
               AND MLM.USER_ID=".$auth->getUserId()."
               AND MLM.STATUS=3";
   $db->query($sql);    
   $row = $db->nextRow();
   $season_id = $row['SEASON_ID'];

   if (isset($_GET['action']) && $_GET['action']=='accept_invite' && isset($_GET['league_id'])) {
     // check that user is invited and if there is entry fee involved
     $sql="SELECT ML.SEASON_ID, ML.LEAGUE_ID, ML.TITLE, ML.ENTRY_FEE, ML.USER_ID, ML.SEASON_ID, ML.PARTICIPANTS
             FROM rvs_manager_leagues ML, rvs_manager_leagues_members MLM
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
	       AND ML.LEAGUE_ID=".$_GET['league_id']."
               AND MLM.USER_ID=".$auth->getUserId()."
               AND MLM.STATUS=3";
 
     $db->query($sql);    
     if ($row = $db->nextRow()) {
       unset($sdata);
       $sdata['JOINED'] = 'JOINED+1';  
       $db->update('rvs_manager_leagues', $sdata, "LEAGUE_ID=".$_GET['league_id']);

       if ($row['ENTRY_FEE'] == 0) {
         $udata['STATUS'] = 2;  
         $udata['START_DATE'] = "NOW()";
         $db->update('rvs_manager_leagues_members', $udata, 'USER_ID='.$auth->getUserId().' AND LEAGUE_ID='.$_GET['league_id']);
         unset($udata);
         $manager_user_log = new RvsManagerUserLog();
         $manager_user_log->logEvent($auth->getUserId(), 1, $row['SEASON_ID'], $_GET['league_id'], '', $row['USER_ID']);
       } else {
         if ($row['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] >= $row['ENTRY_FEE']) {
           $udata['STATUS'] = 2;  
           $udata['START_DATE'] = "NOW()";
           $udata['ENTRY_FEE'] = $row['ENTRY_FEE'];  
           $db->update('rvs_manager_leagues_members', $udata, 'USER_ID='.$auth->getUserId().' AND LEAGUE_ID='.$_GET['league_id']);
           unset($udata);

           // transfer credits
           $credits = new Credits();
           $credit_log = new CreditsLog();
           $credits->updateRvsLeagueCredits($_GET['league_id'], $row['ENTRY_FEE']);
           $credits->updateCredits($auth->getUserId(), -1*$row['ENTRY_FEE']); 
           $credit_log->logEvent ($auth->getUserId(), 5, $row['ENTRY_FEE'], $_GET['league_id']);
           $manager_user_log = new RvsManagerUserLog();
           $manager_user_log->logEvent($auth->getUserId(), 1, $row['SEASON_ID'], $_GET['league_id'], '', '');
           $rvs_manager_log = new RvsManagerLog();
           $rvs_manager_log->logEvent ($auth->getUserId(), 5, $row['SEASON_ID'], $_GET['league_id']);

           // check that it is filled and remove all other invites
         }
       }
       $sql="SELECT COUNT(MLM.USER_ID) USERS, ML.PARTICIPANTS, ML.SEASON_ID
             FROM rvs_manager_leagues ML, rvs_manager_leagues_members MLM
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
	       AND MLM.LEAGUE_ID=".$_GET['league_id']."
               AND MLM.STATUS in (1,2)";
 
       $db->query($sql);    
       $row = $db->nextRow();
       if ($row['USERS'] == $row['PARTICIPANTS']) {
         // league is assembled
           $rvs_manager_log = new RvsManagerLog();
           $rvs_manager_log->logEvent ($auth->getUserId(), 2, $row['SEASON_ID'], $_GET['league_id']);
           $db->delete('rvs_manager_leagues_members', 'STATUS=3 AND LEAGUE_ID='.$_GET['league_id']); 
           unset($sdata);
           $sdata['STATUS'] = 2;  
           $db->update('rvs_manager_leagues', $sdata, "LEAGUE_ID=".$_GET['league_id']);
       }
     }
   }
   if (isset($_GET['action']) && $_GET['action']=='decline_invite' && isset($_GET['league_id'])) {
     $db->update('rvs_manager_leagues_members', "STATUS=5", 'USER_ID='.$auth->getUserId().' AND LEAGUE_ID='.$_GET['league_id']);
   }
  } 

  $manager = new Manager($season_id);
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
  $rvs_manager_user = new RvsManagerUser($manager->mseason_id);
  
  $content = $managerbox->getRvsManagerLeagueBox($rvs_manager_user);

  echo $content;
// close connections
include('class/db_close.inc.php');
?>