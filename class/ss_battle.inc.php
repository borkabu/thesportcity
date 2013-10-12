<?php

class SS_Battle {
  var $battle_id;
  var $battle_type;

  function SS_Battle($battle_id, $battle_type='') {
    $this->battle_id = $battle_id;
    $this->battle_type = $battle_type;
  }

  function setBattleType($battle_type) {
    $this->battle_type = $battle_type;
  }

  function getCurrentUser() {
    global $db;
    global $auth;

    $current_user = '';
   // get all data current user?
    $sql = "SELECT U.USER_NAME, SB.BATTLE_ID, SB.BATTLE_TYPE, SB.TEAM_ID1, SB.TEAM_ID2, SB.MOVES, SB.ATTEMPTS, SB.RESULT1, SB.RESULT2, SB.STATUS, SBS.BALL, SB.ROUNDS, SB.POINTS,
                  SBS.COORDX, SBS.COORDY, SBE.MOVE_ID,  
                  UNIX_TIMESTAMP( NOW( ) ) - UNIX_TIMESTAMP( SB.LAST_MOVE_START ) AS TIMEOUT_CHECK,
                  UNIX_TIMESTAMP(SBE.MOVE_DATE) - UNIX_TIMESTAMP(SB.LAST_MOVE_START) AS MOVE_DATE,
                  SBS.STATUS as BSTATUS, SBS.USER_ID, SBS.TEAM_ID, SBE.COORDX AS CURX, SBE.COORDY AS CURY, SBE.STATUS
           FROM users U, ss_battle SB, ss_battle_status SBS
              LEFT JOIN ss_battle_events SBE ON SBE.USER_ID=".$auth->getUserId()." 
                                             AND SBE.BATTLE_ID=".$this->battle_id."
                                             AND SBE.MOVE_ID=SBS.MOVE_ID
           WHERE SBS.BATTLE_ID = ".$this->battle_id." 
                 AND SBS.USER_ID=".$auth->getUserId()." 
                 AND U.USER_ID = SBS.USER_ID
                 AND SBS.USER_TYPE=0
                 AND SBS.BATTLE_ID=SB.BATTLE_ID 
           ORDER BY SBE.MOVE_ID DESC";
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $current_user = $row;
    }
    return $current_user;
  }
  
  function getOtherUsers($moves) {
    global $db;
    global $auth;

    $other_user = '';

    // get all data for other users?
    $sql = "SELECT SB.BATTLE_ID, SB.BATTLE_TYPE, SB.TEAM_ID1, SB.TEAM_ID2, SB.MOVES, SB.ATTEMPTS, 
                  SB.RESULT1, SB.RESULT2, SB.STATUS, SBS.BALL, 
                  SBS.COORDX, SBS.COORDY, SBS.USER_TYPE, SBE.MOVE_ID, 
                  UNIX_TIMESTAMP( NOW( ) ) - UNIX_TIMESTAMP( SB.LAST_MOVE_START ) AS TIMEOUT_CHECK,
                  UNIX_TIMESTAMP(SBE.MOVE_DATE) - UNIX_TIMESTAMP(SB.LAST_MOVE_START) AS MOVE_DATE,
                  SBS.STATUS as BSTATUS, SBS.USER_ID, SBS.TEAM_ID, SBE.COORDX AS CURX, SBE.COORDY AS CURY
           FROM ss_battle SB, ss_battle_status SBS
              LEFT JOIN ss_battle_events SBE ON SBE.USER_ID=SBS.USER_ID
                                             AND SBE.USER_TYPE=SBS.USER_TYPE
                                             AND SBE.BATTLE_ID=".$this->battle_id."
                                             AND SBE.MOVE_ID=".$moves."
           WHERE SBS.BATTLE_ID = ".$this->battle_id." 
                 AND ((SBS.USER_ID<>".$auth->getUserId()." 
                 AND SBS.USER_TYPE = 0) OR SBS.USER_TYPE = 1)
                 AND SBS.BATTLE_ID=SB.BATTLE_ID 
           ORDER BY SBE.MOVE_ID DESC";
    $db->query($sql);

    $c = 0;
    $oids ='';
    $pre ='';
    while ($row = $db->nextRow()) {
      $other_users[$c] = $row;
      if ($row['USER_TYPE'] == 0) {
        $oids = $pre.$row['USER_ID'];
        $pre = ',';
      }
      $c++;
    }
    if ($oids != '') {
      $sql = "SELECT USER_NAME, USER_ID FROM 
              users U WHERE USER_ID IN (".$oids.")";
      $db->query($sql);
      while ($row = $db->nextRow()) {
        for ($i =0; $i< $c; $i++) {
          if ($other_users[$i]['USER_ID'] == $row['USER_ID'])
            $other_users[$i]['USER_NAME'] = $row['USER_NAME'];
        }
      }
    }  

    for ($i =0; $i< $c; $i++) {
      if (!isset($other_users[$i]['USER_NAME']))
        $other_users[$i]['USER_NAME'] = 'BOT'.$other_users[$i]['USER_ID'];
    } 

    return $other_users;
  }


  function isGameOverCondition() {

  }

  function endBattle() {
     global $db;
     global $auth;

     unset($sdata);
     $sdata['END_DATE'] = "NOW()";
     $sdata['STATUS'] = 3;
     $db->update('ss_battle', $sdata, "BATTLE_ID=".$this->battle_id);
     unset($sdata);
     $sdata['STATUS'] = 3;
     $db->update('ss_battle_status', $sdata, "BATTLE_ID=".$this->battle_id." AND USER_ID=".$auth->getUserId());

  }

  function areWeWaiting() {
    global $db;
    global $auth;

    $waiting = true;
    
    $sql = "SELECT SBS.USER_ID
              FROM ss_battle_status SBS, ss_battle SB
             WHERE SBS.MOVE_ID <= SB.MOVES
                   AND SB.BATTLE_ID = SBS.BATTLE_ID
                   AND SB.BATTLE_ID=".$this->battle_id."
                   AND SBS.USER_TYPE=0
                   AND SBS.USER_ID=".$auth->getUserId();
    $db->query($sql);
    if ($row = $db->nextRow()) {
      return false;
    }

    if ($waiting) {
        $sql = "SELECT COUNT(*) NOTMOVED 
                  FROM ss_battle_status SBS, ss_battle SB
                 WHERE SB.MOVES <> SBS.MOVE_ID
                       AND SB.BATTLE_ID = SBS.BATTLE_ID
                       AND SB.BATTLE_ID=".$this->battle_id;
        $db->query($sql);
        $row = $db->nextRow();
        if ($row['NOTMOVED'] == 0)
          $waiting = false;
    }
    return $waiting;
  }

  function getBattle() {
    global $db;

    $db->select("ss_battle", "*", "BATTLE_ID=".$this->battle_id);
    if ($row = $db->nextRow()) {
      $battle_data = $row;
    }    
    return $battle_data;
  }

 function makeMove($current_user, $other_users, $total_moves, $attack, $defence) {
   global $db;
   global $duel_timeout;
   global $_SESSION;
   global $auth;
   global $_POST;
   global $fields;
   global $actions;
   global $properties;
   global $fatigue_attack_action_effect;
   global $fatigue_defense_action_effect;
   global $defense_action_basic_value;
   global $attack_action_basic_value;

   $handle = fopen("ss_log", "a+b");
// insert battle events
  // get move of current user
   $move_id1 = $current_user['MOVE_ID'];

   $move_id2 = $move_id1;  
   $move_date = 1000000;
   foreach ($other_users as $ouser) {
   // find last move
    if ($move_id1 > $ouser['MOVE_ID'])
      $move_id2 = $ouser['MOVE_ID'];
    $move_date  = $ouser['TIMEOUT_CHECK'];
   }

   if ($move_id1 > $move_id2) {
//echo "..x.";
     $data['TIMELEFT'] = $duel_timeout - $current_user['TIMEOUT_CHECK']; 
     if ($data['TIMELEFT'] <= 0)
       $data['TIMELEFT'] = 10;
     $data['TIMELEFT_VISIBILITY'] = "visible";
     $waiting = true;
   } 
   else if ($move_id1 == $move_id2) {
//echo "..y.";
     if ($move_date < $current_user['MOVE_DATE'])
       $data['TIMELEFT'] = $duel_timeout - $current_user['MOVE_DATE']; 
     else $data['TIMELEFT'] = $duel_timeout - $move_date; 
     if ($data['TIMELEFT'] <= 0)
       $data['TIMELEFT'] = 10;
     $data['TIMELEFT_VISIBILITY'] = "visible";
     $waiting = false;
   }
   else if ($move_id1 < $move_id2) {
//echo "..z.";
     $data['TIMELEFT'] = $duel_timeout - $current_user['MOVE_DATE']; 
     if ($data['TIMELEFT'] <= 0)
       $data['TIMELEFT'] = 10;
     $data['TIMELEFT_VISIBILITY'] = "visible";
     $waiting = false;
   }

   $data['WAITING'] = $waiting;
   //echo $waiting." ".$current_user['MOVE_ID']." ".$move_id2;

   $scores = $this->getScores();

   if ($this->battle_type == 2 || $this->battle_type == 3 || $this->battle_type == 4) {
     $this->makeBotMoves($current_user, $other_users, $total_moves, $attack, $defence);
   }

   $data['HANDLE_TIMEOUT'][0] = $this->handleTimeout();
   if ($data['HANDLE_TIMEOUT'][0]['OUTCOME'] == true) {
     $data['POST'][0] = $this->postMovesUpdates($attack, $defence, $data['HANDLE_TIMEOUT'][0]['PENALTY'][1], $data['HANDLE_TIMEOUT'][0]['PENALTY'][2]);
     $data['RELOAD'] = 1;
     fclose($handle);
     return $data;
   }

   if (isset($_POST['move']) && !$waiting) {
     $sql = "SELECT MAX(MOVE_ID) MOVES FROM ss_battle_events 
                                 WHERE USER_ID=".$auth->getUserId()." 
                                       AND USER_TYPE=0
                                       AND BATTLE_ID=".$this->battle_id;
     $db->query($sql);       
     if ($row2 = $db->nextRow()) {
       $total_moves = $row2['MOVES'];
     }       

     $coordx = $current_user['COORDX'];
     $coordy = $current_user['COORDY'];

//     fwrite($handle, "stage1: ". $coordx." ".$coordy."\n");
     // convert field to coords
     $coordx += $fields[$_POST['field']][0];
     $coordy += $fields[$_POST['field']][1];               

//     fwrite($handle, "stage2: ". $coordx." ".$coordy."\n");
     // insert data into table  
     unset($sdata);
     $sdata['BATTLE_ID'] = $this->battle_id;
     $sdata['USER_ID'] = $auth->getUserId();
     $sdata['COORDX'] = $coordx;
     $sdata['COORDY'] = $coordy;
     $sdata['MOVE_ID'] = $total_moves + 1;
     $sdata['MOVE_DATE'] = "NOW()"; 
     $sdata['ATTEMPT'] = $scores['ATTEMPTS'];
     $total_moves++;
     $data['TOTAL_MOVES'] = $total_moves;
     if (isset($_POST['attack'])) {
       $pos = strpos($_POST['attack'], '_');
       if ($pos > 0) {
         $att = substr($_POST['attack'], 0, $pos);
         $sdata['ACTION'] = $actions['attack'][$att];
         if ($att == 'pass') {          
           $sdata['PASS_USER'] = substr($_POST['attack'], $pos+1);
	   $_POST['attack'] = 'pass';
         }
       }
       else {
        $sdata['ACTION'] = $actions['attack'][$_POST['attack']];
       }
//echo $att;
     }
     else if (isset($_POST['defense'])) {
         $sdata['ACTION'] = $actions['defense'][$_POST['defense']];
     }
     else {
         $sdata['ACTION'] = -1;   
     }
     $sdata['REACTION'] = 0;
     $sdata['STATUS'] = $current_user['STATUS'];
//$db->showquery = true;
     $db->insert('ss_battle_events', $sdata);
     unset($sdata); 
     $sdata['BATTLE_ID'] = $this->battle_id;
     $sdata['USER_ID'] = $auth->getUserId();
     $sdata['MOVE_ID'] = $total_moves;
     $sdata['COORDX'] = $coordx;
     $sdata['COORDY'] = $coordy;
     $sdata['MOVED'] = $_POST['field'];
     if (isset($_POST['attack']) && $actions['attack'][$_POST['attack']])  
       $sdata['ATTACK'] = $actions['attack'][$_POST['attack']];
     else $sdata['ATTACK'] =  -1;
     if (isset($_POST['defense']) && isset($actions['defense'][$_POST['defense']]))  
       $sdata['DEFENSE'] = $actions['defense'][$_POST['defense']];
     else $sdata['DEFENSE'] = -1;
     $db->update('ss_battle_status', $sdata, "USER_ID=".$auth->getUserId()." AND BATTLE_ID=".$this->battle_id);
     unset($sdata);
     $sdata['LAST_MOVE'] = "NOW()"; 
     $db->update('ss_battle', $sdata, "BATTLE_ID=".$this->battle_id);

     $data['POST'][0] = $this->postMovesUpdates($attack, $defence);
     $data['RELOAD'] = 1;
   }
   else if (isset($_POST['use']) && isset($_POST['id']) && !$waiting) {
     $sql = "SELECT SUI.GENERAL_ID, SU.ITEM_ID, SU.ACTION_VALUE, SU.PROP_AFFECTED 
           FROM ss_users_items SUI, ss_items SU
          WHERE SUI.USER_ID=".$auth->getUserId()." 
                AND SUI.EQUIPED=1 
                AND SUI.ITEM_ID=SU.ITEM_ID
                AND SUI.GENERAL_ID=".$_POST['id'];

     $db->query($sql);       
     if ($row = $db->nextRow()) {
       // item exists
         unset($sdata);
         if ($row['ACTION_VALUE'] > 0) {
           if ($_SESSION['_user']['SS'][0][$properties[$row['PROP_AFFECTED']]] + $row['ACTION_VALUE'] < 100)
             $sdata[$properties[$row['PROP_AFFECTED']]] = $_SESSION['_user']['SS'][0][$properties[$row['PROP_AFFECTED']]] + $row['ACTION_VALUE'];
           else $sdata[$properties[$row['PROP_AFFECTED']]] = 100;
         } else if ($row['ACTION_VALUE'] < 0) {
           if ($_SESSION['_user']['SS'][0][$properties[$row['PROP_AFFECTED']]] + $row['ACTION_VALUE'] > 0)
             $sdata[$properties[$row['PROP_AFFECTED']]] = $_SESSION['_user']['SS'][0][$properties[$row['PROP_AFFECTED']]] + $row['ACTION_VALUE'];
           else $sdata[$properties[$row['PROP_AFFECTED']]] = 0;
         }
         $db->update("ss_users" , $sdata, "USER_ID=".$auth->getUserId());
         $db->delete("ss_users_items", "GENERAL_ID=".$_POST['id']);
     }       
   }
   fclose($handle);
   return $data;
 }


 function makeBotMoves($current_user, $other_users, $total_moves, $attack, $defence) {
   global $db;
   global $duel_timeout;
   global $fields;
   global $actions;
   global $fatigue_attack_action_effect;
   global $fatigue_defense_action_effect;
   global $defense_action_basic_value;
   global $attack_action_basic_value;
   global $basket_coord;

   $data = '';
   $handle = fopen("ss_log", "a+b");
// insert battle events
  // get move of current user
   $move_date = 1000000;
   foreach ($other_users as $ouser) {
    if ($move_date > $ouser['MOVE_DATE'])
      $move_date  = $ouser['MOVE_DATE'];
   }

   $scores = $this->getScores();

   // get ball coords - defence
   $sql = "SELECT COORDX, COORDY FROM ss_battle_status 
                                 WHERE BALL=1 AND BATTLE_ID=".$this->battle_id;
   $db->query($sql);       
   if ($row2 = $db->nextRow()) {
     $ball_coordx = $row2['COORDX'];
     $ball_coordy = $row2['COORDY'];
   }       
    
   // get basket coords - attack
   // x = 5, y = 3

   foreach ($other_users as $ouser) {
//echo "botmove".$total_moves;
     $sql = "SELECT MAX(MOVE_ID) MOVES FROM ss_battle_events 
                                 WHERE USER_ID=".$ouser['USER_ID']." 
                                       AND USER_TYPE=1
                                       AND BATTLE_ID=".$this->battle_id;
     $db->query($sql);       
     if ($row2 = $db->nextRow()) {
       $cur_moves = $row2['MOVES'];
     }       
//echo "botmove".$total_moves;
    if ($total_moves == $cur_moves) {
     $sql = "SELECT SBS.*
               FROM ss_battle_status SBS
             WHERE SBS.USER_ID=".$ouser['USER_ID']." 
                   AND SBS.USER_TYPE=1
                   AND SBS.BATTLE_ID=".$this->battle_id;

     $db->query($sql);       
//     if ($row2 = $db->nextRow()) {
//       $status = $row2['STATUS'];
  //   }       
     if ($ouser['TEAM_ID'] == $attack)
       $status = 1;
     else $status = 2;

     $coordx = $ouser['COORDX'];
     $coordy = $ouser['COORDY'];

     fwrite($handle, "stage1: ". $coordx." ".$coordy."\n");
     // convert field to coords
     // find best fields to move...

     fwrite($handle, "stage2: ". $coordx." ".$coordy."\n");
     // insert data into table  

     unset($sdata);
 /// ============ AI ====================
     if ($ouser['TEAM_ID'] == $attack) {
       $sdata = $this->AIAttack($current_user, $other_users, $ouser, $ball_coordx, $ball_coordy, $attack, $defence);
       $coordx = $sdata['COORDX'];
       $coordy = $sdata['COORDY'];
     } 
     else if ($ouser['TEAM_ID'] == $defence) {
       $sdata = $this->AIDefense($current_user, $other_users, $ouser, $ball_coordx, $ball_coordy, $attack, $defence);
       $coordx = $sdata['COORDX'];
       $coordy = $sdata['COORDY'];
     }

     $sdata['BATTLE_ID'] = $this->battle_id;
     $sdata['USER_ID'] = $ouser['USER_ID'];
     $sdata['USER_TYPE'] = 1;
     $sdata['MOVE_ID'] = $total_moves+1;
     $sdata['MOVE_DATE'] = "NOW()"; 
     $sdata['ATTEMPT'] = $scores['ATTEMPTS'];
     $data['TOTAL_MOVES'] = $total_moves+1;
     $sdata['REACTION'] = 0;
     $sdata['STATUS'] = $status;
     $sdata['COORDX'] = $coordx;
     $sdata['COORDY'] = $coordy;

//$db->showquery = true;
//print_r($sdata);
     $db->insert('ss_battle_events', $sdata);
//echo $db->getNativeErrorText();
     unset($sdata); 
     $sdata['BATTLE_ID'] = $this->battle_id;
     $sdata['USER_ID'] = $ouser['USER_ID'];
     $sdata['USER_TYPE'] = 1;
     $sdata['MOVE_ID'] = $total_moves+1;
     $sdata['COORDX'] = $coordx;
     $sdata['COORDY'] = $coordy;
     $sdata['MOVED'] = -2;
     if ($ouser['TEAM_ID'] == $attack) {
       $sdata['ATTACK'] = 1;
     } 
     else $sdata['ATTACK'] =  0;

     if ($ouser['TEAM_ID'] == $defence) {
       $sdata['DEFENSE'] = 2;
     }
     else $sdata['DEFENSE'] = 0;

     $db->update('ss_battle_status', $sdata, "USER_ID=".$ouser['USER_ID']." AND USER_TYPE=1 AND BATTLE_ID=".$this->battle_id);
//echo $db->getNativeErrorText();
     unset($sdata);
     $sdata['LAST_MOVE'] = "NOW()"; 
     $db->update('ss_battle', $sdata, "BATTLE_ID=".$this->battle_id);
    }
   }
   fclose($handle);
   return $data;
 }

 function getMovesCount() {
   global $db;
   $sql="SELECT MOVES FROM ss_battle
             WHERE BATTLE_ID = ".$this->battle_id;
   $db->query($sql);
   $row = $db->nextRow();
   return $row['MOVES'];
 }

 function getNotmovedCount() {
  global $db;
  $sql = "SELECT COUNT(*) NOTMOVED 
            FROM ss_battle_status SBS, ss_battle SB
           WHERE SB.MOVES = SBS.MOVE_ID
                 AND SB.BATTLE_ID = SBS.BATTLE_ID
                 AND SB.BATTLE_ID=".$this->battle_id;
  $db->query($sql);
  $row = $db->nextRow();
  return $row['NOTMOVED'];
 }

 function getScores() {
   global $db;
   $db->select('ss_battle', "RESULT1, RESULT2, ATTEMPTS, BATTLE_TYPE", "BATTLE_ID=".$this->battle_id);
   $row = $db->nextRow();
   $this->battle_type = $row['BATTLE_TYPE'];
   return $row;
 }

 function handleTimeout() {
   global $duel_timeout;
   global $db;

   $sdata = '';
   $penalty[1] = 0;
   $penalty[2] = 0;

   $moves = $this->getMovesCount();

//$db->showquery=true;
   $sql = "SELECT SB.BATTLE_ID, SB.BATTLE_TYPE, SB.TEAM_ID1, SB.TEAM_ID2, SB.MOVES, 
                  SB.ATTEMPTS, SB.RESULT1, SB.RESULT2, SB.STATUS, SBS.BALL,
                  SBS.COORDX, SBS.COORDY, SBE.MOVE_ID, SBE1.STATUS AS ESTATUS,
                  UNIX_TIMESTAMP( NOW( ) ) - UNIX_TIMESTAMP( SB.LAST_MOVE_START ) AS TIMEOUT_CHECK,
                  UNIX_TIMESTAMP(SBE.MOVE_DATE) - UNIX_TIMESTAMP(SB.LAST_MOVE_START) AS MOVE_DATE,
                  SBS.STATUS as BSTATUS, SBS.USER_ID, SBS.USER_TYPE, SBS.TEAM_ID           
             FROM ss_battle SB, ss_battle_status SBS
              LEFT JOIN ss_battle_events SBE ON SBE.USER_ID=SBS.USER_ID
                                             AND SBE.USER_TYPE=SBS.USER_TYPE
                                             AND SBE.BATTLE_ID=SBS.BATTLE_ID
                                             AND SBE.MOVE_ID=".($moves+1)."
              LEFT JOIN ss_battle_events SBE1 ON SBE1.USER_ID=SBS.USER_ID
                                             AND SBE1.USER_TYPE=SBS.USER_TYPE
                                             AND SBE1.BATTLE_ID=SBS.BATTLE_ID
                                             AND SBE1.MOVE_ID=".($moves)."
           WHERE SBS.BATTLE_ID = ".$this->battle_id." 
                 AND SBS.BATTLE_ID=SB.BATTLE_ID 
           ORDER BY SBE.MOVE_ID DESC";
   $db->query($sql);
   $c = 0;
   while ($row = $db->nextRow()) {
     if ($row['USER_TYPE']==0
          && $row['TIMEOUT_CHECK'] > $duel_timeout &&
         ($row['MOVE_DATE'] > $duel_timeout
         || empty($row['MOVE_DATE']))) {
      //echo "move_date".$row['MOVE_DATE'];
       // penalize and make move  
       $data['LAG'][$c] = $row;
       $penalty[$row['TEAM_ID']]++; 
       $c++;
     }
   }
   if ($c == 0) {
     $data['OUTCOME'] = false;
     return $data;
   }

   foreach($data['LAG'] as $player) {
       $moves = $player['MOVES'] + 1;
       unset($sdata);
       $sdata['MOVE_ID'] = $moves;
       $sdata['COORDX'] = $player['COORDX'];
       $sdata['COORDY'] = $player['COORDY'];
       $sdata['BATTLE_ID'] = $this->battle_id;
       $sdata['USER_ID'] = $player['USER_ID'];
       $sdata['USER_TYPE'] = $player['USER_TYPE'];
       $sdata['MOVE_DATE'] = 'NOW()';
       $attempts = $player['ATTEMPTS'];
       $sdata['ATTEMPT'] = $player['ATTEMPTS'];
       $sdata['STATUS'] = $player['ESTATUS'];
       $sdata['ACTION'] = 10;
       $db->insert('ss_battle_events', $sdata);
       unset($sdata);
       $sdata['MOVE_ID'] = $player['MOVES'] + 1;
       $sdata['STATUS'] = $player['BSTATUS'];
       $db->update('ss_battle_status', $sdata, "BATTLE_ID=".$this->battle_id." AND USER_ID=".$player['USER_ID']." AND USER_TYPE=".$player['USER_TYPE']);
       
   }
   if (isset($moves)) {
//     unset($sdata);
//     $db->update('ss_battle', $sdata, "BATTLE_ID=".$this->battle_id);
   }

   $data['OUTCOME'] = true;
   $data['PENALTY'][1] = $penalty[1];
   $data['PENALTY'][2] = $penalty[2];

   if ($penalty[1] == $penalty[2]) {
     //print_r($data);
   //  exit;
   }

   return $data;
 }


 function postMovesUpdates($attack, $defence, $penalty1=0, $penalty2=0) {
  // check if all users are equal
  global $db;
  global $_SESSION;
  global $auth;	
  global $properties;
  global $cells;
  global $actions;
  global $fatigue_attack_action_effect;
  global $fatigue_defense_action_effect;
  global $defense_action_basic_value;
  global $attack_action_basic_value;
  global $_POST;

   $handle = fopen("ss_log", "a+b");
//echo "postMovesUpdates";
   unset($sdata);
   $sdata['LAST_VISIT'] = "NOW()"; 
   $db->update('ss_users', $sdata, "USER_ID=".$auth->getUserId());

   $scores = $this->getScores();
   $notmoved = $this->getNotmovedCount();

   if ($notmoved == 0) { // everybody moved, start calculations
       $sql = "SELECT SU.STAMINA, SU.STRENGTH, SU.HEIGHT, SU.WEIGHT, 
                      SU.SPEED, SU.COORDINATION, SU.ENDURANCE, SU.LUCK, SU.INJURY,
                      SBE.*, SBS.MOVED, SBS.ATTACK, SBS.DEFENSE, SBS.BALL, SBS.MOVE_ID, SBS.TEAM_ID
                 FROM ss_users SU, ss_battle_status SBS
                  LEFT JOIN ss_battle_events SBE ON  SBE.USER_ID=SBS.USER_ID 
                                                     AND SBE.USER_TYPE=0
                                                     AND SBE.BATTLE_ID=".$this->battle_id." 
                                                     AND SBE.MOVE_ID>=SBS.MOVE_ID
                WHERE SU.USER_ID=SBS.USER_ID
                      AND SBS.USER_TYPE=0
                      AND SBS.TEAM_ID=".$attack."
                      AND SBS.BATTLE_ID=".$this->battle_id."

                UNION

                SELECT SU.STAMINA, SU.STRENGTH, SU.HEIGHT, SU.WEIGHT, 
                      SU.SPEED, SU.COORDINATION, SU.ENDURANCE, SU.LUCK, 0 as INJURY,
                      SBE.*, SBS.MOVED, SBS.ATTACK, SBS.DEFENSE, SBS.BALL, SBS.MOVE_ID, SBS.TEAM_ID
                 FROM ss_bots SU, ss_battle_status SBS
                  LEFT JOIN ss_battle_events SBE ON  SBE.USER_ID=SBS.USER_ID 
                                                     AND SBE.USER_TYPE=1
                                                     AND SBE.BATTLE_ID=".$this->battle_id." 
                                                     AND SBE.MOVE_ID>=SBS.MOVE_ID
                WHERE SU.USER_ID=SBS.USER_ID
                      AND SBS.USER_TYPE=1
                      AND SBS.TEAM_ID=".$attack."
                      AND SBS.BATTLE_ID=".$this->battle_id."                  
               ORDER BY EVENT_ID"; 

       $db->query($sql);       
       // get attack users 
       while($row_attack = $db->nextRow()) {
         $attack_user[$row_attack['USER_ID']] = $row_attack;
         if ($row_attack['BALL'] == 1)
           $ball_user = $row_attack;
         $total_moves = $row_attack['MOVE_ID'];
       }

       $sql = "SELECT SU.STAMINA, SU.STRENGTH, SU.HEIGHT, SU.WEIGHT, 
                      SU.SPEED, SU.COORDINATION, SU.ENDURANCE, SU.LUCK, SU.INJURY,
                      SBE.*, SBS.MOVED, SBS.ATTACK, SBS.DEFENSE, SBS.BALL, SBS.MOVE_ID
                 FROM ss_users SU, ss_battle_status SBS
                  LEFT JOIN ss_battle_events SBE ON  SBE.USER_ID=SBS.USER_ID 
                                                     AND SBE.USER_TYPE=0
                                                     AND SBE.BATTLE_ID=".$this->battle_id." 
                                                     AND SBE.MOVE_ID>=SBS.MOVE_ID
                WHERE SU.USER_ID=SBS.USER_ID
                      AND SBS.USER_TYPE=0
                      AND SBS.TEAM_ID=".$defence."
                      AND SBS.BATTLE_ID=".$this->battle_id."                  

                UNION

                SELECT SU.STAMINA, SU.STRENGTH, SU.HEIGHT, SU.WEIGHT, 
                      SU.SPEED, SU.COORDINATION, SU.ENDURANCE, SU.LUCK, 0 as INJURY,
                      SBE.*, SBS.MOVED, SBS.ATTACK, SBS.DEFENSE, SBS.BALL, SBS.MOVE_ID
                 FROM ss_bots SU, ss_battle_status SBS
                  LEFT JOIN ss_battle_events SBE ON  SBE.USER_ID=SBS.USER_ID 
                                                     AND SBE.USER_TYPE=1
                                                     AND SBE.BATTLE_ID=".$this->battle_id." 
                                                     AND SBE.MOVE_ID>=SBS.MOVE_ID
                WHERE SU.USER_ID=SBS.USER_ID
                      AND SBS.USER_TYPE=1
                      AND SBS.TEAM_ID=".$defence."
                      AND SBS.BATTLE_ID=".$this->battle_id."                  
               ORDER BY EVENT_ID"; 

       $db->query($sql);   
       // get defense users     
       while($row_defense = $db->nextRow()) {
         $defense_user[$row_defense['USER_ID']] = $row_defense;
       }

       foreach($attack_user as $auser) {       
//print_r($auser);
         // get inventory effect
         $sql = "SELECT SU.ITEM_ID, SU.EQUIP_POINT, SU.PROP_AFFECTED, SU.ACTION_VALUE, SU.PREVENT_INJURY
                   FROM ss_users_items SUI, ss_items SU
                  WHERE SUI.USER_ID=".$auser['USER_ID']." 
                        AND SU.EFFECT_TYPE = 1
                        AND SUI.EQUIPED=1 
                        AND SU.ITEM_ID=SUI.ITEM_ID";
         $db->query($sql);
         while ($row = $db->nextRow()) {
            $auser[$properties[$row['PROP_AFFECTED']]] = $auser[$properties[$row['PROP_AFFECTED']]] + $row['ACTION_VALUE'];
            if (!isset($auser['PREVENT_INJURY']))
              $auser['PREVENT_INJURY'] = 0;
            $auser['PREVENT_INJURY'] += $row['PREVENT_INJURY'];
         }
//print_r($auser);
         // get skills effect
         $sql = "SELECT SD.ATTR_ID, SUD.LEVEL, SD.PROP_AFFECTED
                   FROM ss_users_da SUD, ss_skills SD, ss_battle SB
                  WHERE SUD.USER_ID=".$auser['USER_ID']." 
                        AND SD.ATTR_ID=SUD.ATTR_ID
                        AND SD.SPORT_ID=SB.SPORT_ID 
                        AND SB.BATTLE_ID=".$this->battle_id;
         $db->query($sql);
         while ($row = $db->nextRow()) {
            $auser[$properties[$row['PROP_AFFECTED']]] = $auser[$properties[$row['PROP_AFFECTED']]] + $row['LEVEL'];
         }
         $auser['FATIGUE'] = $this->calculateFatiguePass1($auser);
         $attack_user[$auser['USER_ID']] = $auser;
       }
       foreach($defense_user as $duser) {       
         // get inventory effect
         $sql = "SELECT SU.ITEM_ID, SU.EQUIP_POINT, SU.PROP_AFFECTED, SU.ACTION_VALUE, SU.PREVENT_INJURY
                   FROM ss_users_items SUI, ss_items SU
                  WHERE SUI.USER_ID=".$duser['USER_ID']." 
                        AND SUI.EQUIPED=1 
                        AND SU.EFFECT_TYPE = 1
                        AND SU.ITEM_ID=SUI.ITEM_ID";
         $db->query($sql);
         while ($row = $db->nextRow()) {
            $duser[$properties[$row['PROP_AFFECTED']]] = $duser[$properties[$row['PROP_AFFECTED']]] + $row['ACTION_VALUE'];
            if (!isset($duser['PREVENT_INJURY']))
              $duser['PREVENT_INJURY'] = 0;
            $duser['PREVENT_INJURY'] += $row['PREVENT_INJURY'];;
         }
         // get skills effect
         $sql = "SELECT SD.ATTR_ID, SUD.LEVEL, SD.PROP_AFFECTED
                   FROM ss_users_da SUD, ss_skills SD, ss_battle SB
                  WHERE SUD.USER_ID=".$duser['USER_ID']." 
                        AND SD.ATTR_ID=SUD.ATTR_ID
                        AND SD.SPORT_ID=SB.SPORT_ID 
                        AND SB.BATTLE_ID=".$this->battle_id;
         $db->query($sql);
         while ($row = $db->nextRow()) {
            $duser[$properties[$row['PROP_AFFECTED']]] = $duser[$properties[$row['PROP_AFFECTED']]] + $row['LEVEL'];
         }

         $duser['FATIGUE'] = $this->calculateFatiguePass1($duser);
         $defense_user[$duser['USER_ID']] = $duser;
       }
 
       //echo $coordx. " ". $coordy." ".$field;
       $attack_reaction = 0;
       $defense_reaction = 0;

       foreach($attack_user as $auser) {       
         $auser['FATIGUE'] = $this->calculateFatiguePass2($auser);
         $auser['STAMINA'] = $auser['STAMINA'] - $auser['FATIGUE'];
         unset($sdata);
         $sdata['STAMINA'] = $auser['STAMINA'];
         if (!isset($auser['PREVENT_INJURY']))
           $auser['PREVENT_INJURY'] = 0;
         $auser['INJURY'] = $this->calculateInjury($auser);

         if ($auser['USER_TYPE'] == 0)
           $db->update('ss_users', $sdata, "USER_ID=".$auser['USER_ID']);
         else if ($auser['USER_TYPE'] == 1)
           $db->update('ss_bots', $sdata, "USER_ID=".$auser['USER_ID']);  
         $attack_user[$auser['USER_ID']] = $auser;
       }

       foreach($defense_user as $duser) {       
         $duser['FATIGUE'] = $this->calculateFatiguePass2($duser);
         $duser['STAMINA'] = $duser['STAMINA'] - $duser['FATIGUE'];
         unset($sdata);
         $sdata['STAMINA'] = $duser['STAMINA'];
         if (!isset($duser['PREVENT_INJURY']))
           $duser['PREVENT_INJURY'] = 0;
         $duser['INJURY'] = $this->calculateInjury($duser);

         if ($duser['USER_TYPE'] == 0)
           $db->update('ss_users', $sdata, "USER_ID=".$duser['USER_ID']);
         else if ($duser['USER_TYPE'] == 1)
           $db->update('ss_bots', $sdata, "USER_ID=".$duser['USER_ID']);  

         $defense_user[$duser['USER_ID']] = $duser;
       }

       // calc defense
       foreach($defense_user as $duser) {       
         if ($duser['DEFENSE'] == 0) {
           $duser['DEFENSE_ACTION_RESULT'] = -1;
           $duser['DEFENSE_REACTION'] = -1;
         } else {
           $duser = $this->calculateDefenseActionResult ($duser, $ball_user);
         }
         $defense_user[$duser['USER_ID']] = $duser;
       }

       // calc attack
       foreach($attack_user as $auser)
       {
         if ($auser['BALL'] == 1) {
           $auser = $this->calculateAttackActionResult ($auser, $defense_user);
           $attack_user[$auser['USER_ID']] = $auser;
         }
       }
       $update = false;
    // calculate success of action
       $attack_reaction = 1;
       foreach($defense_user as $duser) {       
         if ($duser['DEFENSE_REACTION'] == 1 && $duser['DEFENSE'] > 0) {
           $attack_reaction = 0;
           //$scores['RESULT2'] += 2;
           $update = true;
           break;
         }
       }

       foreach($attack_user as $auser)
       {
         if ($auser['BALL'] == 1) {
           unset($sdata);
           if (!isset($auser['ATTACK_REACTION']))
             $auser['ATTACK_REACTION'] = 0;

           $sdata['REACTION'] = $auser['ATTACK_REACTION'] * $attack_reaction;
           $sdata['ACTION_RESULT'] = $auser['ATTACK_ACTION_RESULT'];
           $sdata['DICE']= $auser['SCS'];
           $db->update('ss_battle_events', $sdata, "BATTLE_ID=".$this->battle_id." AND MOVE_ID=".$total_moves." AND USER_TYPE=".$auser['USER_TYPE']." AND USER_ID=".$auser['USER_ID']);
           if ($auser['ATTACK_REACTION'] * $attack_reaction == 1) {
             if ($auser['ACTION'] == 1) {
               if ((abs($auser['COORDX']-5) + abs($auser['COORDY']-3)) >= 3) {
                 if ($auser['TEAM_ID'] == 1) 
                   $scores['RESULT1'] += 3;
                 else $scores['RESULT2'] += 3;
               }
               else {
                 if ($auser['TEAM_ID'] == 1) 
                   $scores['RESULT1'] += 2;
                 else $scores['RESULT2'] += 2;
               }
               $update = true;
             }
             else if ($auser['ACTION'] == 2) {
               foreach($attack_user as $aouser) {
                 if ($auser['PASS_USER'] == $aouser['USER_ID']) {
                   unset($sdata);
                   $sdata['BALL'] = 0;
                   $db->update('ss_battle_status', $sdata, "BATTLE_ID=".$this->battle_id." AND USER_ID=".$auser['USER_ID']);
                   unset($sdata);
                   $sdata['BALL'] = 1;
                   $db->update('ss_battle_status', $sdata, "BATTLE_ID=".$this->battle_id." AND USER_ID=".$aouser['USER_ID']);
                   break;
                 }
               }
             } 
           }
           $attack_user[$auser['USER_ID']] = $auser;
         }  
       }
//print_r($defense_user);
       foreach($defense_user as $duser) 
       {
         unset($sdata);
         $sdata['REACTION'] = $duser['DEFENSE_REACTION'];
         $sdata['ACTION_RESULT'] = $duser['DEFENSE_ACTION_RESULT'];
         $sdata['DICE']= $duser['SCS'];
         $db->update('ss_battle_events', $sdata, "BATTLE_ID=".$this->battle_id." AND MOVE_ID=".$total_moves." AND USER_TYPE=".$duser['USER_TYPE']." AND USER_ID=".$duser['USER_ID']);
       }

//       fwrite($handle, "result: ". $attack_user[0]['COORDX']." ".$attack_user[0]['COORDY']." ".abs($attack_user[0]['COORDX']-5)." ".abs($attack_user[0]['COORDY']-3)."\n");
       if ($update ) {
         unset($sdata);
         $sdata['RESULT1'] = $scores['RESULT1'] + $penalty2;
         $sdata['RESULT2'] = $scores['RESULT2'] + $penalty1;
         $sdata['ATTEMPTS'] = $scores['ATTEMPTS']+1;
         $db->update('ss_battle', $sdata, "BATTLE_ID=".$this->battle_id);
         $this->initStartPositions($total_moves+1, $sdata['ATTEMPTS'], $defence, $attack);
       }
       else if ($penalty1 > 0 || $penalty2 > 0) {
         unset($sdata);
         $sdata['RESULT1'] = $scores['RESULT1'] + $penalty2;
         $sdata['RESULT2'] = $scores['RESULT2'] + $penalty1;
         $sdata['ATTEMPTS'] = $scores['ATTEMPTS']+1;
         $sdata['MOVES'] = $total_moves;
         $sdata['LAST_MOVE'] = "NOW()"; 
         $sdata['LAST_MOVE_START'] = "NOW()"; 
         $db->update('ss_battle', $sdata, "BATTLE_ID=".$this->battle_id);
       }
       else {
         unset($sdata);
         $sdata['MOVES'] = $total_moves;
         $sdata['LAST_MOVE'] = "NOW()"; 
         $sdata['LAST_MOVE_START'] = "NOW()"; 
         $db->update('ss_battle', $sdata, "BATTLE_ID=".$this->battle_id);
       }
//echo "here it is!";
//exit;
       $data['WAIT'] = 0;
   }
   else {
     //echo "notmoved:".$row['NOTMOVED'];
     $data['WAIT'] = 1;
   }
   fclose($handle);
//exit;
   return $data;
 }


 function initStartPositions($move_id, $attempt=0, $attack, $defence) {
   global $db;
   global $table_size;
//echo xxx.$battle_type;
   $table_width = $table_size[$this->battle_type][0];
   $table_heigth = $table_size[$this->battle_type][1];
/*print_r($table_size);
echo $this->battle_type;
echo $table_heigth;
exit;*/
   // get team users
   $sql = "SELECT SBS.USER_ID, SBS.TEAM_ID, SBS.USER_TYPE
             FROM ss_battle_status SBS
            WHERE SBS.BATTLE_ID=".$this->battle_id;
   $db->query($sql);
   $c = 0;
   while($row = $db->nextRow()) {
     $teams[$row['TEAM_ID']][$row['USER_ID']] = $row;
     if ($row['TEAM_ID'] == $attack)
       $c++;
   }
// attack 1, defence 2
   // put on attack
//$db->showquery = true;
    $ball = rand(0, $c-1);
//echo $ball;
    $t = 0;
    foreach($teams[$attack] as $player) {
      unset($sdata);
      $coordx = rand(1, 2);
      $coordy = rand(1, $table_heigth);
      $sdata['MOVE_ID'] = $move_id;
      $sdata['COORDX'] = $coordx;
      $sdata['COORDY'] = $coordy;
      if ($t == $ball)
        $sdata['BALL'] = 1;
      else $sdata['BALL'] = 0;
      $db->update('ss_battle_status', $sdata, "BATTLE_ID=".$this->battle_id." AND USER_ID=".$player['USER_ID']." AND USER_TYPE=".$player['USER_TYPE']);
//exit;
      unset($sdata);
      $sdata['MOVE_ID'] = $move_id;
      $sdata['COORDX'] = $coordx;
      $sdata['COORDY'] = $coordy;
      $sdata['BATTLE_ID'] = $this->battle_id;
      $sdata['USER_ID'] = $player['USER_ID'];
      $sdata['USER_TYPE'] = $player['USER_TYPE'];
      $sdata['MOVE_DATE'] = 'NOW()';
      $sdata['ATTEMPT'] = $attempt;
      $sdata['STATUS'] = 1;
      $sdata['ACTION'] = -1;
      $db->insert('ss_battle_events', $sdata);
      $t++;
    }
   // put on defence
    foreach($teams[$defence] as $player) {
      unset($sdata);
      $coordx = rand(3, $table_width);
      $coordy = rand(1, $table_heigth);
      $sdata['MOVE_ID'] = $move_id;
      $sdata['COORDX'] = $coordx;
      $sdata['COORDY'] = $coordy;
      $sdata['BALL'] = 0;
      $db->update('ss_battle_status', $sdata, "BATTLE_ID=".$this->battle_id." AND USER_ID=".$player['USER_ID']." AND USER_TYPE=".$player['USER_TYPE']);
      unset($sdata);
      $sdata['MOVE_ID'] = $move_id;
      $sdata['COORDX'] = $coordx;
      $sdata['COORDY'] = $coordy;
      $sdata['BATTLE_ID'] = $this->battle_id;
      $sdata['USER_ID'] = $player['USER_ID'];
      $sdata['USER_TYPE'] = $player['USER_TYPE'];
      $sdata['MOVE_DATE'] = 'NOW()';
      $sdata['ATTEMPT'] = $attempt;
      $sdata['STATUS'] = 2;
      $sdata['ACTION'] = -1;
      $db->insert('ss_battle_events', $sdata);
    }
    unset($sdata);
    $sdata['LAST_MOVE'] = "NOW()"; 
    $sdata['LAST_MOVE_START'] = "NOW()"; 
    $sdata['MOVES'] = $move_id;
    $db->update('ss_battle', $sdata, "BATTLE_ID=".$this->battle_id);
//exit;

 }

 function AIAttack($current_user, $other_users, $ouser, $ball_coordx, $ball_coordy, $attack, $defence) {
   global $basket_coord;
   global $table_size;

     $coordx = $ouser['COORDX'];
     $coordy = $ouser['COORDY'];
       // find how many defenders are around
       // perform pass if too many  
        $around_guys = 0;
        $attack_guys = '';
        $c=0; 
        if ($ouser['BALL'] == 1) {
          foreach ($other_users as $oouser) {
            if ($oouser['TEAM_ID'] == $defence) {
              if (abs($oouser['COORDX'] - $ouser['COORDX']) <= 1 && 
                  abs($oouser['COORDY'] - $ouser['COORDY']) <= 1 ) {
                $around_guys++;
              } 
             } else if ($oouser['TEAM_ID'] == $attack && $oouser['USER_ID'] != $ouser['USER_ID']) {
               $attack_guys[$c] = $oouser['USER_ID'];
               $c++; 
             }
          }
          if ($current_user['TEAM_ID'] == $defence) {
            if (abs($current_user['COORDX'] - $ouser['COORDX']) <= 1 && 
                 abs($current_user['COORDY'] - $ouser['COORDY']) <= 1 ) {
               $around_guys++;
            } 
          } else if ($current_user['TEAM_ID'] == $attack) {
             $attack_guys[$c] = $current_user['USER_ID'];
          }
          if ($around_guys >=2) {
            // pass
            $sdata['ACTION'] = 2;
            $sdata['PASS_USER'] = $attack_guys[rand(0, count($attack_guys)-1)];
          } else {
            $sdata['ACTION'] = 1;
          }
         if ($coordx < $basket_coord[$this->battle_type][0])
           $coordx = $coordx + 1;
         else if ($coordx > 1)
                $coordx = $coordx - 1;
         if ($coordy < $basket_coord[$this->battle_type][1])
           $coordy = $coordy + 1;
         else if ($coordy > 1)
                $coordy = $coordy - 1;
       } else {
        // try to ditch defenders, go away from ball men
         if (abs($coordx - $ball_coordx) + abs($coordy - $ball_coordy) < 2) {
           if ($coordx < $ball_coordx && $coordx > 1)
             $coordx = $coordx - 1;
           else if ($coordx > $ball_coordx && $coordx < $table_size[$this->battle_type][0])
                  $coordx = $coordx + 1;
           if ($coordy < $ball_coordy && $coordy > 1)
             $coordy = $coordy - 1;
           else if ($coordy > $ball_coordy && $coordy < $table_size[$this->battle_type][1])
                  $coordy = $coordy - 1;      
         }
         else {
          if ($coordx < $basket_coord[$this->battle_type][0])
            $coordx = $coordx + 1;
          else if ($coordx > 1)
                $coordx = $coordx - 1;
          if ($coordy < $basket_coord[$this->battle_type][1])
             $coordy = $coordy + 1;
          else if ($coordy > 1)
                $coordy = $coordy - 1;
         }
         $sdata['ACTION'] = -1;
       }
      $sdata['COORDX'] = $coordx;
      $sdata['COORDY'] = $coordy;
      return $sdata;
   }

 function AIDefense($current_user, $other_users, $ouser, $ball_coordx, $ball_coordy, $attack, $defence) {
     $coordx = $ouser['COORDX'];
     $coordy = $ouser['COORDY'];

       $sdata['ACTION'] = 0;
       if (abs($coordx - $ball_coordx) <= 1 
           && abs($coordy - $ball_coordy) <= 1)
         $sdata['ACTION'] = 2;

       if ($coordx < $ball_coordx)
         $coordx = $coordx + 1;
       else if ($coordx > 1)
              $coordx = $coordx - 1;
       if ($coordy < $ball_coordy)
         $coordy = $coordy + 1;
       else if ($coordy > 1)
              $coordy = $coordy - 1;
      $sdata['COORDX'] = $coordx;
      $sdata['COORDY'] = $coordy;
      return $sdata;

 }

 function calculateFatiguePass1($duser) {
      $duser['FATIGUE'] = 0.1;
      if ($duser['MOVED'] != 5) {
          $duser['FATIGUE'] += 0.02*(50 - $duser['ENDURANCE']);
      } 
echo $duser['FATIGUE'];
      return $duser['FATIGUE'];
 }

 function calculateFatiguePass2($user) {
    global $fatigue_defense_action_effect;
    global $fatigue_attack_action_effect;

    $user['FATIGUE'] = 0.1;
//print_r($user);
    if ($user['DEFENSE'] > 0) {
       $user['FATIGUE'] += 0.01*($fatigue_defense_action_effect[$user['ACTION']] - $user['ENDURANCE']);
    } 
    if ($user['ATTACK'] > 0) {
       $user['FATIGUE'] += 0.01*($fatigue_attack_action_effect[$user['ACTION']] - $user['ENDURANCE']);
    } 

echo $user['FATIGUE'];
    return $user['FATIGUE'];
 } 

 function calculateInjury($user) {
  if ($user['STAMINA'] < 70 - $user['PREVENT_INJURY']) {
     if (rand(0, 100) < 80-$user['STAMINA'] ) {
       if ($user['STAMINA'] > 50 - $user['PREVENT_INJURY']) {
         $user['INJURY'] = 1;
       } else if ($user['STAMINA'] > 30 - $user['PREVENT_INJURY']) {
         $user['INJURY'] = 2; 
       }
       else {
         $user['INJURY'] = 3;
       }
     }
   }
   return $user['INJURY'];

 }

 function calculateDefenseActionResult ($user, $ball_user) {
      global $defense_action_basic_value;

      $distance = ($user['COORDX']-$ball_user['COORDX'])*($user['COORDX']-$ball_user['COORDX']) + ($user['COORDY']-$ball_user['COORDY'])*($user['COORDY']-$ball_user['COORDY']);        
      $user['COORDINATION_X'] = 0.7*$user['COORDINATION']*2;
      $user['LUCK_X'] = rand(0, $user['LUCK']);
      if ($distance > 2) {
         $user['DEFENSE_ACTION_RESULT'] = -1;
         $user['DEFENSE_REACTION'] = -1;
      } 
      else {
         $user['DEFENSE_ACTION_RESULT'] = ($user['STAMINA']/5 + $user['COORDINATION_X'] + $user['LUCK_X']) * $defense_action_basic_value[$user['ACTION']]; //'DEFENSE' ?
         $user['DEFENSE_ACTION_RESULT'] = $user['DEFENSE_ACTION_RESULT'] / (1+$user['INJURY']);
      } 

      $scs_d = rand(0, 100);                 
      $user['SCS'] = $scs_d;
      if ($scs_d < $user['DEFENSE_ACTION_RESULT'])
        $user['DEFENSE_REACTION'] = 1;
      else $user['DEFENSE_REACTION'] = 0; 

      return $user;
 }

 function calculateAttackActionResult ($user, $defense_user) {
      global $attack_action_basic_value;
      global $cells;

      $distance_proc = $cells[$user['COORDY']-1][$user['COORDX']-1];        
      $coordination = 0.7*$user['COORDINATION']*2;
      $luck = rand(0, $user['LUCK']);
      $defense_action_result = 0;
      foreach($defense_user as $duser) { 
        if ($duser['DEFENSE_ACTION_RESULT'] > $defense_action_result) {
          $defense_action_result += $duser['DEFENSE_ACTION_RESULT'];
        }     
      } 
      $user['ATTACK_ACTION_RESULT'] = ($user['STAMINA']/10 + $coordination + $luck + $distance_proc) * $attack_action_basic_value[$user['ACTION']] - $defense_action_result/2;  
      $user['ATTACK_ACTION_RESULT'] = $user['ATTACK_ACTION_RESULT'] / (1 + $user['INJURY']);

      $scs_a = rand(0, 100);                   
      $user['SCS'] = $scs_a;
      if ($scs_a < $user['ATTACK_ACTION_RESULT'])
        $user['ATTACK_REACTION']  = 1;  

      return $user;
 }

 function generateTeam($team_id, $men, $bots) {
    global $db;
    global $auth;

    for ($i = 0; $i < $men; $i++) {
       $sdata['BATTLE_ID'] = $this->battle_id;
       $sdata['USER_ID'] = $auth->getUserId();
       $sdata['TEAM_ID'] = $team_id;
       $sdata['STATUS'] = 1;
       $sdata['USER_TYPE'] = 0;
       $db->insert('ss_battle_status', $sdata);
    }
    unset($sdata);

    for ($i = 0; $i < $bots; $i++) {
       $sdata['BATTLE_ID'] = $this->battle_id;
       $sdata['USER_ID'] = $this->generateBot($auth->getUserId());
       $sdata['TEAM_ID'] = $team_id;
       $sdata['STATUS'] = 1;
       $sdata['USER_TYPE'] = 1;
       $db->insert('ss_battle_status', $sdata);
    }
 }
 

 function generateBot($user_id) {
   global $db;
  
   $sql = "SELECT SU.EQUIPED_LEVEL
             FROM ss_users SU
              WHERE SU.USER_ID=".$user_id;
   $db->query($sql);
   $row = $db->nextRow();
   unset($sdata);
   $sdata['LEVEL'] = $row['EQUIPED_LEVEL'];
   $sdata['STAMINA'] = 100;
   $sdata['HEIGHT'] = 100;
   $sdata['WEIGHT'] = 100;
   $weigth = array(0,0,0,0,0);

   for ($i=0; $i < $row['EQUIPED_LEVEL']; $i++) {
     $param[0] = rand(0, 100);
     $param[1] = rand(0, 100);
     $param[2] = rand(0, 100);
     $param[3] = rand(0, 100);
     $param[4] = rand(0, 100);

     $maxkoeff = -1;
     $max = 0;
     for ($k=0; $k < 5; $k++) {
       if ($param[$k] > $max) {
         $max = $param[$k];
         $maxkoeff = $k;
       }
     }
     $weigth[$maxkoeff]++;
   }

   $sdata['STRENGTH'] = 5+$weigth[0];
   $sdata['SPEED'] = 5+$weigth[1];
   $sdata['COORDINATION'] = 5+$weigth[2];
   $sdata['ENDURANCE'] = 5+$weigth[3];
   $sdata['LUCK'] = 5+$weigth[4];
   $db->insert('ss_bots', $sdata); 
   return $db->id();
 }

}

?>