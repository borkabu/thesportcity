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
include('class/manager.inc.php');

// --- build content data -----------------------------------------------------
//else 

 $manager = new Manager();

 $sql="SELECT MLM.USER_ID, U.USER_NAME, RML.TITLE, RML.DRAFT_START_DATE, RML.DRAFT_DATE, RML.LEAGUE_ID, U.TIMEZONE, U.LAST_LANG, U.EMAIL
      FROM rvs_manager_leagues_members MLM, users U, rvs_manager_leagues RML
      WHERE MLM.LEAGUE_ID=6		
        AND MLM.STATUS in (1,2)
	   AND U.USER_ID=MLM.USER_ID
   	   AND U.USER_ID=6
	   AND RML.LEAGUE_ID=MLM.LEAGUE_ID";
 
 $db->query($sql);    
 $u = 0;
 unset($players);
 if ($row = $db->nextRow()) {
   $player = $row;
 }

//print_r($player);
// $manager->sendDraftEndEmail($player);


/* $sdata['USER_NAME'] = 'yyy';
 $sdata['PASSWORD'] = 'xxx';
 $sdata['URL'] = $conf_site_url.'/register.php?mode=activate&u='.$_SESSION['_user']['USER_ID'].'&k='.$user_actkey."\r\n\r\n";
 echo $email->getEmailFromTemplate ('email_register', $sdata) ;
 $email->body="text";
 echo $email->send("borkaaaa@yahoo.co.uk", "test");            */
// include common footer
//include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>