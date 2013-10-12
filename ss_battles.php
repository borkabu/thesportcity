<?php

ini_set('display_errors', 1);
error_reporting (E_ALL  & ~E_NOTICE);

/*
===============================================================================
thanks.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows thank you message

TABLES USED: 
  - none

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');

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
include('class/inputs.inc.php');
// extras
include('class/ss_conf.inc.php');
include('ss_include.php');

// --- build content data -----------------------------------------------------
//$db->showquery = true; 

 // clear old battles
 $utils->clearOldBattles();

 $allow_create = false;
 $allow_join = false;
 $allow_escape = false;
 $allow_accept = false;
 $allow_decline = false;
 $allow_retreat = false;

//echo $sport_id;
 if (isset($_POST['sport_id'])) {
   if ($_POST['sport_id'] == 1) {
     include('class/ss_func_krep.inc.php');
   }
   else if ($_POST['sport_id'] == 2) {
     include('class/ss_func_foot.inc.php');
   }
 } else if (isset($battle_id)) {
   $db->select('ss_battle', "SPORT_ID", 'BATTLE_ID='.$battle_id);
   $row = $db->nextRow();
   if ($row['SPORT_ID'] == 1) {
     include('class/ss_func_krep.inc.php');
   }
   else if ($row['SPORT_ID'] == 2) {
     include('class/ss_func_foot.inc.php');
   }
 }

if ($auth->userOn() && $_SESSION["_user"]['SS'][0]['STAMINA'] < 75) {   
  $data['NOT_ENOUGH_STAMINA'][0]['X'] = 1;  
}
if ($auth->userOn() && $_SESSION["_user"]['SS'][0]['MONEY'] < 0) {   
  $data['NOT_ENOUGH_MONEY'][0]['X'] = 1;  
}
else{
 if ($auth->userOn()) { 
  $sql = "SELECT SB.TEAM_ID1, SB.TEAM_ID2, SB.STATUS, SB.BATTLE_TYPE, SB.SPORT_ID, SB.BATTLE_ID,
		SB.TEAM1_N, SB.TEAM2_N, SBS.STATUS AS BSTATUS,
                UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(START_DATE) AS TIMESPENT 
            FROM ss_battle SB, ss_battle_status SBS
            WHERE SB.END_DATE IS NULL 
              AND SB.BATTLE_ID=SBS.BATTLE_ID
              AND SBS.USER_ID=".$auth->getUserId();

  $db->query($sql);
  if (!$row = $db->nextRow()) {
    $allow_create = true;
    $opt['class'] = 'input';
    $opt['options'] = $sports;
    $opt2['class'] = 'input';
    $opt2['options'] = $battle_types_l;
    $sport_id = '';
    $battle_type = '';
    $data['ALLOW_CREATE'][0]['SPORTS'] = $frm->getInput(FORM_INPUT_SELECT, 'sport_id', $sport_id, $opt, $sport_id);
    $data['ALLOW_CREATE'][0]['BATTLE_TYPE'] = $frm->getInput(FORM_INPUT_SELECT, 'battle_type', $battle_type, $opt2, $battle_type);
    $allow_join = true;    
    $data['ALLOW_JOIN'][0]['X'] = 1;     
  }
  else {
   //echo $row['TIMESPENT'];
    $battle_id = $row['BATTLE_ID'];
    $team_id1 = $row['TEAM_ID1'];
    $team_id2 = $row['TEAM_ID2'];
    $status = $row['STATUS'];  
    // check things
    // if it is allowed to join
     $sql = "SELECT SBS.USER_ID, SBS.TEAM_ID
            FROM  ss_battle_status SBS
            WHERE SBS.BATTLE_ID=".$battle_id;

    $db->query($sql);
    $teams = '';
    while ($row2 = $db->nextRow()) {     
      $teams[$row2['TEAM_ID']][$row2['USER_ID']] = $row2['USER_ID'];
    }
    
//echo "<br>";
    if ($row['BATTLE_TYPE'] == 1) { // duel
      if (!isset($teams[$team_id1][$auth->getUserId()]) && count($teams[$team_id2]) == 0 && $row['STATUS'] == 0) {
        $data['ALLOW_JOIN'][0]['X'] = 1;     
        $allow_join = true;    
      } else if (isset($teams[$team_id1][$auth->getUserId()]) && isset($teams[$team_id2]) && count($teams[$team_id2]) > 0 && $row['STATUS'] == 1) {
        $data['ALLOW_ACCEPT'][0]['X'] = 1;     
        $data['ALLOW_DECLINE'][0]['X'] = 1;     
        $allow_accept = true; 
        $allow_decline = true; 
      } else if (isset($teams[$team_id1][$auth->getUserId()]) && !isset($teams[$team_id2]) && $row['STATUS'] == 0) {
        $data['ALLOW_RETREAT'][0]['X'] = 1;  
        $allow_retreat = true; 
      } else if (count($teams[$team_id1]) > 0 && isset($teams[$team_id2][$auth->getUserId()]) && $row['STATUS'] == 1) {
        $data['ALLOW_ESCAPE'][0]['X'] = 1;     
        $allow_escape = true;
      } else if ($row['STATUS'] == 2) {
        header("location: ss_battle.php");
        exit; 
      }
    }
  }

// CREATE 
   if (isset($_POST['create_battle']) && $allow_create) {
 // check that user is not in battle yet
 // create
     if ($_POST['battle_type'] == 1) {
       unset($sdata);
       $sdata['TEAM_ID1'] = 1;
       $sdata['START_DATE'] = "NOW()";
       $sdata['STATUS'] = 0;
       $sdata['BATTLE_TYPE'] = $_POST['battle_type'];
       $sdata['SPORT_ID'] = $_POST['sport_id'];
       $sdata['ROUNDS'] = $_POST['rounds'];
       $sdata['POINTS'] = $_POST['points'];
       $db->insert('ss_battle', $sdata);
       unset($sdata);
       $sdata['BATTLE_ID'] = $db->id();
       $sdata['USER_ID'] = $auth->getUserId();
       $sdata['TEAM_ID'] = 1;
       $sdata['STATUS'] = 0;
       $sdata['USER_TYPE'] = 0;
       $db->insert('ss_battle_status', $sdata);
//print_r($sdata);
//echo $db->dbNativeErrorText();
       unset($sdata);
       $allow_create = false;
       $allow_join = false;
       $allow_retreat = true;
       unset($data['ALLOW_CREATE']);
       unset($data['ALLOW_JOIN']);     
       header("location: ss_battles.php");
       exit;
     } else if ($_POST['battle_type'] == 2) {
       unset($sdata);
       $sdata['TEAM_ID1'] = 1;
       $sdata['TEAM_ID2'] = 2;
       $sdata['START_DATE'] = "NOW()";
       $sdata['STATUS'] = 2;
       $sdata['BATTLE_TYPE'] = $_POST['battle_type'];
       $sdata['SPORT_ID'] = $_POST['sport_id'];
       $sdata['ROUNDS'] = $_POST['rounds'];
       $sdata['POINTS'] = $_POST['points'];
       $db->insert('ss_battle', $sdata);
       $battle_id = $db->id();
       $battle = new SS_Battle($battle_id, $_POST['battle_type']);
       $battle->generateTeam(1, 1, 0);
       $battle->generateTeam(2, 0, 1);
       $battle->initStartPositions(0, 0, 1, 2);
       header("location: ss_battle.php");
       exit;
     } else if ($_POST['battle_type'] == 3) {
       unset($sdata);
       $sdata['TEAM_ID1'] = 1;
       $sdata['TEAM_ID2'] = 2;
       $sdata['START_DATE'] = "NOW()";
       $sdata['STATUS'] = 2;
       $sdata['BATTLE_TYPE'] = $_POST['battle_type'];
       $sdata['SPORT_ID'] = $_POST['sport_id'];
       $sdata['ROUNDS'] = $_POST['rounds'];
       $sdata['POINTS'] = $_POST['points'];
       $db->insert('ss_battle', $sdata);
       $battle_id = $db->id();
       $battle = new SS_Battle($battle_id, $_POST['battle_type']);
       $battle->generateTeam(1, 1, 1);
       $battle->generateTeam(2, 0, 2);
       $battle->initStartPositions(0, 0, 1, 2);
       header("location: ss_battle.php");
       exit;
     } else if ($_POST['battle_type'] == 4) {
       unset($sdata);
       $sdata['TEAM_ID1'] = 1;
       $sdata['TEAM_ID2'] = 2;
       $sdata['START_DATE'] = "NOW()";
       $sdata['STATUS'] = 2;
       $sdata['BATTLE_TYPE'] = $_POST['battle_type'];
       $sdata['SPORT_ID'] = $_POST['sport_id'];
       $sdata['ROUNDS'] = $_POST['rounds'];
       $sdata['POINTS'] = $_POST['points'];
       $db->insert('ss_battle', $sdata);
       $battle_id = $db->id();
       $battle = new SS_Battle($battle_id, $_POST['battle_type']);
       $battle->generateTeam(1, 1, 2);
       $battle->generateTeam(2, 0, 3);
       $battle->initStartPositions(0, 0, 1, 2);
       header("location: ss_battle.php");
       exit;
     }

   }

// JOIN

   if (isset($join_battle) && isset($battle_id) && $allow_join) {
 // check that user is not in battle yet
 // create
     unset($sdata);
     $sdata['TEAM_ID2'] = 2;
     $sdata['STATUS'] = 1;
     $db->update('ss_battle', $sdata, 'BATTLE_ID='.$battle_id);
     unset($sdata);
     $sdata['BATTLE_ID'] = $battle_id;
     $sdata['USER_ID'] = $auth->getUserId();
     $sdata['TEAM_ID'] = 2;
     $sdata['STATUS'] = 0;
     $db->insert('ss_battle_status', $sdata);

//print_r($sdata);
//echo $db->dbNativeErrorText();
     unset($sdata);
     $allow_join = false;
     $allow_create = false;
     unset($data['ALLOW_CREATE']);
     unset($data['ALLOW_JOIN']);     
     $allow_escape = true;
     header("location: ss_battles.php");
     exit;
   }

  // ACCEPT

   if (isset($accept_battle) && isset($battle_id) && $allow_accept) {
 // check that user is not in battle yet
 // create
     unset($sdata);
     $sdata['STATUS'] = 2;
     $db->update('ss_battle', $sdata, 'BATTLE_ID='.$battle_id);
     $db->select('ss_battle', "*", 'BATTLE_ID='.$battle_id);
     $row = $db->nextRow();
     unset($sdata);
     $sdata['MOVE_ID'] = 0;
     $sdata['STATUS'] = 1;
     $db->update('ss_battle_status', $sdata, "BATTLE_ID=".$battle_id);
     $battle = new SS_Battle($battle_id, $_POST['battle_type']);
     $battle->initStartPositions(0, 0, 1, 2);
//print_r($sdata);
//echo $db->dbNativeErrorText();
     unset($sdata);
     header("location: ss_battle.php");
     exit; 
   }

  // ESCAPE

   if (isset($escape_battle) && isset($battle_id) && $allow_escape) {
 // check that user is not in battle yet
 // create
     unset($sdata);
     $sdata['TEAM_ID2'] = 0;
     $sdata['STATUS'] = 0;
     $db->update('ss_battle', $sdata, 'BATTLE_ID='.$battle_id);
     $db->delete('ss_battle_status', 'BATTLE_ID='.$battle_id." AND USER_ID=".$auth->getUserId());
//print_r($sdata);
//echo $db->dbNativeErrorText();
     unset($sdata);
     $allow_create = true;
     $data['ALLOW_CREATE'][0]['X'] = 1;
     $allow_join = true;    
     $data['ALLOW_JOIN'][0]['X'] = 1;     
     $allow_escape = false;
     header("location: ss_battles.php");
     exit;
   }

   // DECLINE
   if (isset($decline_battle) && isset($battle_id) && $allow_decline) {
 // check that user is not in battle yet
 // create
     unset($sdata);
     $sdata['USER_ID2'] = 0;
     $sdata['STATUS'] = 0;
     $db->update('ss_battle', $sdata, 'BATTLE_ID='.$battle_id);
     $db->delete('ss_battle_status', 'BATTLE_ID='.$battle_id." AND USER_ID=".$auth->getUserId());
//print_r($sdata);
//echo $db->dbNativeErrorText();
     unset($sdata);
     $allow_decline = false;     
     $allow_accept = false;
     header("location: ss_battles.php");
   }

   // RETREAT
   if (isset($_POST['retreat_battle']) && isset($_POST['battle_id']) && $allow_retreat) {
 // check that user is not in battle yet
 // create
     $db->delete('ss_battle', 'BATTLE_ID='.$_POST['battle_id']);
     $db->delete('ss_battle_status', 'BATTLE_ID='.$_POST['battle_id']);
//print_r($sdata);
//echo $db->dbNativeErrorText();
     unset($sdata);
    $allow_create = true;
    $data['ALLOW_CREATE'][0]['X'] = 1;
    $allow_join = true;    
    $data['ALLOW_JOIN'][0]['X'] = 1;   
    header("location: ss_battles.php");  
     exit;
   }

   
  }
 }

// show list of battles

  $sql = "SELECT SB.*, U.USER_NAME, SBS.TEAM_ID, SBS.USER_ID, SU.EQUIPED_LEVEL,
                       UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(START_DATE) AS TIMESPENT
          FROM ss_battle SB
           LEFT JOIN ss_battle_status SBS on SB.BATTLE_ID=SBS.BATTLE_ID
           LEFT JOIN users U on U.USER_ID = SBS.USER_ID
           LEFT JOIN ss_users SU on SU.USER_ID = SBS.USER_ID
           WHERE SB.END_DATE IS NULL AND (SB.STATUS=1 OR SB.STATUS=0)";
  $db->query($sql);
//echo $sql;
  $c = 0;
  while ($row = $db->nextRow()) {
    $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']] = $row;
    $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['ROUNDS'] = $row['ROUNDS'] + 1;
    $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['SPORT'] = $sports[$row['SPORT_ID']];
    $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['BATTLE_TYPE'] = $battle_types[$row['BATTLE_TYPE']];
    $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['TEAM'.$row['TEAM_ID']][$row['TEAM_ID']]['USERS'][$c]['USER_NAME'] = $row['USER_NAME'];
    $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['TEAM'.$row['TEAM_ID']][$row['TEAM_ID']]['USERS'][$c]['EQUIPED_LEVEL'] = $row['EQUIPED_LEVEL'];
    $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['TEAM'.$row['TEAM_ID']][$row['TEAM_ID']]['USERS'][$c]['USER_ID'] = $row['USER_ID'];
    $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['TIMELEFT'] = $battle_timeout - $row['TIMESPENT'];
    if ($allow_join && $auth->userOn()) {
      $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['ALLOW_JOIN'][0]['BATTLE_ID'] = $row['BATTLE_ID'];
    } 
    else unset($data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['ALLOW_JOIN']);
    if ($allow_escape && $auth->userOn()) {
      $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['ALLOW_ESCAPE'][0]['BATTLE_ID'] = $row['BATTLE_ID'];
    } 
    else unset($data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['ALLOW_ESCAPE']);
    if ($allow_decline && $auth->userOn()) {
      $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['ALLOW_DECLINE'][0]['BATTLE_ID'] = $row['BATTLE_ID'];
    } 
    else unset($data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['ALLOW_DECLINE']);
    if ($allow_retreat && $auth->userOn() && $row['BATTLE_ID'] == $battle_id) {
      $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['ALLOW_RETREAT'][0]['BATTLE_ID'] = $row['BATTLE_ID'];
    } 
    else unset($data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['ALLOW_RETREAT']);
    if ($allow_accept && $auth->userOn()) {
      $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['ALLOW_ACCEPT'][0]['BATTLE_ID'] = $row['BATTLE_ID'];
    } 
    else unset($data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['ALLOW_ACCEPT']);
    if ($row['USER_ID'] == $auth->getUserId()) {
      $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['WAITING'][0]['X'] = 1;
    }
    if (!$auth->userOn()) {
      $data['BATTLES'][0]['ITEMS'][$row['BATTLE_ID']]['LOGIN'][0]['X'] = 1;
    }
  
    $c++;
   
  }
  $db->free();

  $data['CUR_BATTLES'] = $battlebox->getCurrentBattlesBox(1, 10);

  unset($sdata);
  $utils->setLocation(SS_PLAYGROUND);


$tpl->setTemplateFile('tpl/ss_battles.tpl.html');  
$tpl->addData($data);
$content = $tpl->parse();
// ----------------------------------------------------------------------------

// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

// close connections
include('class/db_close.inc.php');

?>