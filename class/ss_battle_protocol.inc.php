<?php

class SS_Battle_Protocol extends Box {

  function SS_Battle_Protocol($langs, $lang) {
    parent::Box($langs, $lang);
  }


  function getBattleProtocolBox ($battle_id, $user1, $user2, $res1, $res2, $limit=1) {
    global $tpl;
    global $db;
   
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/ss_battle_protocol.tpl.html');
    $this->data['PROTOCOL'][0] = $this->getProtocol($battle_id, $user1, $user2, $res1, $res2, $limit);
//print_r($this->data['CUR_BATTLES']);
    $tpl->addData($this->data);
    return $tpl->parse();
  }

  function getProtocol($battle_id, $user1, $user2, $res1, $res2, $limit=1) {
    global $db;
    global $reactions_explained;
    global $attack_actions_explained;
    global $defense_actions_explained;
    global $_SESSION;
    global $auth;
 
    $data = '';

    $data['RESULT1'] = $res1;
    $data['RESULT2'] = $res2;

    $sql = "SELECT U.USER_NAME, SBS.TEAM_ID, SBS.USER_ID
           FROM ss_battle_status SBS 
            LEFT JOIN users U on U.USER_ID = SBS.USER_ID AND SBS.USER_TYPE=0
            WHERE SBS.USER_TYPE =0
                  AND SBS.BATTLE_ID =".$battle_id." 
 
            UNION
 
           SELECT 'BOT' as USER_NAME, SBS.TEAM_ID, SBS.USER_ID
             FROM ss_battle_status SBS 
           WHERE SBS.USER_TYPE=1
           and SBS.BATTLE_ID =".$battle_id;
   $db->query($sql);
   $c = 0;
   while ($row = $db->nextRow()) {
    if ($row['TEAM_ID'] == 1) { 
      $data['HOME'][$c]['USER_NAME'] = $row['USER_NAME'];
    } else { 
      $data['VISITER'][$c]['USER_NAME'] = $row['USER_NAME'];
    }
     $c++;
    
   }
   $db->free();
 
 
    $sql="SELECT MOVES FROM ss_battle
              WHERE BATTLE_ID = ".$battle_id;
    $db->query($sql);
    $row = $db->nextRow();
    $moves = $row['MOVES'];
    $sql = "SELECT SBE.USER_ID, U.USER_NAME, SBS.TEAM_ID, SBE.STATUS, SBE.ATTEMPT,
                   SBE.MOVE_ID, SBE.ACTION, SBE.REACTION
              FROM ss_battle SB, ss_battle_status SBS
                   LEFT JOIN ss_battle_events SBE ON
                    SBS.BATTLE_ID = SBE.BATTLE_ID
                    AND SBE.USER_ID = SBS.USER_ID
                    AND SBE.USER_TYPE = SBS.USER_TYPE
                    AND ((SBE.MOVE_ID<=".$moves.")  OR (SBE.USER_TYPE=0))
                   LEFT JOIN users U ON U.USER_ID=SBE.USER_ID AND SBE.USER_TYPE=0
              WHERE SB.BATTLE_ID = ".$battle_id."
                    AND SBS.BATTLE_ID=SB.BATTLE_ID 
                    AND SBE.MOVE_ID > ".($moves-$limit)."
             ORDER BY SBE.MOVE_ID DESC";
    $db->query($sql);
    while ($row = $db->nextRow()) {
      $data['MOVES'][$row['MOVE_ID']]['NUMBER'] = $row['MOVE_ID'];
      $data['MOVES'][$row['MOVE_ID']]['ROUND'] = $row['ATTEMPT'] +1;
      if ($row['TEAM_ID'] == 1) {
        if (!empty($row['USER_NAME']))
          $data['MOVES'][$row['MOVE_ID']]['HOME'][$row['USER_ID']]['USER_NAME'] = $row['USER_NAME'];
        else $data['MOVES'][$row['MOVE_ID']]['HOME'][$row['USER_ID']]['USER_NAME'] = 'Bot'.$row['USER_ID']; 
        if ($row['STATUS'] == 1) {
          $data['MOVES'][$row['MOVE_ID']]['HOME'][$row['USER_ID']]['MOVE'] = $attack_actions_explained[$row['ACTION']];
          if ($row['ACTION'] > 0) {
            $data['MOVES'][$row['MOVE_ID']]['HOME'][$row['USER_ID']]['REACTION_'.$row['REACTION']][0]['R'] = $reactions_explained[$row['REACTION']];
          }
        } else {
          $data['MOVES'][$row['MOVE_ID']]['HOME'][$row['USER_ID']]['MOVE'] = $defense_actions_explained[$row['ACTION']];
          if ($row['ACTION'] > 0) {
            $data['MOVES'][$row['MOVE_ID']]['HOME'][$row['USER_ID']]['REACTION_'.$row['REACTION']][0]['R'] = $reactions_explained[$row['REACTION']];
          }
        }
      } else {
        if (!empty($row['USER_NAME']))
          $data['MOVES'][$row['MOVE_ID']]['VISITER'][$row['USER_ID']]['USER_NAME'] = $row['USER_NAME'];
        else $data['MOVES'][$row['MOVE_ID']]['VISITER'][$row['USER_ID']]['USER_NAME'] = 'Bot'.$row['USER_ID']; 
        if ($row['STATUS'] == 1) {
          $data['MOVES'][$row['MOVE_ID']]['VISITER'][$row['USER_ID']]['MOVE'] = $attack_actions_explained[$row['ACTION']];
          if ($row['ACTION'] > 0) {
            $data['MOVES'][$row['MOVE_ID']]['VISITER'][$row['USER_ID']]['REACTION_'.$row['REACTION']][0]['R'] = $reactions_explained[$row['REACTION']];
          }
        } else {	
          $data['MOVES'][$row['MOVE_ID']]['VISITER'][$row['USER_ID']]['MOVE'] = $defense_actions_explained[$row['ACTION']];
          if ($row['ACTION'] > 0) {
            $data['MOVES'][$row['MOVE_ID']]['VISITER'][$row['USER_ID']]['REACTION_'.$row['REACTION']][0]['R'] = $reactions_explained[$row['REACTION']];
          }  
        }
      }
    }
    return $data;                               
  }

}