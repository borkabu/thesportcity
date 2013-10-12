<?php

 $duel_timeout = 100;
 $battle_timeout = 500;
 $max_attempts = 5; 
 
 define('SS_HOME', 0);
 define('SS_PLAYGROUND', 1);
 define('SS_BATTLE', 2);
 define('SS_OUTSIDE', 3);
 define('SS_SHOP', 4);
 define('SS_GYM', 5);
 define('SS_KIOSK', 6);

 $locations= array (0 => 'ss_home.php',
                    1 => 'ss_battles.php',
                    2 => 'ss_battles.php',
                    3 => 'ss_outside.php',
                    4 => 'ss_shop.php',
                    5 => 'ss_gym.php',
		    6 => 'ss_kiosk.php');

 $properties=array( 1 => 'STAMINA',
                    2 => 'INJURY',
                    3 => 'LUCK', 
                    4 => 'STRENGTH',
                    5 => 'COORDINATION',
		    6 => 'ENDURANCE',
		    7 => 'SPEED');

 $properties_l=array( 1 => 'LANG_STAMINA_U',
                      2 => 'LANG_INJURY_U',
                      3 => 'LANG_LUCK_U', 
                      4 => 'LANG_STRENGTH_U',
                      5 => 'LANG_COORDINATION_U',
                      6 => 'LANG_ENDURANCE_U',
		      7 => 'LANG_SPEED_U');
  
 $log_events=array(0 => '',
                   1 => 'Bought item,',
                   2 => 'Sold item',
                   3 => 'Trained skill',
                   4 => 'Won game',
                   5 => 'Lost game',
                   6 => 'Draw game'
                   );

 $slots_equip = array (1 => 1,
                       2 => 2,
                       3 => 1,
                       4 => 1,
                       5 => 1,
                       6 => 1,
                       7 => 2,
                       8 => 3,
                       9 => 3);

 $level_price = array (1 => 10,
                       2 => 25,
                       3 => 50,
                       4 => 80,
                       5 => 115);



 $level_prize = array (0 => 1,
                       1 => 2,
                       2 => 3,
                       3 => 4,
                       4 => 5
                      );

 $level_fine = array (0 => 0,
                       1 => 1,
                       2 => 2,
                       3 => 3,
                       4 => 4
                      );

?>