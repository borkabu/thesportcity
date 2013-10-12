<?php 

 $locations = array( 1 => array (1 => 7, 0 => 8, -1 => 9),
                     0 => array (1 => 4, 0 => 5, -1 => 6),
                    -1 => array (1 => 1, 0 => 2, -1 => 3));

 $fields = array( 1 => array (0 => -1, 1 => 1),
                  2 => array (0 => 0, 1 => 1),
                  3 => array (0 => 1, 1 => 1),
		  4 => array (0 => -1, 1 => 0),
                  5 => array (0 => 0, 1 => 0),
                  6 => array (0 => 1, 1 => 0),
		  7 => array (0 => -1, 1 => -1),
                  8 => array (0 => 0, 1 => -1),
                  9 => array (0 => 1, 1 => -1));

 $actions = array ('attack' => array ('shoot' => 1, 'dribble' => 0, 'pass' => 2),
                   'defense' => array ('krep_defense_1' => 2, 'krep_defense_2' => 3));

 $cells = array(array (10, 11, 12, 14, 15),
                array (11, 12, 13, 16, 17),
                array (13, 14, 17, 18, 20),
		array (11, 12, 13, 16, 17),
                array (10, 11, 12, 14, 15));

 $fatigue_attack_action_effect = array(-1 => 30,
                                       0 => 50,
                                       1 => 80,
                                       2 => 50,
                                      10 => 500);

 $fatigue_defense_action_effect = array(-1 => 20,
					0 => 30,
                                        2 => 50,
                                        3 => 60,
                                       10 => 500);

 $attack_action_basic_value = array(-1 => 0,
				    0 => 0,
                                    1 => 0.9,
                                    2 => 100, 
                                    10 => 0);

 $defense_action_basic_value = array(-1 => 0,
                                     0 => 0,
                                     2 => 0.6,
                                     3 => 0.7, 
                                    10 => 0);

 $table_size = array ( 1=> array(5,5, 300, 300),
                       2=> array(5,5, 300, 300),
                       3=> array(5,5, 300, 300),
		       4=> array(5,5, 300, 300));

 $cell_size = array ( 1=> array(60,60),
                      2=> array(60,60),
                      3=> array(60,60),
		      4=> array(60,60));

 $basket_coord = array ( 1=> array(5,3),
                         2=> array(5,3),
                         3=> array(5,3),
			 4=> array(5,3));

 function prepareTable($current_user, $type, $waiting, $other_users, $attack, $defence, $battle_type) {
   global $locations;  
   global $table_size;
   global $cell_size;

   $table_width = $table_size[$battle_type][0];
   $table_heigth = $table_size[$battle_type][1];
   $cell_width = $cell_size[$battle_type][0];
   $cell_heigth = $cell_size[$battle_type][1];
   for ($i = 1; $i < $table_width + 1; $i++) {
     for ($j = 1; $j < $table_heigth + 1; $j++) {
       if ((abs($current_user['CURX']) - abs($j)) <= 1 && (abs($current_user['CURY']) - abs($i)) <= 1 ) {
        if (!empty($locations[$current_user['CURY'] - $i][$current_user['CURX'] - $j]) && !$waiting) {
         $data['ROWS'][$i]['COLS'][$j]['BUTTON'][0]['VALUE'] = $locations[$current_user['CURY'] - $i][$current_user['CURX'] - $j];
          $data['ROWS'][$i]['COLS'][$j]['WIDTH'] = $cell_width; 
          $data['ROWS'][$i]['COLS'][$j]['HEIGHT'] = $cell_heigth; 
         if ( $data['ROWS'][$i]['COLS'][$j]['BUTTON'][0]['VALUE'] == 5)
           $data['ROWS'][$i]['COLS'][$j]['BUTTON'][0]['CHECKED'] = "checked";
        }
        else {
          $data['ROWS'][$i]['COLS'][$j]['X'] = 1; 
          $data['ROWS'][$i]['COLS'][$j]['WIDTH'] = $cell_width; 
          $data['ROWS'][$i]['COLS'][$j]['HEIGHT'] = $cell_heigth; 
        } 
       }  
       else {
         $data['ROWS'][$i]['COLS'][$j]['X'] = 1;
          $data['ROWS'][$i]['COLS'][$j]['WIDTH'] = $cell_width; 
          $data['ROWS'][$i]['COLS'][$j]['HEIGHT'] = $cell_heigth;
       }
     }
   }
  if (!isset($data['ROWS'][$current_user['CURX']]['COLS'][$current_user['CURY']]['NAMES']))
    $data['ROWS'][$current_user['CURX']]['COLS'][$current_user['CURY']]['NAMES'] = $current_user['USER_NAME']."\n";
  else
    $data['ROWS'][$current_user['CURX']]['COLS'][$current_user['CURY']]['NAMES'] .= $current_user['USER_NAME']."\n";
  $table = '';
  for ($i = 1; $i < $table_width + 1; $i++) {
     for ($j = 1; $j < $table_heigth + 1; $j++) {
       $table[$i][$j]['ATTACKBALL'] = 0;
       $table[$i][$j]['ATTACK'] = 0;
       $table[$i][$j]['DEFENSE'] = 0;
     }
  }
  foreach($other_users as $ouser) {
    if (!isset($data['ROWS'][$ouser['CURY']]['COLS'][$ouser['CURX']]['NAMES']))
      $data['ROWS'][$ouser['CURY']]['COLS'][$ouser['CURX']]['NAMES'] = $ouser['USER_NAME']."\n";
    else $data['ROWS'][$ouser['CURY']]['COLS'][$ouser['CURX']]['NAMES'] .= $ouser['USER_NAME']."\n";
    if ($ouser['TEAM_ID'] == $attack && $ouser['BALL'] == 1) {
      $table[$ouser['CURY']][$ouser['CURX']]['ATTACKBALL']++; 
    }
    else if ($ouser['TEAM_ID'] == $attack) {
      $table[$ouser['CURY']][$ouser['CURX']]['ATTACK']++ ; 
    } 
    else if ($ouser['TEAM_ID'] == $defence) {
      $table[$ouser['CURY']][$ouser['CURX']]['DEFENSE']++ ; 
    } 
  }
  if ($current_user['TEAM_ID'] == $attack && $current_user['BALL'] == 1) {
    $table[$current_user['CURY']][$current_user['CURX']]['ATTACKBALL']++; 
  }
  else if ($current_user['TEAM_ID'] == $attack) {
    $table[$current_user['CURY']][$current_user['CURX']]['ATTACK']++ ; 
  } 
  else if ($current_user['TEAM_ID'] == $defence) {
    $table[$current_user['CURY']][$current_user['CURX']]['DEFENSE']++ ; 
  } 

//print_r($table);
  for ($i = 1; $i < $table_width + 1; $i++) {
     for ($j = 1; $j < $table_heigth + 1; $j++) {
       if ($table[$i][$j]['ATTACKBALL'] == 1 && $table[$i][$j]['ATTACK'] > 0 && $table[$i][$j]['DEFENSE'] > 0
           && $table[$i][$j]['ATTACK'] + $table[$i][$j]['DEFENSE'] >= 2) {
         $data['ROWS'][$i]['COLS'][$j]['ATTACKBALLDEFENSE_X'][0]['SPORT'] = 'basketball'; 
       } else if ($table[$i][$j]['ATTACKBALL'] == 1 && $table[$i][$j]['ATTACK'] > 0) {
         $data['ROWS'][$i]['COLS'][$j]['ATTACKBALL_X'][0]['SPORT'] = 'basketball'; 
       } else if ($table[$i][$j]['ATTACK'] == 1 && $table[$i][$j]['DEFENSE'] == 1) {
         $data['ROWS'][$i]['COLS'][$j]['ATTACKDEFENSE'][0]['SPORT'] = 'basketball'; 
       } else if ($table[$i][$j]['ATTACK'] >= 1 && $table[$i][$j]['DEFENSE'] >= 1) {
         $data['ROWS'][$i]['COLS'][$j]['ATTACKDEFENSE_X'][0]['SPORT'] = 'basketball';  
       } else if ($table[$i][$j]['ATTACKBALL'] > 0 && $table[$i][$j]['DEFENSE'] > 0) {
         $data['ROWS'][$i]['COLS'][$j]['ATTACKBALLDEFENSE'][0]['SPORT'] = 'basketball'; 
       } else if ($table[$i][$j]['ATTACKBALL'] > 0) {
         $data['ROWS'][$i]['COLS'][$j]['ATTACKBALL'][0]['SPORT'] = 'basketball'; 
       } else if ($table[$i][$j]['ATTACK'] > 1) {
         $data['ROWS'][$i]['COLS'][$j]['ATTACK_X'][0]['SPORT'] = 'basketball'; 
       } else if ($table[$i][$j]['ATTACK'] == 1) {
         $data['ROWS'][$i]['COLS'][$j]['ATTACK'][0]['SPORT'] = 'basketball'; 
       } else if ($table[$i][$j]['DEFENSE'] > 1) {
         $data['ROWS'][$i]['COLS'][$j]['DEFENSE_X'][0]['SPORT'] = 'basketball'; 
       } else if ($table[$i][$j]['DEFENSE'] == 1) {
         $data['ROWS'][$i]['COLS'][$j]['DEFENSE'][0]['SPORT'] = 'basketball'; 
       }

     }
  }
  return $data['ROWS'];
 }

 function drawBoard($current_user, $other_users, $waiting, $attack, $defence, $battle_type) {
   global $auth;
   global $table_size;

     if ($current_user['USER_ID'] == $auth->getUserId()
         && $current_user['TEAM_ID'] == $attack) { // home
       // create table
       $data['ROWS'] = prepareTable($current_user, 0, $waiting, $other_users, $attack, $defence, $battle_type);
       if ($waiting) {
         $data['WAITING'] = 1;
       } 
       else { 
         if ($current_user['BALL'] == 0) {
           $data['ATTACK'][0]['NOBALL'] = 1;
         }
         else {
           $data['ATTACK'][0]['BALL'][0]['X'] = 1;
           if ($battle_type == 3 || $battle_type == 4) {
             $c = 0;
             foreach($other_users as $ouser) {
               if ($current_user['TEAM_ID'] == $ouser['TEAM_ID']) {
                 $data['ATTACK'][0]['BALL'][0]['PASS'][$c] = $ouser;
                 $c++;
               }
             }
           }
         }
       }
     }
     else if ($current_user['USER_ID'] == $auth->getUserId()
              && $current_user['TEAM_ID'] == $defence) { // away
       $data['ROWS'] = prepareTable($current_user, 1, $waiting, $other_users, $attack, $defence, $battle_type);
       if ($waiting) {
         $data['WAITING'] = 1;
       } 
       else {
         foreach($other_users as $ouser) {
           if ($ouser['BALL'] == 1) {
             if ($ouser['CURX'] == $current_user['CURX'] &&
                 $ouser['CURY'] == $current_user['CURY'])
               $data['DEFENSE'][0]['BLOCK'] = 1;
             if (abs($ouser['CURX'] - $current_user['CURX']) <=1 &&
                      abs($ouser['CURY'] - $current_user['CURY']) <= 1)
               $data['DEFENSE'][0]['STEAL'] = 1;            
           }
         }
         if (!isset($data['DEFENSE'][0]['BLOCK']) 
             && !isset($data['DEFENSE'][0]['STEAL']))
           $data['DEFENSE'][0]['NOBALL'] = 1;
       } 
     } 
     // create table
   $data['FIELD'] ='bcourt';

   $table_width_px = $table_size[$battle_type][2];
   $table_height_px = $table_size[$battle_type][3];

   $data['WIDTH'] =$table_width_px;
   $data['HEIGHT'] =$table_height_px;
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
  
?>