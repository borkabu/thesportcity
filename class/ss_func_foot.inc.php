<?php 

 $actions = array ('attack' => array ('fut_attack_1' => 0, 'fut_attack_2' => 1),
                   'defense' => array ('fut_defense_1' => 2, 'fut_defense_2' => 3));

 $cells = array(array (1, 2, 3,  4,  5,  6,  7,  8,  9, 10),
                array (2, 3, 4,  5,  6, 10, 11, 12, 13, 15),
                array (3, 4, 5,  6,  8, 15, 30, 40, 50, 25),
                array (4, 5, 6,  7, 10, 20, 40, 50, 55, 50),
                array (5, 6, 7, 10, 15, 25, 50, 60, 65, 70),
                array (5, 6, 7, 10, 15, 25, 50, 60, 65, 70),
                array (4, 5, 6,  7, 10, 20, 40, 50, 55, 50),
                array (3, 4, 5,  6,  8, 15, 30, 40, 50, 25),
                array (2, 3, 4,  5,  6, 10, 11, 12, 13, 15),
                array (1, 2, 3,  4,  5,  6,  7,  8,  9, 10));

 $attack_actions_explained = array (-1 => 'Ready to attack',
                                    0 => 'Dribbled the ball', 
                              	    1 => 'Shooting attempt',
                                    2 => 'Attempt to pass',
                                    10 => 'Timeout');

 $defense_actions_explained = array ( -1 => 'Ready to defend',
                                      0 => 'Keeps eye on a ball',  
                                      2 => 'Attempt to intercept',
                                      3 => 'Attempt to block',
                                      10 => 'Timeout');

 $fatigue_attack_action_effect = array(0 => 50,
                                       1 => 80,
                                       2 => 50,
                                      10 => 500);

 $fatigue_defense_action_effect = array(0 => 30,
                                        2 => 50,
                                        3 => 60,
                                      10 => 500);

 $attack_action_basic_value = array(0 => 0,
                                    1 => 0.9,
                                    2 => 0.9, 
                                    10 => 0);

 $defense_action_basic_value = array(0 => 0,
                                     2 => 0.6,
                                     3 => 0.5, 
                                    10 => 0);


 function initStartPositions($battle_id, $move_id, $attempt=0, $attack, $defence) {
   global $db;

   // get team users
   $sql = "SELECT SBS.USER_ID, SBS.TEAM_ID, SBS.USER_TYPE
             FROM ss_battle_status SBS
            WHERE SBS.BATTLE_ID=".$battle_id;
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
      $coordy = rand(1, 10);
      $sdata['MOVE_ID'] = $move_id;
      $sdata['COORDX'] = $coordx;
      $sdata['COORDY'] = $coordy;
      if ($t == $ball)
        $sdata['BALL'] = 1;
      else $sdata['BALL'] = 0;
      $db->update('ss_battle_status', $sdata, "BATTLE_ID=".$battle_id." AND USER_ID=".$player['USER_ID']." AND USER_TYPE=".$player['USER_TYPE']);
//exit;
      unset($sdata);
      $sdata['MOVE_ID'] = $move_id;
      $sdata['COORDX'] = $coordx;
      $sdata['COORDY'] = $coordy;
      $sdata['BATTLE_ID'] = $battle_id;
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
      $coordx = rand(5, 10);
      $coordy = rand(1, 10);
      $sdata['MOVE_ID'] = $move_id;
      $sdata['COORDX'] = $coordx;
      $sdata['COORDY'] = $coordy;
      $sdata['BALL'] = 0;
      $db->update('ss_battle_status', $sdata, "BATTLE_ID=".$battle_id." AND USER_ID=".$player['USER_ID']." AND USER_TYPE=".$player['USER_TYPE']);
      unset($sdata);
      $sdata['MOVE_ID'] = $move_id;
      $sdata['COORDX'] = $coordx;
      $sdata['COORDY'] = $coordy;
      $sdata['BATTLE_ID'] = $battle_id;
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
    $db->update('ss_battle', $sdata, "BATTLE_ID=".$battle_id);
//exit;

 }

 function prepareTable($current_user, $type, $waiting, $other_users, $attack, $defence) {
   global $locations;
   for ($i = 1; $i < 11; $i++) {
     for ($j = 1; $j < 11; $j++) {
       if ($current_user['CURX'] < 11 && (abs($current_user['CURX']) - abs($j)) <= 1 && (abs($current_user['CURY']) - abs($i)) <= 1 ) {
        if (!empty($locations[$current_user['CURY'] - $i][$current_user['CURX'] - $j]) && !$waiting) {
         $data['ROWS'][$i]['COLS'][$j]['BUTTON'][0]['VALUE'] = $locations[$current_user['CURY'] - $i][$current_user['CURX'] - $j];
         $data['ROWS'][$i]['COLS'][$j]['WIDTH'] = 20; 
         $data['ROWS'][$i]['COLS'][$j]['HEIGHT'] = 30; 
         if ( $data['ROWS'][$i]['COLS'][$j]['BUTTON'][0]['VALUE'] == 5)
           $data['ROWS'][$i]['COLS'][$j]['BUTTON'][0]['CHECKED'] = "checked";
        }
        else {
              $data['ROWS'][$i]['COLS'][$j]['WIDTH'] = 20; 
              $data['ROWS'][$i]['COLS'][$j]['HEIGHT'] = 30; 
             }
       }  
       else {
              $data['ROWS'][$i]['COLS'][$j]['WIDTH'] = 20; 
              $data['ROWS'][$i]['COLS'][$j]['HEIGHT'] = 30; 

       }
     }
   }
  $mixed = false;
  foreach($other_users as $ouser) {
    if ($current_user['CURX'] == $ouser['CURX'] && $current_user['CURY'] == $ouser['CURY']) {
      $data['ROWS'][$current_user['CURY']]['COLS'][$current_user['CURX']]['ATTACKDEFENSE'][0]['SPORT'] = 'football'; 
      $mixed = true;
    }
    else {
      if ($ouser['TEAM_ID'] == $defence) {
        $data['ROWS'][$ouser['CURY']]['COLS'][$ouser['CURX']]['DEFENSE'][0]['SPORT'] = 'football'; 
      }
      else if ($ouser['TEAM_ID'] == $attack){
        $data['ROWS'][$ouser['CURY']]['COLS'][$ouser['CURX']]['ATTACK'][0]['SPORT'] = 'football';  
      }
    }
  }

  if ($type == 0 && $mixed == false) {
      $data['ROWS'][$current_user['COORDY']]['COLS'][$current_user['COORDX']]['ATTACK'][0]['SPORT'] = 'football'; 
    }
  else if ($type == 1 && $mixed == false) {
     $data['ROWS'][$current_user['COORDY']]['COLS'][$current_user['COORDX']]['DEFENSE'][0]['SPORT'] = 'football'; 
  }
  return $data['ROWS'];
 }

 function getProtocol($battle_id, $user1, $user2, $limit=1) {
   global $db;
   global $reactions_explained;
   global $attack_actions_explained;
   global $defense_actions_explained;
   global $_SESSION;

   $sql="SELECT MOVES FROM ss_battle
             WHERE BATTLE_ID = ".$battle_id;
   $db->query($sql);
   $row = $db->nextRow();
   $moves = $row['MOVES'];
   
   $sql = "SELECT SBE.USER_ID, SBS.TEAM_ID, SBE.STATUS, SBE.ATTEMPT,
                  SBE.MOVE_ID, SBE.ACTION, SBE.REACTION
             FROM ss_battle SB, ss_battle_status SBS
                  LEFT JOIN ss_battle_events SBE ON
                   SBS.BATTLE_ID = SBE.BATTLE_ID
                   AND SBE.USER_ID = SBS.USER_ID
                   AND SBE.USER_TYPE = SBS.USER_TYPE
                   AND ((SBE.MOVE_ID<=".$moves."  AND SBE.USER_ID<>".$auth->getUserId().")
                      OR (SBE.USER_ID=".$auth->getUserId()." AND SBE.USER_TYPE=0))
             WHERE SB.BATTLE_ID = ".$battle_id."
                   AND SBS.BATTLE_ID=SB.BATTLE_ID 
            ORDER BY SBE.MOVE_ID DESC
            LIMIT ".$limit;
//echo "protocol".$sql;
   $db->query($sql);
   while ($row = $db->nextRow()) {
     $data[$row['MOVE_ID']]['NUMBER'] = $row['MOVE_ID'];
     $data[$row['MOVE_ID']]['ROUND'] = $row['ATTEMPT'] +1;
     if ($row['TEAM_ID'] == 1) {
       if ($row['STATUS'] == 1) {
         $data[$row['MOVE_ID']]['HOME'][0]['MOVE'] = $attack_actions_explained[$row['ACTION']];
         if ($row['REACTION'] == 1) {
           $data[$row['MOVE_ID']]['HOME'][0]['REACTION'][0]['R'] = $reactions_explained[$row['REACTION']];
         }
       } else {
         $data[$row['MOVE_ID']]['HOME'][0]['MOVE'] = $defense_actions_explained[$row['ACTION']];
         if ($row['REACTION'] == 1) {
           $data[$row['MOVE_ID']]['HOME'][0]['REACTION'][0]['R'] = $reactions_explained[$row['REACTION']];
         }
       }
     } else {
       if ($row['STATUS'] == 1) {
         $data[$row['MOVE_ID']]['VISITER'][0]['MOVE'] = $attack_actions_explained[$row['ACTION']];
         if ($row['REACTION'] == 1) {
           $data[$row['MOVE_ID']]['VISITER'][0]['REACTION'][0]['R'] = $reactions_explained[$row['REACTION']];
         }
       } else {	
         $data[$row['MOVE_ID']]['VISITER'][0]['MOVE'] = $defense_actions_explained[$row['ACTION']];
         if ($row['REACTION'] == 1) {
           $data[$row['MOVE_ID']]['VISITER'][0]['REACTION'][0]['R'] = $reactions_explained[$row['REACTION']];
         }  
       }
     }

/*    if ($row['MOVE_ID'] == $row['MOVE_ID2'] && $user_1 == $user1) {
     $data[$row['MOVE_ID']]['NUMBER'] = $row['MOVE_ID'];
     $data[$row['MOVE_ID']]['HOME'][0]['MOVE'] = $attack_actions_explained[$row['ACTION']];
     $data[$row['MOVE_ID']]['HOME'][0]['REACTION'] = $reactions_explained[$row['REACTION']];
     $data[$row['MOVE_ID']]['VISITER'][0]['MOVE'] = $defense_actions_explained[$row['ACTION2']];
     $data[$row['MOVE_ID']]['VISITER'][0]['REACTION'] = $reactions_explained[$row['REACTION2']];
    }
    else if ($row['MOVE_ID'] == $row['MOVE_ID2'] && $user_1 == $user2) {
     $data[$row['MOVE_ID']]['NUMBER'] = $row['MOVE_ID'];
     $data[$row['MOVE_ID']]['HOME'][0]['MOVE'] = $attack_actions_explained[$row['ACTION2']];
     $data[$row['MOVE_ID']]['HOME'][0]['REACTION'] = $reactions_explained[$row['REACTION2']];
     $data[$row['MOVE_ID']]['VISITER'][0]['MOVE'] = $defense_actions_explained[$row['ACTION']];
     $data[$row['MOVE_ID']]['VISITER'][0]['REACTION'] = $reactions_explained[$row['REACTION']];
    }
    else if ($user_1 == $user1) {
     $data[$row['MOVE_ID']]['NUMBER'] = $row['MOVE_ID'];
     $data[$row['MOVE_ID']]['HOME'][0]['MOVE'] = $attack_actions_explained[$row['ACTION']];
     $data[$row['MOVE_ID']]['HOME'][0]['REACTION'] = 'Unclear';
    }
    else if ($user_1 == $user2) {
     $data[$row['MOVE_ID']]['NUMBER'] = $row['MOVE_ID'];
     $data[$row['MOVE_ID']]['VISITER'][0]['MOVE'] = $defense_actions_explained[$row['ACTION']];
     $data[$row['MOVE_ID']]['VISITER'][0]['REACTION'] = 'Unclear';
    }*/
   }
   return $data;                               

 }

 function drawBoard($current_user, $other_users, $waiting, $attack, $defence) {
   global $_SESSION;
//echo $attack."''''".$defence;
     if ($current_user['USER_ID'] == $auth->getUserId() 
         && $current_user['TEAM_ID'] == $attack) { // home
       // create table
       $data['ROWS'] = prepareTable($current_user, 0, $waiting, $other_users, $attack, $defence);
       if ($waiting) {
         $data['WAITING'] = 1;
       } 
       else $data['ATTACK'] = 1;
     }
     else if ($current_user['USER_ID'] == $auth->getUserId()
              && $current_user['TEAM_ID'] == $defence) { // away
       $data['ROWS'] = prepareTable($current_user, 1, $waiting, $other_users, $attack, $defence);
       if ($waiting) {
         $data['WAITING'] = 1;
       } 
       else $data['DEFENSE'] = 1;
     } 
     // create table
   $data['FIELD'] ='fcourt';
   $data['WIDTH'] ='205';
   $data['HEIGHT'] ='300';

   return $data;
 }

 function makeMove($current_user, $other_users, $total_moves, $attack, $defence) {
   global $db;
   global $duel_timeout;
   global $_SESSION;
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
   $battle_id = $current_user['BATTLE_ID'];

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

   $db->select('ss_battle', "RESULT1, RESULT2, ATTEMPTS, BATTLE_TYPE", "BATTLE_ID=".$battle_id);
   $scores = $db->nextRow();

   if ($scores['BATTLE_TYPE'] == 2) {
     makeBotMoves($battle_id, $other_users, $total_moves, $attack, $defence);
   }

   $data['HANDLE_TIMEOUT'][0] = handleTimeout($battle_id);
   if ($data['HANDLE_TIMEOUT'][0]['OUTCOME'] == true) {
     $data['POST'][0] = postMovesUpdates($battle_id, $attack, $defence, $data['HANDLE_TIMEOUT'][0]['PENALTY'][1], $data['HANDLE_TIMEOUT'][0]['PENALTY'][2]);
     $data['RELOAD'] = 1;
     fclose($handle);
     return $data;
   }

   if (isset($_POST['move']) && !$waiting) {
     $sql = "SELECT MAX(MOVE_ID) MOVES FROM ss_battle_events 
                                 WHERE USER_ID=".$auth->getUserId()." 
                                       AND USER_TYPE=0
                                       AND BATTLE_ID=".$battle_id;
     $db->query($sql);       
     if ($row2 = $db->nextRow()) {
       $total_moves = $row2['MOVES'];
     }       

     $sql = "SELECT SBS.*
               FROM ss_battle_status SBS
             WHERE SBS.USER_ID=".$auth->getUserId()." 
                   AND SBS.USER_TYPE=0
                   AND SBS.BATTLE_ID=".$battle_id;

     $db->query($sql);       

     $coordx = $current_user['COORDX'];
     $coordy = $current_user['COORDY'];

     fwrite($handle, "stage1: ". $coordx." ".$coordy."\n");
     // convert field to coords
     $coordx += $fields[$_POST['field']][0];
     $coordy += $fields[$_POST['field']][1];               

     fwrite($handle, "stage2: ". $coordx." ".$coordy."\n");
     // insert data into table  
     unset($sdata);
     $sdata['BATTLE_ID'] = $battle_id;
     $sdata['USER_ID'] = $auth->getUserId();
     $sdata['COORDX'] = $coordx;
     $sdata['COORDY'] = $coordy;
     $sdata['MOVE_ID'] = $total_moves + 1;
     $sdata['MOVE_DATE'] = "NOW()"; 
     $sdata['ATTEMPT'] = $scores['ATTEMPTS'];
     $total_moves++;
     $data['TOTAL_MOVES'] = $total_moves;
     if (isset($_POST['attack'])) {
       $sdata['ACTION'] = $actions['attack'][$_POST['attack']];
       // calculate fatigue
     }
     else if (isset($_POST['defense'])) {
         $sdata['ACTION'] = $actions['defense'][$_POST['defense']];
     }
     $sdata['REACTION'] = 0;
     $sdata['STATUS'] = $current_user['STATUS'];
//$db->showquery = true;
     $db->insert('ss_battle_events', $sdata);

     unset($sdata); 
     $sdata['BATTLE_ID'] = $battle_id;
     $sdata['USER_ID'] = $auth->getUserId();
     $sdata['MOVE_ID'] = $total_moves;
     $sdata['COORDX'] = $coordx;
     $sdata['COORDY'] = $coordy;
     $sdata['MOVED'] = $_POST['field'];
     if ($actions['attack'][$_POST['attack']])  
       $sdata['ATTACK'] = $actions['attack'][$_POST['attack']];
     else $sdata['ATTACK'] =  0;
     if (isset($actions['defense'][$_POST['defense']]))  
       $sdata['DEFENSE'] = $actions['defense'][$_POST['defense']];
     else $sdata['DEFENSE'] = 0;
     $db->update('ss_battle_status', $sdata, "USER_ID=".$auth->getUserId()." AND BATTLE_ID=".$battle_id);
     unset($sdata);
     $sdata['LAST_MOVE'] = "NOW()"; 
     $db->update('ss_battle', $sdata, "BATTLE_ID=".$battle_id);

     $data['POST'][0] = postMovesUpdates($battle_id, $attack, $defence);
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

 function makeBotMoves($battle_id, $other_users, $total_moves, $attack, $defence) {
   global $db;
   global $duel_timeout;
   global $fields;
   global $actions;
   global $fatigue_attack_action_effect;
   global $fatigue_defense_action_effect;
   global $defense_action_basic_value;
   global $attack_action_basic_value;

   $handle = fopen("ss_log", "a+b");
// insert battle events
  // get move of current user
   $move_date = 1000000;
   foreach ($other_users as $ouser) {
    if ($move_date > $ouser['MOVE_DATE'])
      $move_date  = $ouser['MOVE_DATE'];
   }

   $db->select('ss_battle', "RESULT1, RESULT2, ATTEMPTS", "BATTLE_ID=".$battle_id);
   $scores = $db->nextRow();

   // get ball coords - defence
   $sql = "SELECT COORDX, COORDY FROM ss_battle_status 
                                 WHERE BALL=1 AND BATTLE_ID=".$battle_id;
   $db->query($sql);       
   if ($row2 = $db->nextRow()) {
     $ball_coordx = $row2['COORDX'];
     $ball_coordy = $row2['COORDY'];
   }       
    
   // get basket coords - attack
   // x = 5, y = 3
   $basket_coordx = 10;
   $basket_coordy = 5;

   foreach ($other_users as $ouser) {
//echo "botmove".$total_moves;
     $sql = "SELECT MAX(MOVE_ID) MOVES FROM ss_battle_events 
                                 WHERE USER_ID=".$ouser['USER_ID']." 
                                       AND USER_TYPE=1
                                       AND BATTLE_ID=".$battle_id;
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
                   AND SBS.BATTLE_ID=".$battle_id;

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
     if ($ouser['TEAM_ID'] == $attack) {
       if ($coordx < 10)
         $coordx = $coordx + 1;
       else if ($coordx > 1)
              $coordx = $coordx - 1;
       if ($coordy < 7)
         $coordy = $coordy + 1;
       else if ($coordy > 1)
              $coordy = $coordy - 1;
     } 
     else if ($ouser['TEAM_ID'] == $defence) {
       if ($coordx < $ball_coordx)
         $coordx = $coordx + 1;
       else if ($coordx > 1)
              $coordx = $coordx - 1;
       if ($coordy < $ball_coordy)
         $coordy = $coordy + 1;
       else if ($coordy > 1)
              $coordy = $coordy - 1;
     }

     fwrite($handle, "stage2: ". $coordx." ".$coordy."\n");
     // insert data into table  
     unset($sdata);
     $sdata['BATTLE_ID'] = $battle_id;
     $sdata['USER_ID'] = $ouser['USER_ID'];
     $sdata['USER_TYPE'] = 1;
     $sdata['COORDX'] = $coordx;
     $sdata['COORDY'] = $coordy;
     $sdata['MOVE_ID'] = $total_moves+1;
     $sdata['MOVE_DATE'] = "NOW()"; 
     $sdata['ATTEMPT'] = $scores['ATTEMPTS'];
     $data['TOTAL_MOVES'] = $total_moves+1;

     if ($ouser['TEAM_ID'] == $attack) {
       $sdata['ACTION'] = 1;
     } 
     else if ($ouser['TEAM_ID'] == $defence) {
       $sdata['ACTION'] = 0;
       if (abs($coordx - $ball_coordx) <= 1 
           && abs($coordy - $ball_coordy) <= 1)
         $sdata['ACTION'] = 2;
     }

     $sdata['REACTION'] = 0;
     $sdata['STATUS'] = $status;
//$db->showquery = true;
     $db->insert('ss_battle_events', $sdata);
//echo $db->getNativeErrorText();
     unset($sdata); 
     $sdata['BATTLE_ID'] = $battle_id;
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

     $db->update('ss_battle_status', $sdata, "USER_ID=".$ouser['USER_ID']." AND USER_TYPE=1 AND BATTLE_ID=".$battle_id);
//echo $db->getNativeErrorText();
     unset($sdata);
     $sdata['LAST_MOVE'] = "NOW()"; 
     $db->update('ss_battle', $sdata, "BATTLE_ID=".$battle_id);
    }
   }
   fclose($handle);
   return $data;
 }

 function postMovesUpdates($battle_id, $attack, $defence, $penalty1=0, $penalty2=0) {
  // check if all users are equal
  global $db;
  global $_SESSION;
  global $properties;
  global $cells;
  global $fatigue_attack_action_effect;
  global $fatigue_defense_action_effect;
  global $defense_action_basic_value;
  global $attack_action_basic_value;

   $handle = fopen("ss_log", "a+b");
//echo "postMovesUpdates";
   unset($sdata);
   $sdata['LAST_VISIT'] = "NOW()"; 
   $db->update('ss_users', $sdata, "USER_ID=".$auth->getUserId());

   $db->select('ss_battle', "RESULT1, RESULT2, ATTEMPTS", "BATTLE_ID=".$battle_id);
   $scores = $db->nextRow();

  $sql = "SELECT COUNT(*) NOTMOVED 
            FROM ss_battle_status SBS, ss_battle SB
           WHERE SB.MOVES = SBS.MOVE_ID
                 AND SB.BATTLE_ID = SBS.BATTLE_ID
                 AND SB.BATTLE_ID=".$battle_id;
  $db->query($sql);
  $row = $db->nextRow();

  if ($row['NOTMOVED'] == 0) { // everybody moved, start calculations
       $sql = "SELECT SU.STAMINA, SU.STRENGTH, SU.HEIGHT, SU.WEIGHT, 
                      SU.SPEED, SU.COORDINATION, SU.ENDURANCE, SU.LUCK, SU.INJURY,
                      SBE.*, SBS.MOVED, SBS.ATTACK, SBS.DEFENSE, SBS.BALL, SBS.MOVE_ID, SBS.TEAM_ID
                 FROM ss_users SU, ss_battle_status SBS
                  LEFT JOIN ss_battle_events SBE ON  SBE.USER_ID=SBS.USER_ID 
                                                     AND SBE.USER_TYPE=0
                                                     AND SBE.BATTLE_ID=".$battle_id." 
                                                     AND SBE.MOVE_ID>=SBS.MOVE_ID
                WHERE SU.USER_ID=SBS.USER_ID
                      AND SBS.USER_TYPE=0
                      AND SBS.TEAM_ID=".$attack."
                      AND SBS.BATTLE_ID=".$battle_id."

                UNION

                SELECT SU.STAMINA, SU.STRENGTH, SU.HEIGHT, SU.WEIGHT, 
                      SU.SPEED, SU.COORDINATION, SU.ENDURANCE, SU.LUCK, 0 as INJURY,
                      SBE.*, SBS.MOVED, SBS.ATTACK, SBS.DEFENSE, SBS.BALL, SBS.MOVE_ID, SBS.TEAM_ID
                 FROM ss_bots SU, ss_battle_status SBS
                  LEFT JOIN ss_battle_events SBE ON  SBE.USER_ID=SBS.USER_ID 
                                                     AND SBE.USER_TYPE=1
                                                     AND SBE.BATTLE_ID=".$battle_id." 
                                                     AND SBE.MOVE_ID>=SBS.MOVE_ID
                WHERE SU.USER_ID=SBS.USER_ID
                      AND SBS.USER_TYPE=1
                      AND SBS.TEAM_ID=".$attack."
                      AND SBS.BATTLE_ID=".$battle_id."                  
               ORDER BY EVENT_ID"; 

       $db->query($sql);       
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
                                                     AND SBE.BATTLE_ID=".$battle_id." 
                                                     AND SBE.MOVE_ID>=SBS.MOVE_ID
                WHERE SU.USER_ID=SBS.USER_ID
                      AND SBS.USER_TYPE=0
                      AND SBS.TEAM_ID=".$defence."
                      AND SBS.BATTLE_ID=".$battle_id."                  

                UNION

                SELECT SU.STAMINA, SU.STRENGTH, SU.HEIGHT, SU.WEIGHT, 
                      SU.SPEED, SU.COORDINATION, SU.ENDURANCE, SU.LUCK, 0 as INJURY,
                      SBE.*, SBS.MOVED, SBS.ATTACK, SBS.DEFENSE, SBS.BALL, SBS.MOVE_ID
                 FROM ss_bots SU, ss_battle_status SBS
                  LEFT JOIN ss_battle_events SBE ON  SBE.USER_ID=SBS.USER_ID 
                                                     AND SBE.USER_TYPE=1
                                                     AND SBE.BATTLE_ID=".$battle_id." 
                                                     AND SBE.MOVE_ID>=SBS.MOVE_ID
                WHERE SU.USER_ID=SBS.USER_ID
                      AND SBS.USER_TYPE=1
                      AND SBS.TEAM_ID=".$defence."
                      AND SBS.BATTLE_ID=".$battle_id."                  
               ORDER BY EVENT_ID"; 

       $db->query($sql);       
       while($row_defense = $db->nextRow()) {
         $defense_user[$row_defense['USER_ID']] = $row_defense;
       }

       foreach($attack_user as $auser) {       
         // get inventory effect
         $sql = "SELECT SU.ITEM_ID, SU.EQUIP_POINT, SU.PROP_AFFECTED, SU.ACTION_VALUE
                   FROM ss_users_items SUI, ss_items SU
                  WHERE SUI.USER_ID=".$auser['USER_ID']." 
                        AND (SU.ITEM_TYPE=1 OR SU.ITEM_TYPE=4)
                        AND SUI.EQUIPED=1 
                        AND SU.ITEM_ID=SUI.ITEM_ID";
         $db->query($sql);
         while ($row = $db->nextRow()) {
            $auser[$properties[$row['PROP_AFFECTED']]] = $auser[$properties[$row['PROP_AFFECTED']]] + $row['ACTION_VALUE'];
            $auser['PREVENT_INJURY']++;
         }
         // get skills effect
         $sql = "SELECT SD.ATTR_ID, SUD.LEVEL, SD.PROP_AFFECTED
                   FROM ss_users_da SUD, ss_dynattr SD, ss_battle SB
                  WHERE SUD.USER_ID=".$auser['USER_ID']." 
                        AND SD.ATTR_ID=SUD.ATTR_ID
                        AND SD.SPORT_ID=SB.SPORT_ID 
                        AND SB.BATTLE_ID=".$battle_id;
         $db->query($sql);
         while ($row = $db->nextRow()) {
            $auser[$properties[$row['PROP_AFFECTED']]] = $auser[$properties[$row['PROP_AFFECTED']]] + $row['LEVEL'];
         }
         $auser['FATIGUE'] = 0.1;
         if ($auser['MOVED'] != 5) {
           $auser['FATIGUE'] += 0.02*(50 - $auser['ENDURANCE']);
         } 
         $attack_user[$auser['USER_ID']] = $auser;
       }
       foreach($defense_user as $duser) {       
         // get inventory effect
         $sql = "SELECT SU.ITEM_ID, SU.EQUIP_POINT, SU.PROP_AFFECTED, SU.ACTION_VALUE
                   FROM ss_users_items SUI, ss_items SU
                  WHERE SUI.USER_ID=".$duser['USER_ID']." 
                        AND SUI.EQUIPED=1 
                        AND (SU.ITEM_TYPE=1 OR SU.ITEM_TYPE=4)
                        AND SU.ITEM_ID=SUI.ITEM_ID";
         $db->query($sql);
         while ($row = $db->nextRow()) {
            $duser[$properties[$row['PROP_AFFECTED']]] = $duser[$properties[$row['PROP_AFFECTED']]] + $row['ACTION_VALUE'];
            $duser['PREVENT_INJURY']++;
         }
         // get skills effect
         $sql = "SELECT SD.ATTR_ID, SUD.LEVEL, SD.PROP_AFFECTED
                   FROM ss_users_da SUD, ss_dynattr SD, ss_battle SB
                  WHERE SUD.USER_ID=".$duser['USER_ID']." 
                        AND SD.ATTR_ID=SUD.ATTR_ID
                        AND SD.SPORT_ID=SB.SPORT_ID 
                        AND SB.BATTLE_ID=".$battle_id;
         $db->query($sql);
         while ($row = $db->nextRow()) {
            $duser[$properties[$row['PROP_AFFECTED']]] = $duser[$properties[$row['PROP_AFFECTED']]] + $row['LEVEL'];
         }

         $duser['FATIGUE'] = 0.1;
         if ($duser['MOVED'] != 5) {
             $duser['FATIGUE'] += 0.02*(50 - $duser['ENDURANCE']);
         } 
         $defense_user[$duser['USER_ID']] = $duser;
       }
 
       //echo $coordx. " ". $coordy." ".$field;
       $attack_reaction = 0;
       $defense_reaction = 0;

       foreach($attack_user as $auser) {       
         if ($auser['ATTACK'] > 0) {
           $auser['FATIGUE'] += 0.01*($fatigue_attack_action_effect[$auser['ACTION']] - $auser['ENDURANCE']);
         } 
         $auser['STAMINA'] = $auser['STAMINA'] - $auser['FATIGUE'];
         unset($sdata);
         $sdata['STAMINA'] = $auser['STAMINA'];
         if ($auser['STAMINA'] < 70 - $auser['PREVENT_INJURY']) {
           if (rand(0, 100) < 80-$auser['STAMINA'] ) {
             if ($auser['STAMINA'] > 50 - $auser['PREVENT_INJURY']) {
               $auser['INJURY'] = 1;
             } else if ($auser['STAMINA'] > 30 - $auser['PREVENT_INJURY']) {
               $auser['INJURY'] = 2; 
             }
             else {
               $auser['INJURY'] = 3;
             }
           }
         }

         if ($auser['USER_TYPE'] == 0)
           $db->update('ss_users', $sdata, "USER_ID=".$auser['USER_ID']);
         else if ($auser['USER_TYPE'] == 1)
           $db->update('ss_bots', $sdata, "USER_ID=".$auser['USER_ID']);  
         $attack_user[$auser['USER_ID']] = $auser;
       }

       foreach($defense_user as $duser) {       
         $duser['FATIGUE'] = 0.1;
         if ($duser['DEFENSE'] > 0) {
           $duser['FATIGUE'] += 0.01*($fatigue_defense_action_effect[$duser['ACTION']] - $duser['ENDURANCE']);
         } 
         $duser['STAMINA'] = $duser['STAMINA'] - $duser['FATIGUE'];
         unset($sdata);
         $sdata['STAMINA'] = $duser['STAMINA'];
         if ($duser['STAMINA'] < 70 - $duser['PREVENT_INJURY']) {
           if (rand(0, 100) < 80-$duser['STAMINA'] ) {
             if ($duser['STAMINA'] > 50 - $duser['PREVENT_INJURY'])
               $duser['INJURY'] = 1;
             else if ($duser['STAMINA'] > 30- $duser['PREVENT_INJURY'])
               $duser['INJURY'] = 2;
             else $duser['INJURY'] = 3;
           }
         }

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
           $distance = ($duser['COORDX']-$ball_user['COORDX'])*($duser['COORDX']-$ball_user['COORDX']) + ($duser['COORDY']-$ball_user['COORDY'])*($duser['COORDY']-$ball_user['COORDY']);        
     fwrite($handle, "distance: ".$distance."\n");
           $duser['COORDINATION_X'] = 0.7*$duser['COORDINATION']*2;
           $duser['LUCK_X'] = rand(0, $duser['LUCK']);
           if ($distance > 2) {
             $duser['DEFENSE_ACTION_RESULT'] = -1;
             $duser['DEFENSE_REACTION'] = -1;
           } 
           else {
             $duser['DEFENSE_ACTION_RESULT'] = ($duser['STAMINA']/3 + $duser['COORDINATION_X'] + $duser['LUCK_X']) * $defense_action_basic_value[$duser['ACTION']]; //'DEFENSE' ?
             $duser['DEFENSE_ACTION_RESULT'] = $duser['DEFENSE_ACTION_RESULT'] / (1+$duser['INJURY']);
           }
           $scs_d = rand(0, 100);                 
           $duser['SCS'] = $scs_d;
           if ($scs_d < $duser['DEFENSE_ACTION_RESULT'])
             $duser['DEFENSE_REACTION'] = 1;
           else $duser['DEFENSE_REACTION'] = 0; 
         }
         $defense_user[$duser['USER_ID']] = $duser;
       }

       // calc attack
       foreach($attack_user as $auser)
       {
         if ($auser['BALL'] == 1) {
           $distance_proc = $cells[$auser['COORDY']-1][$auser['COORDX']-1];
     fwrite($handle, "distance_proc: ".$distance_proc."\n");
           $coordination = 0.7*$ball_user['COORDINATION']*2;
           $luck = rand(0, $ball_user['LUCK']);
           $defense_action_result = 0;
           foreach($defense_user as $duser) { 
             if ($duser['DEFENSE_ACTION_RESULT'] > $defense_action_result) {
               $defense_action_result = $duser['DEFENSE_ACTION_RESULT'];
             }     
           } 
           $auser['ATTACK_ACTION_RESULT'] = ($auser['STAMINA']/10 + $coordination + $luck + $distance_proc) * $attack_action_basic_value[$auser['ACTION']] - $defense_action_result/2;  
           $auser['ATTACK_ACTION_RESULT'] = $auser['ATTACK_ACTION_RESULT'] / (1 + $auser['INJURY']);

           $scs_a = rand(0, 100);                   
           $auser['SCS'] = $scs_a;
           if ($scs_a < $auser['ATTACK_ACTION_RESULT'])
             $auser['ATTACK_REACTION']  = 1;  
           $attack_user[$auser['USER_ID']] = $auser;
         }
       }
       $update = false;
    // calculate success of action
       $attack_reaction = 1;
       foreach($defense_user as $duser) {       
         if ($duser['DEFENSE_REACTION'] == 1 && $duser['DEFENSE'] > 0) {
           $attack_reaction = 0;
           $update = true;
           break;
         }
       }

       foreach($attack_user as $auser)
       {
         if ($auser['BALL'] == 1) {
           unset($sdata);
           $sdata['REACTION'] = $auser['ATTACK_REACTION'] * $attack_reaction;
           $sdata['ACTION_RESULT'] = $auser['ATTACK_ACTION_RESULT'];
           $sdata['DICE']= $auser['SCS'];
           $db->update('ss_battle_events', $sdata, "BATTLE_ID=".$battle_id." AND MOVE_ID=".$total_moves." AND USER_TYPE=".$auser['USER_TYPE']." AND USER_ID=".$auser['USER_ID']);
           if ($auser['ATTACK_REACTION'] * $attack_reaction == 1) {
             if ($auser['TEAM_ID'] == 1) 
               $scores['RESULT1'] += 1;
             else $scores['RESULT2'] += 1;
             $update = true;
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
         $db->update('ss_battle_events', $sdata, "BATTLE_ID=".$battle_id." AND MOVE_ID=".$total_moves." AND USER_TYPE=".$duser['USER_TYPE']." AND USER_ID=".$duser['USER_ID']);
       }

       fwrite($handle, "result: ". $attack_user[0]['COORDX']." ".$attack_user[0]['COORDY']." ".abs($attack_user[0]['COORDX']-5)." ".abs($attack_user[0]['COORDY']-3)."\n");
       if ($update ) {
         unset($sdata);
         $sdata['RESULT1'] = $scores['RESULT1'] + $penalty2;
         $sdata['RESULT2'] = $scores['RESULT2'] + $penalty1;
         $sdata['ATTEMPTS'] = $scores['ATTEMPTS']+1;
         $db->update('ss_battle', $sdata, "BATTLE_ID=".$battle_id);
         initStartPositions($battle_id, $total_moves+1, $sdata['ATTEMPTS'], $defence, $attack);
       }
       else if ($penalty1 > 0 || $penalty2 > 0) {
         unset($sdata);
         $sdata['RESULT1'] = $scores['RESULT1'] + $penalty2;
         $sdata['RESULT2'] = $scores['RESULT2'] + $penalty1;
         $sdata['ATTEMPTS'] = $scores['ATTEMPTS']+1;
         $sdata['MOVES'] = $total_moves;
         $sdata['LAST_MOVE'] = "NOW()"; 
         $sdata['LAST_MOVE_START'] = "NOW()"; 
         $db->update('ss_battle', $sdata, "BATTLE_ID=".$battle_id);
       }
       else {
         unset($sdata);
         $sdata['MOVES'] = $total_moves;
         $sdata['LAST_MOVE'] = "NOW()"; 
         $sdata['LAST_MOVE_START'] = "NOW()"; 
         $db->update('ss_battle', $sdata, "BATTLE_ID=".$battle_id);
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
  
 function handleTimeout($battle_id) {
   global $duel_timeout;
   global $db;

   $penalty[1] = 0;
   $penalty[2] = 0;


   $sql="SELECT MOVES FROM ss_battle
             WHERE BATTLE_ID = ".$battle_id;
   $db->query($sql);
   $row = $db->nextRow();
   $moves = $row['MOVES'];

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
           WHERE SBS.BATTLE_ID = ".$battle_id." 
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
       $sdata['BATTLE_ID'] = $battle_id;
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
       $db->update('ss_battle_status', $sdata, "BATTLE_ID=".$battle_id." AND USER_ID=".$player['USER_ID']." AND USER_TYPE=".$player['USER_TYPE']);
       
   }
   if (isset($moves)) {
     unset($sdata);
//     $sdata['LAST_MOVE'] = "NOW()"; 
//     $sdata['LAST_MOVE_START'] = "NOW()"; 
//     $sdata['MOVES'] = $moves;
//     $sdata['ATTEMPTS'] = $attempts+1;
     $db->update('ss_battle', $sdata, "BATTLE_ID=".$battle_id);
   }
//echo $c;
//exit;

   $data['OUTCOME'] = true;
   $data['PENALTY'][1] = $penalty[1];
   $data['PENALTY'][2] = $penalty[2];

if ($penalty[1] == $penalty[2]) {
  //print_r($data);
//  exit;
}

   return $data;
 }

?>