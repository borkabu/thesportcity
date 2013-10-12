<?php


 $attack_actions_explained = array (-1 => 'Posicine ataka',
                                    0 => 'Kamuolio varymas', 
                              	    1 => 'Bandymas mesti',
                                    2 => 'Bandymas pasuoti',
                                    10 => 'Timeout');

 $defense_actions_explained = array ( -1 => 'Posicinis gynimas',
                                      0 => 'Stebi kamuoli',  
                                      1 => 'Skundzhiasi teisejui',
                                      2 => 'Bandymas perimti',
                                      3 => 'Bandymas blokuoti',
                                      10 => 'Timeout');


 $reactions_explained = array ( 0 => 'Nesekme', 
				1 => 'Sekme');


 $langs_sport= array (
                'KREP_LANG_BLOCK_U' => 'Blokuoti',
                'KREP_LANG_DRIBBLE_U' => 'Varytis',
                'KREP_LANG_POSITIONAL_ATTACK_ONLY_U' => 'Posicine ataka',
                'KREP_LANG_POSITIONAL_DEFENCE_ONLY_U' => 'Posicine gynyba',
                'KREP_LANG_PASS_TO_U' => 'Passuoti',
                'KREP_LANG_SHOOT_U' => 'Mesti',
                'KREP_LANG_STEAL_U' => 'Perimti',
 );

 while (list($key, $val) = each($langs_sport)) {
     $data[$key] = $val;
 }

?>