<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);

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
 if (!isset($_GET['battle_id']) && !$auth->userOn()) {
    header("location: ss_battles.php");  
    exit;
 }

// $db->showquery = true;
 
 $attack = 1;
 $defence = 2;
 $battle_data = '';

 if ($auth->userOn()) {
   $move_id1 = 0;
   $sql = "SELECT SB.*, SBS.MOVE_ID, SBS.BATTLE_ID, SBS.STATUS AS BSTATUS
             FROM ss_battle_status SBS, ss_battle SB
           WHERE SBS.USER_ID=".$auth->getUserId()." 
                 AND SBS.STATUS IN (1, 3)
                 AND SB.BATTLE_ID = SBS.BATTLE_ID
           GROUP BY SBS.BATTLE_ID
           ORDER BY SBS.BATTLE_ID DESC LIMIT 1";
   $db->query($sql);
   if ($row = $db->nextRow()) {
     if (!empty($row['MOVE_ID']))
       $move_id1 = $row['MOVE_ID'];
     $battle_id = $row['BATTLE_ID'];
     $battle_data = $row;
     $moves = $row['MOVES'];
     $data['POINTS'] = $row['POINTS'];
     $data['ROUNDS'] = $row['ROUNDS'] + 1;
   }
   $db->free();
   $data['BATTLE_ID'] = $battle_id;
 }

 if (!empty($_GET['battle_id']) && !$auth->userOn()) {
    $battle_id = $_GET['battle_id'];
 } 

 $battle = new SS_Battle($battle_id);
 if (!empty($_GET['battle_id']) && !$auth->userOn()) {
   $battle_data = $battle->getBattle();
 }
 $current_user = $battle->getCurrentUser();
   // get all data current user?

   if ($current_user['BALL'] == 1) {
       $attack = $current_user['TEAM_ID'];
//echo "attack".$attack;
       $defence = 3 - $current_user['TEAM_ID'];
   }

   $current_user = $battle->getCurrentUser();
//print_r($battle_data);

// includes
   if ($battle_data['SPORT_ID'] == 1) {
     include('class/ss_func_krep.inc.php');
     include('class/ss_lang_krep_'.$_SESSION["_lang"].'.inc.php');
   } else if ($battle_data['SPORT_ID'] == 2) {
     include('class/ss_func_foot.inc.php');
     include('class/ss_lang_foot_'.$_SESSION["_lang"].'.inc.php');
   }

   $other_users = $battle->getOtherUsers($moves);   
   for ($i = 0; $i < count($other_users); $i++) {
     if ($other_users[$i]['BALL'] == 1) {
       $attack = $other_users[$i]['TEAM_ID'];
       $defence = 3 - $other_users[$i]['TEAM_ID']; 
     }
   }

//exit;
 
 $total_moves = 0;

