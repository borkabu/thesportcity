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
include('class/bracket.inc.php');
include('class/bracket_user.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
 
  if ($auth->userOn()) {
   if (isset($_GET['action']) && $_GET['action']=='accept_invite' && isset($_GET['league_id'])) {
     // check that user is invited and if there is entry fee involved
     $sql="SELECT ML.LEAGUE_ID, ML.TITLE, ML.ENTRY_FEE, ML.USER_ID
             FROM bracket_leagues ML, bracket_leagues_members MLM
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
	       AND ML.LEAGUE_ID=".$_GET['league_id']."
               AND MLM.USER_ID=".$auth->getUserId()."
               AND MLM.STATUS=3";
 
     $db->query($sql);    
     if ($row = $db->nextRow()) {

       if ($row['ENTRY_FEE'] == 0) {
         $udata['STATUS'] = 2;  
         $udata['START_DATE'] = "NOW()";
         $db->update('bracket_leagues_members', $udata, 'USER_ID='.$auth->getUserId().' AND LEAGUE_ID='.$_GET['league_id']);
         unset($udata);
       } else {
         if ($row['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] >= $row['ENTRY_FEE']) {
           $udata['STATUS'] = 2;  
           $udata['START_DATE'] = "NOW()";
           $db->update('bracket_leagues_members', $udata, 'USER_ID='.$auth->getUserId().' AND LEAGUE_ID='.$_GET['league_id']);
           unset($udata);

           // transfer credits
           $credits = new Credits();
           $credit_log = new CreditsLog();
           $credits->updateCredits($row['USER_ID'], $row['ENTRY_FEE']);
           $credit_log->logEvent ($row['USER_ID'], 10, $row['ENTRY_FEE'], $auth->getUserId());
           $credits->updateCredits($auth->getUserId(), -1*$row['ENTRY_FEE']); 
           $credit_log->logEvent ($auth->getUserId(), 5, $row['ENTRY_FEE'], $row['USER_ID']);
         }
       }
     }
   }
   if (isset($_GET['action']) && $_GET['action']=='decline_invite' && isset($_GET['league_id'])) {
     $db->update('bracket_leagues_members', "STATUS=5", 'USER_ID='.$auth->getUserId().' AND LEAGUE_ID='.$_GET['league_id']);
   }
  } 

  $bracket = new Bracket();
  $bracketbox = new BracketBox($langs, $_SESSION["_lang"]);
  $bracket_user = new BracketUser($bracket->tseason_id);
  
  $content = $bracketbox->getBracketLeagueBox($bracket_user);

  echo $content;
// close connections
include('class/db_close.inc.php');
?>