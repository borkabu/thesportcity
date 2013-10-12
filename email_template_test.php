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

// --- build content data -----------------------------------------------------
//else 

 
 $sdata['ADDRESS1'] = 'address1';
 $sdata['ADDRESS2'] = 'address2';
 $sdata['TOWN'] = 'town';
 $sdata['COUNTRY'] = 'COUNTRY';
 $sdata['POSTCODE'] = 'POSTCODE';
 $sdata['ANSWER'] = 'answer';
 $sdata['AUTHOR'] = 'author';
 $sdata['DRAFT_DATE'] = "draft_date";
 $sdata['DRAFT_START_DATE'] = "draft_start_date";
 $sdata['USER_NAME'] = 'yyy';
 $sdata['USER_NAME2'] = 'user_name2';
 $sdata['USER_NAME3'] = 'user_name3';
 $sdata['FIRST_NAME'] = 'first name';
 $sdata['FIRST_NAME2'] = 'first name2';
 $sdata['LAST_NAME'] = 'last name';
 $sdata['LAST_NAME2'] = 'last name2';
 $sdata['SELLING_PRICE'] = '12345';
 $sdata['SELLING_PRICE2'] = '12346';
 $sdata['LEAGUE_ID'] = '12346';
 $sdata['PASSWORD'] = 'xxx';
 $sdata['CATEGORY'] = 'category';
 $sdata['DESCR'] = "descr";
 $sdata['FORUM_NAME'] = "forum_name";
 $sdata['PLAYER_NAME'] = 'player_name';
 $sdata['REALITY'] = "reality";
 $sdata['SEASON_TITLE'] = "season_title";
 $sdata['SOURCE'] = "source";
 $sdata['START_DATE'] = "start_date";
 $sdata['TOPIC_NAME'] = "topic_name";
 $sdata['TOURNAMENT_TITLE'] = "tournament_title";
 $sdata['TEXT'] = "text";
 $sdata['TITLE'] = "title";
 $voting1['USER_NAME'] = "user1";
 $voting2['USER_NAME'] = "user2";
 $voting1['VOTE'] = 1;
 $voting2['VOTE'] = 2;
 $voting1['COMMENT_TRUST_LEVEL'] = 1;
 $voting2['COMMENT_TRUST_LEVEL'] = 2;
 $voting1['CONTENT_TRUST_LEVEL'] = 1;
 $voting2['CONTENT_TRUST_LEVEL'] = 2;

 $vdata[]=$voting1;
 $vdata[]=$voting2;
 $sdata['VOTING_DETAILS'] = $vdata;
 $sdata['URL'] = "url";
 $sdata['URL2'] = "url2";
 $sdata['URL_ALLOW'] = "url_allow";
 $sdata['URL_APPROVE'] = "url_approve";
 $sdata['URL_IGNORE'] = "url_approve";
 $sdata['URL_DISAPPROVE'] = "url_disapprove";
 $sdata['QUESTION'] = 'question';

 $transfer['LEAGUE_ID'] = "55";
 $transfer['TITLE'] = "title";
 $transfer['LAST_NAME'] = "last_name";
 $transfer['FIRST_NAME'] = "first_name";
 $transfer['CURRENT_PRICE_MONEY'] = "12345";
 $transfer['LAST_NAME2'] = "last_name2";
 $transfer['FIRST_NAME2'] = "first_name2";
 $transfer['CURRENT_PRICE_MONEY2'] = "6789";
 $transfer['USER_NAME2'] = "user_name2";
 $transfer['SEASON_TITLE'] = "season_title";
 $sdata['ACCEPTED_TRANSFERS'][] = $transfer;
 $sdata['OFFERED_TRANSFERS'][] = $transfer;
 $email=new Email();
 $descr = $email->getEmailFromTemplate ('email_comment_voting_moderate', $sdata) ;
 echo $descr;
// $pm = new PM();
// $pm->createSystemPM(6, $langs['LANG_EMAIL_RVS_MODERATE_TRANSFER_SUBJECT'], $descr);

// include common footer
//include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>