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
include('class/manager_user.inc.php');
include('class/manager_tournament.inc.php');
include('class/manager_tournamentbox.inc.php');
 $manager_tournamentbox = new ManagerTournamentBox($langs, $_SESSION["_lang"]);

// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
 
  if ($auth->userOn()) {
   if (isset($_GET['action']) && $_GET['action']=='accept_invite' && isset($_GET['mt_id'])) {
     // check that user is invited and if there is entry fee involved
     $sql="SELECT ML.MT_ID, ML.TITLE, ML.ENTRY_FEE, ML.USER_ID
             FROM manager_tournament ML, manager_tournament_members MLM
             WHERE ML.MT_ID=MLM.MT_ID
	       AND ML.MT_ID=".$_GET['mt_id']."
               AND MLM.USER_ID=".$auth->getUserId()."
               AND MLM.STATUS=3";
//echo $sql; 
     $db->query($sql);    
     if ($row = $db->nextRow()) {
       unset($sdata);   
       $sdata['JOINED'] = 'JOINED+1';
       $sdata['PRIZE_FUND'] = 'PRIZE_FUND+'.$row['ENTRY_FEE'];
       $db->update('manager_tournament', $sdata, "MT_ID=".$_GET['mt_id']);

       if ($row['ENTRY_FEE'] == 0) {
         unset($udata);
         $udata['STATUS'] = 2;  
         $udata['START_DATE'] = "NOW()";
         $db->update('manager_tournament_members', $udata, 'USER_ID='.$auth->getUserId().' AND MT_ID='.$_GET['mt_id']);
         unset($sdata);   
         $sdata['USER_ID'] = $auth->getUserId();
         $sdata['MT_ID'] = $_GET['mt_id'];
         $sdata['TOUR'] = 0;
         $db->insert('manager_tournament_users', $sdata);

         $manager_tournament_log = new ManagerTournamentLog();
         $manager_tournament_log->logEvent($auth->getUserId(), 2, 0, 0, $_GET['mt_id']);

       } else {
         if ($row['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] >= $row['ENTRY_FEE']) {
           unset($udata);
           $udata['STATUS'] = 2;  
           $udata['START_DATE'] = "NOW()";
           $udata['ENTRY_FEE'] = $row['ENTRY_FEE'];  
           $db->update('manager_tournament_members', $udata, 'USER_ID='.$auth->getUserId().' AND MT_ID='.$_GET['mt_id']);
           unset($sdata);
           $sdata['USER_ID'] = $auth->getUserId();
           $sdata['MT_ID'] = $_GET['mt_id'];
           $sdata['TOUR'] = 0;
           $db->insert('manager_tournament_users', $sdata);

           // transfer credits
           $credits = new Credits();
           $credits->updateCredits ($auth->getUserId(), $row['ENTRY_FEE'] * -1);
           $credit_log = new CreditsLog();
           $credit_log->logEvent ($auth->getUserId(), 5, $row['ENTRY_FEE']);

           $manager_tournament_log = new ManagerTournamentLog();
           $manager_tournament_log->logEvent($auth->getUserId(), 2, 0, 0, $_GET['mt_id']);
         }
       }

       $sql="SELECT COUNT(MLM.USER_ID) USERS, ML.PARTICIPANTS, ML.SEASON_ID
             FROM manager_tournament ML, manager_tournament_members MLM
             WHERE ML.MT_ID=MLM.MT_ID
	       AND MLM.MT_ID=".$_GET['mt_id']."
               AND MLM.STATUS in (1,2)";
 
       $db->query($sql);    
       $row = $db->nextRow();
       if ($row['USERS'] == $row['PARTICIPANTS']) {
         // league is assembled
           $manager_tournament_log = new ManagerTournamentLog();
           $manager_tournament_log->logEvent($auth->getUserId(), 7, 0, 0, $_GET['mt_id']);
           $db->delete('manager_tournament_members', 'STATUS=3 AND MT_ID='.$_GET['mt_id']); 
           unset($sdata);
           $sdata['STATUS'] = 2;  
           $db->update('manager_tournament', $sdata, "MT_ID=".$_GET['mt_id']);
           $manager_tournament = new ManagerTournament($_GET['mt_id']);
           $manager = new Manager($manager_tournament->mseason_id);
           $manager_tournament->setTours();
       }

     }
   }
   if (isset($_GET['action']) && $_GET['action']=='decline_invite' && isset($_GET['mt_id'])) {
     $db->update('manager_tournament_members', "STATUS=5", 'USER_ID='.$auth->getUserId().' AND MT_ID='.$_GET['mt_id']);
   }
  } 

  $manager = new Manager();
  $managertournamentbox = new ManagerTournamentBox($langs, $_SESSION["_lang"]);
  $manager_user = new ManagerUser($manager->mseason_id);
  
  $content = $managertournamentbox->getManagerTournamentBox();

  echo $content;
// close connections
include('class/db_close.inc.php');
?>