//echo $current_user;
  $sdata='';
 if (isset($current_user)) {
//echo 3;
   $battle_id = $current_user['BATTLE_ID'];
   $attempts = $current_user['ATTEMPTS'];
   $data['RESULT1'] = $current_user['RESULT1'];
   $data['RESULT2'] = $current_user['RESULT2'];
   $max_attempts = $current_user['ROUNDS'];
   $points = $current_user['POINTS'];

   $game_over_condition = false;
//echo "mx:".(($max_attempts-$attempts)*3)." ".abs($current_user['RESULT1'] - $current_user['RESULT2']);
   if ($max_attempts <= $attempts || $points <= $current_user['RESULT1'] || $points <= $current_user['RESULT2'] ) {
     $game_over_condition = true;
   }

   if ($attempts > $max_attempts + 1 || $current_user['BSTATUS'] == 3) {
     header("location: ss_battles.php");  
     exit;
   }
   else if ($battle_data['BSTATUS'] != 3 && ($attempts == $max_attempts + 1 || $game_over_condition)) {
     //game over
     unset($sdata);
     $data['GAME_OVER'][0] = $row;
     if (($current_user['RESULT1'] > $battle_data['RESULT2'] && 
          $current_user['USER_ID'] == $auth->getUserId() &&
          $current_user['TEAM_ID'] == 1 ) ||
         ($current_user['RESULT1'] < $current_user['RESULT2'] && 
          $current_user['USER_ID'] == $auth->getUserId() &&
          $current_user['TEAM_ID'] == 2)) {
        unset($sdata);
        $sdata['USER_ID'] = $auth->getUserId();
        $sdata['EVENT_TYPE'] = 4;
        $sdata['EVENT_DATE'] = 'SYSDATE()';
        $sdata['STATUS'] = 0;
        $sdata['MONEY'] = $level_prize[$_SESSION['_user']['SS'][0]['LEVEL']];
        $db->insert("ss_users_log", $sdata);   
        unset($sdata);
       $data['GAME_OVER'][0]['WIN'][0]['PRIZE'] = $level_prize[$_SESSION['_user']['SS'][0]['LEVEL']];
       $sdata['MONEY'] = $_SESSION['_user']['SS'][0]['MONEY'] + $level_prize[$_SESSION['_user']['SS'][0]['LEVEL']];
       $sdata['WON'] = $_SESSION['_user']['SS'][0]['WON']+1;
     } else if (($current_user['RESULT1'] < $battle_data['RESULT2'] && 
                 $current_user['USER_ID'] == $auth->getUserId() &&
                 $current_user['TEAM_ID'] == 1 ) ||
                ($current_user['RESULT1'] > $current_user['RESULT2'] && 
                 $current_user['USER_ID'] == $auth->getUserId() &&
                 $current_user['TEAM_ID'] == 2)) {
        unset($sdata);
        $sdata['USER_ID'] = $auth->getUserId();
        $sdata['EVENT_TYPE'] = 5;
        $sdata['EVENT_DATE'] = 'SYSDATE()';
        $sdata['STATUS'] = 0;
        $sdata['MONEY'] = $level_fine[$_SESSION['_user']['SS'][0]['LEVEL']];
        $db->insert("ss_users_log", $sdata);   
        unset($sdata);
       $data['GAME_OVER'][0]['LOST'][0]['PRIZE'] = $level_fine[$_SESSION['_user']['SS'][0]['LEVEL']];
       $sdata['MONEY'] = $_SESSION['_user']['SS'][0]['MONEY'] - $level_fine[$_SESSION['_user']['SS'][0]['LEVEL']];
       $sdata['LOST'] = $_SESSION['_user']['SS'][0]['LOST']+1;
     } else if ($current_user['RESULT1'] == $current_user['RESULT2']) {
        unset($sdata);
        $sdata['USER_ID'] = $auth->getUserId();
        $sdata['EVENT_TYPE'] = 6;
        $sdata['EVENT_DATE'] = 'SYSDATE()';
        $sdata['STATUS'] = 0;
        $sdata['MONEY'] = 0;
        $db->insert("ss_users_log", $sdata);   
        unset($sdata);
       $data['GAME_OVER'][0]['DRAW'][0]['X'] = 1;
       $sdata['MONEY'] = $_SESSION['_user']['SS'][0]['MONEY'];
       $sdata['DRAW'] = $_SESSION['_user']['SS'][0]['DRAW']+1;
     }
     $db->update('ss_users', $sdata, "USER_ID=".$auth->getUserId());
     $battle->endBattle();

     $game_over = true;
   }
   else $game_over = false;

//echo "game over: ".$game_over;
     if (!empty($current_user['MOVE_ID']))
       $total_moves = $current_user['MOVE_ID'];
  
     if ($auth->userOn() && !$game_over) {
       // make move
//echo "make_move";
      $data2 = $battle->makeMove($current_user, $other_users, $total_moves, $attack, $defence);
      if (isset($data2['RELOAD']) && $data2['RELOAD'] == true) {
        header("location: ss_battle.php");  
        exit;
      }         
      $data['CONTROL'][0]['TIMELEFT'] = $data2['TIMELEFT']; 
      $data['CONTROL'][0]['TIMELEFT_VISIBILITY'] = $data2['TIMELEFT_VISIBILITY'];
      if (isset($data2['TOTAL_MOVES']))
        $total_moves = $data2['TOTAL_MOVES']; 

      // check if we are waiting
      $waiting = $battle->areWeWaiting();
      // draw table
      $data1 = drawBoard($current_user, $other_users, $waiting, $attack, $defence, $battle_data['BATTLE_TYPE']);
      $data['BOARD'][0]['ROWS'] = isset($data1['ROWS']) ? $data1['ROWS'] : 0 ;
      $data['BOARD'][0]['FIELD'] = $data1['FIELD'];
      $data['BOARD'][0]['WIDTH'] = $data1['WIDTH'];
      $data['BOARD'][0]['HEIGHT'] = $data1['HEIGHT'];
      $data['TIMER'][0]['X'] = 1;
      if (isset($data1['WAITING']))
        $data['CONTROL'][0]['WAITING'] = $data1['WAITING'];
      if (isset($data1['ATTACK'])) {
        if ($battle_data['SPORT_ID'] == 1) {
          $data['CONTROL'][0]['BASKETBALL'][0]['ATTACK'] = $data1['ATTACK'];
        } else if ($battle_data['SPORT_ID'] == 2) {
          $data['CONTROL'][0]['FOOTBALL'][0]['ATTACK'] = $data1['ATTACK'];
        } 

      }
      if (isset($data1['DEFENSE'])) {
        if ($battle_data['SPORT_ID'] == 1) {
          $data['CONTROL'][0]['BASKETBALL'][0]['DEFENSE'] = $data1['DEFENSE'];
        } else if ($battle_data['SPORT_ID'] == 2) {
          $data['CONTROL'][0]['FOOTBALL'][0]['DEFENSE'] = $data1['DEFENSE'];
        }
      }
  
      $sql = "SELECT SU.*
                FROM ss_users SU
               WHERE SU.USER_ID=".$auth->getUserId(); 
  
      $db->query($sql);       
      $data['CHARS'][0]['USER_CHAR'][0] = $db->nextRow();
  
       // create table
     } else { // viewer mode
  
  
     }  

  // get protocol 
  $protocol = new SS_Battle_Protocol($langs, $_SESSION['_lang']);
  $data['PROTOCOL'] = $protocol->getBattleProtocolBox($battle_id, 1, 2, $data['RESULT1'], $data['RESULT2'], 300);

  // get inventory
  if (isset($data1['ATTACK']) || isset($data1['DEFENSE'])) {
    $data['INVENTORY'][0]['EMPTY'][0]=1;
    $data['CONTROL'][0]['INVENTORY'][0]['ROWS'] = $utils->getInventory($auth->getUserId(), 1, 0);
  }
 }
 else {
//echo 1;
    header("location: ss_battles.php");  
    exit;
 }
 $utils->setLocation(SS_BATTLE);

$tpl->setTemplateFile('tpl/ss_battle.tpl.html');
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