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

  // get league
//$db->showquery=true;
 if (isset($_GET['league_id'])) {

   // get league
   $manager = new Manager('', 'rvs');
   $rvs_manager_user = new RvsManagerUser($manager->mseason_id, $_GET['league_id']);

   $league = new League('rvs_manager', $_GET['league_id']);
   $league->getLeagueInfo();

   if (!$auth->userOn())
     $smarty->assign("not_logged", 1);
   elseif ($league->league_info['DRAFT_TYPE'] == 1 && 
       $league->league_info['DRAFT_STATE'] == 0 && 
       !empty($league->league_info['DRAFT_START_DATE'])) {
     if (isset($_POST['add_candidates']) || isset($_POST['action'])) {
       if (isset($_POST['add_candidates'])) {
         $candidates = $_POST['candidates'];
         $sql="SELECT MAX(ORDER_ID) MORDER 
		FROM rvs_manager_draft_candidates 
		where USER_ID=".$auth->getUserId()." 
			AND LEAGUE_ID=".$_GET['league_id'];
	 $db->query($sql);
         $row = $db->nextRow();
         $order = $row['MORDER'] + 1;
         foreach($candidates as $candidate) {
            unset($sdata);
            $sdata['USER_ID'] = $auth->getUserId();
            $sdata['PLAYER_ID'] = $candidate;
            $sdata['LEAGUE_ID'] = $_GET['league_id'];
            $sdata['ORDER_ID'] = $order++;
            $db->insert('rvs_manager_draft_candidates', $sdata);
         }
       }
       if (isset($_POST['action']) && $_POST['action'] == 'remove_candidates') {
         $candidates = $_POST['my_candidates'];
         foreach($candidates as $candidate) {
            $db->delete('rvs_manager_draft_candidates', "USER_ID=".$auth->getUserId()." AND PLAYER_ID=".$candidate." AND LEAGUE_ID=".$_GET['league_id']);
         }
       }
       if (isset($_POST['action']) && $_POST['action'] == 'one_up') {
         $candidates = $_POST['my_candidates'];
         if (count($candidates) > 0) {
           $sql="SELECT ORDER_ID
		FROM rvs_manager_draft_candidates 
		where USER_ID=".$auth->getUserId()." 
			AND LEAGUE_ID=".$_GET['league_id']."
			AND PLAYER_ID=".$candidates[0];
  	   $db->query($sql);
           $row = $db->nextRow();
           $order = $row['ORDER_ID'];

           $sql="SELECT MAX(ORDER_ID) MORDER
		FROM rvs_manager_draft_candidates 
		where USER_ID=".$auth->getUserId()." 
			AND LEAGUE_ID=".$_GET['league_id']."
			AND ORDER_ID < ".$order;
  	   $db->query($sql);
           $row = $db->nextRow();
           $morder = $row['MORDER'];
           if (!empty($morder)) { 
             unset($sdata);
             $sdata['ORDER_ID'] =$order;
             $db->update('rvs_manager_draft_candidates', $sdata, "USER_ID=".$auth->getUserId()." AND ORDER_ID=".$morder." AND LEAGUE_ID=".$_GET['league_id']);
           } else {
             $morder = $order - 1;
           }
           foreach($candidates as $candidate) {
             unset($sdata);
             $sdata['ORDER_ID'] = $morder;
             $db->update('rvs_manager_draft_candidates', $sdata, "USER_ID=".$auth->getUserId()." AND PLAYER_ID=".$candidate." AND LEAGUE_ID=".$_GET['league_id']);
           }
         }
       }

       if (isset($_POST['action']) && $_POST['action'] == 'top') {
         $candidates = $_POST['my_candidates'];
         if (count($candidates) > 0) {

           $sql="SELECT MIN(ORDER_ID) MORDER
		FROM rvs_manager_draft_candidates 
		where USER_ID=".$auth->getUserId()." 
			AND LEAGUE_ID=".$_GET['league_id'];
  	   $db->query($sql);
           $row = $db->nextRow();
           $morder = $row['MORDER'];
           foreach($candidates as $candidate) {
             unset($sdata);
             $sdata['ORDER_ID'] = $morder-1;
             $db->update('rvs_manager_draft_candidates', $sdata, "USER_ID=".$auth->getUserId()." AND PLAYER_ID=".$candidate." AND LEAGUE_ID=".$_GET['league_id']);
           }
         }
       }

       if (isset($_POST['action']) && $_POST['action'] == 'one_down') {
         $candidates = $_POST['my_candidates'];
         if (count($candidates) > 0) {
           $sql="SELECT ORDER_ID
		FROM rvs_manager_draft_candidates 
		where USER_ID=".$auth->getUserId()." 
			AND LEAGUE_ID=".$_GET['league_id']."
			AND PLAYER_ID=".$candidates[0];
  	   $db->query($sql);
           $row = $db->nextRow();
           $order = $row['ORDER_ID'];

           $sql="SELECT MIN(ORDER_ID) MORDER
		FROM rvs_manager_draft_candidates 
		where USER_ID=".$auth->getUserId()." 
			AND LEAGUE_ID=".$_GET['league_id']."
			AND ORDER_ID > ".$order;
  	   $db->query($sql);
           $row = $db->nextRow();
           $morder = $row['MORDER'];
           if (!empty($morder)) { 
             unset($sdata);
             $sdata['ORDER_ID'] =$order;
             $db->update('rvs_manager_draft_candidates', $sdata, "USER_ID=".$auth->getUserId()." AND ORDER_ID=".$morder." AND LEAGUE_ID=".$_GET['league_id']);
           } else {
             $morder = $order + 1;
           }
           foreach($candidates as $candidate) {
             unset($sdata);
             $sdata['ORDER_ID'] = $morder;
             $db->update('rvs_manager_draft_candidates', $sdata, "USER_ID=".$auth->getUserId()." AND PLAYER_ID=".$candidate." AND LEAGUE_ID=".$_GET['league_id']);
           }
         }
       }

       if (isset($_POST['action']) && $_POST['action'] == 'bottom') {
         $candidates = $_POST['my_candidates'];
         if (count($candidates) > 0) {

           $sql="SELECT MAX(ORDER_ID) MORDER
		FROM rvs_manager_draft_candidates 
		where USER_ID=".$auth->getUserId()." 
			AND LEAGUE_ID=".$_GET['league_id'];
  	   $db->query($sql);
           $row = $db->nextRow();
           $morder = $row['MORDER'];
           foreach($candidates as $candidate) {
             unset($sdata);
             $sdata['ORDER_ID'] = $morder+1;
             $db->update('rvs_manager_draft_candidates', $sdata, "USER_ID=".$auth->getUserId()." AND PLAYER_ID=".$candidate." AND LEAGUE_ID=".$_GET['league_id']);
           }
         }
       }

     }
     $rvs_manager_user->getDraftsLists($league);
   } else {
     $smarty->assign("no_draft_list", 1);
     $rvs_manager_user->getDraftsLists($league);
   }
   $smarty->assign("league", $league->league_info);
 }

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/rvs_manager_drafts_list.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/rvs_manager_drafts_list.smarty'.($stop-$start);


  include('inc/top_very_small.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');
?>