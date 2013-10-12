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
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
  $success = false;
  if ($auth->userOn() && !$auth->isClanMember()) {
   if (isset($_GET['action']) && $_GET['action']=='accept_invite' && isset($_GET['clan_id'])) {
     $clan = new Clan($_GET['clan_id']);
     $clan->getClanData();
     if (count($clan->clan_members) < 15) {

       // check that user is invited and if there is entry fee involved
       $sql="SELECT ML.CLAN_ID, ML.CLAN_NAME, ML.USER_ID, F.GROUP_ID
             FROM clans ML, clan_members MLM, forum F
             WHERE ML.CLAN_ID=MLM.CLAN_ID
	       AND ML.CLAN_ID=".$_GET['clan_id']."
               AND MLM.USER_ID=".$auth->getUserId()."
               AND MLM.STATUS=3
		AND ML.FORUM_ID=F.FORUM_ID";
 
       $db->query($sql);    
       if ($row = $db->nextRow()) {
         if ($_SESSION['_user']['CREDIT'] >= 10) {
           $udata['STATUS'] = 2;  
           $udata['DATE_JOINED'] = "NOW()";
           $db->update('clan_members', $udata, 'USER_ID='.$auth->getUserId().' AND CLAN_ID='.$_GET['clan_id']);
           unset($udata);
     
           $udata['MEMBERS'] = 'MEMBERS+1';  
           $db->update('clans', $udata, 'CLAN_ID='.$_GET['clan_id']);
           unset($udata);

           unset($sdata);
           $sdata['USER_ID'] = $auth->getUserId();
           $sdata['GROUP_ID'] = $row['GROUP_ID'];
           $sdata['LEVEL'] = 2;
           $sdata['DATE_JOINED'] = "NOW()";

           $db->insert('forum_groups_members', $sdata);
           unset($udata);

           // transfer credits
           $credits = new Credits();
           $credit_log = new CreditsLog();
           $credits->updateClanCredits($_GET['clan_id'], 9);
           $credits->updateCredits($auth->getUserId(), -10); 
           $credit_log->logEvent ($auth->getUserId(), 5, 10);
           $clan_log = new ClanLog();
           $clan_log->logEvent($_GET['clan_id'], 2, 9, $auth->getUserId());
           $clan_user_log = new ClanUserLog();
           $clan_user_log->logEvent($auth->getUserId(), 1, $_GET['clan_id']);
     
           $db->delete('clan_members', "USER_ID=".$auth->getUserId()." AND STATUS=3");    
           if (count($clan->clan_members) == 14) {
              // remove all invites
              $db->delete('clan_members', 'CLAN_ID='.$_GET['clan_id']." AND STATUS=3");                  
           }

           $success = true;
         }
       }
     }
   }
   if (isset($_GET['action']) && $_GET['action']=='decline_invite' && isset($_GET['clan_id'])) {
     $db->update('clan_members', "STATUS=5", 'USER_ID='.$auth->getUserId().' AND CLAN_ID='.$_GET['clan_id']);
     $sql="SELECT ML.CLAN_ID, ML.CLAN_NAME, ML.USER_ID, F.GROUP_ID
             FROM clans ML, forum F
             WHERE ML.CLAN_ID=".$_GET['clan_id']."
		AND ML.FORUM_ID=F.FORUM_ID";
 
     $db->query($sql);    
     if ($row = $db->nextRow()) {
       $db->delete('forum_groups_members', 'USER_ID='.$auth->getUserId().' AND GROUP_ID='.$row['GROUP_ID']);
     }
   }

  } 

  if ($success)
    $content = $langs['LANG_CLAN_MEMBER_U'];
  else 
    $content = $langs['LANG_NOT_CLAN_MEMBER_U'];
    
//  $clanbox = new ClanBox($langs, $_SESSION["_lang"]);
  
//  $content = $clanbox->getClanBox();

  echo $content;
// close connections
include('class/db_close.inc.php');
?>