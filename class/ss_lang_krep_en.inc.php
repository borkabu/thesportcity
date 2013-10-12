<?php


 $attack_actions_explained = array (-1 => 'Positional attack',
                                    0 => 'Dribbled the ball', 
                              	    1 => 'Shooting attempt',
                                    2 => 'Attempt to pass',
                                    10 => 'Timeout');

 $defense_actions_explained = array ( -1 => 'Positional defense',
                                      0 => 'Keeps eye on a ball',  				      
                                      1 => 'Appealing to referree',  				      
                                      2 => 'Attempt to steal',
                                      3 => 'Attempt to block',
                                      10 => 'Timeout');


 $reactions_explained = array ( 0 => 'Failure', 
				1 => 'Success');


 $langs_sport= array (
                'KREP_LANG_BLOCK_U' => 'Block',
                'KREP_LANG_DRIBBLE_U' => 'Dribble',
                'KREP_LANG_POSITIONAL_ATTACK_ONLY_U' => 'Positional attack only',
                'KREP_LANG_POSITIONAL_DEFENCE_ONLY_U' => 'Positional defence only',
                'KREP_LANG_PASS_TO_U' => 'Pass to',
                'KREP_LANG_SHOOT_U' => 'Shoot',
                'KREP_LANG_STEAL_U' => 'Steal',
 );


 while (list($key, $val) = each($langs_sport)) {
     $data[$key] = $val;
 }

?>