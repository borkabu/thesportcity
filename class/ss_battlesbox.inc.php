<?php

class SS_BattleBox extends Box {

  function SS_BattleBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getCurrentBattlesBox ($page=1,$perpage=PAGE_SIZE) {
    global $tpl;
    global $db;
   
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/bar_curbattles.tpl.html');
    $this->data['CUR_BATTLES'][0] = $this->getCurrentBattles($page, $perpage);
    $this->rows = $this->data['CUR_BATTLES'][0]['_ROWS'];	
//print_r($this->data['CUR_BATTLES']);
    $tpl->addData($this->data);
    return $tpl->parse();
  }


  function getCurrentBattles ($page=1,$perpage=PAGE_SIZE) {
      global $db;
      global $sports;
      global $battle_types;


      $sql = "SELECT SB.BATTLE_ID
              FROM ss_battle SB
              WHERE SB.STATUS=2 OR SB.STATUS=3
               ORDER BY SB.BATTLE_ID DESC
              LIMIT ".$perpage;
    
      $db->query($sql);
      $c = 0;
      $pre="";
      $cur_battles = "";
      while ($row = $db->nextRow()) {
        $cur_battles .= $pre.$row['BATTLE_ID'];
        $pre=",";
      }
    
      $sql = "SELECT SB.*, U.USER_NAME, SBS.TEAM_ID, SBS.USER_ID
              FROM ss_battle SB
               LEFT JOIN ss_battle_status SBS on SB.BATTLE_ID=SBS.BATTLE_ID
               LEFT JOIN users U on U.USER_ID = SBS.USER_ID 
               WHERE SB.BATTLE_ID IN (".$cur_battles.") AND SBS.USER_TYPE=0
    
              UNION
    
              SELECT SB.*, 'BOT' USER_NAME, SBS.TEAM_ID, SBS.USER_ID
              FROM ss_battle SB
               LEFT JOIN ss_battle_status SBS on SB.BATTLE_ID=SBS.BATTLE_ID
                      AND SBS.USER_TYPE=1
               WHERE SB.BATTLE_ID IN (".$cur_battles.")";
      $db->query($sql);
      $c = 0;
      while ($row = $db->nextRow()) {
        $data['ITEMS'][$row['BATTLE_ID']]['BATTLE_ID'] = $row['BATTLE_ID'];
        $data['ITEMS'][$row['BATTLE_ID']]['SPORT'] = $sports[$row['SPORT_ID']];
        $data['ITEMS'][$row['BATTLE_ID']]['BATTLE_TYPE'] = $battle_types[$row['BATTLE_TYPE']];
        if (isset($data[$row['BATTLE_ID']]['TEAM'.$row['TEAM_ID']]['PRE'.$row['TEAM_ID']]))
          $data['ITEMS'][$row['BATTLE_ID']]['TEAM'.$row['TEAM_ID']][$row['TEAM_ID']]['USERS'][$c]['USER_NAME'] .= $data[$row['BATTLE_ID']]['TEAM'.$row['TEAM_ID']]['PRE'.$row['TEAM_ID']].$row['USER_NAME'];
        else $data['ITEMS'][$row['BATTLE_ID']]['TEAM'.$row['TEAM_ID']][$row['TEAM_ID']]['USERS'][$c]['USER_NAME'] .= $row['USER_NAME'];
//////    $data['ITEMS'][$row['BATTLE_ID']]['TIMELEFT'] = $battle_timeout - $row['TIMESPENT'];
        $data[$row['BATTLE_ID']]['TEAM'.$row['TEAM_ID']]['PRE'.$row['TEAM_ID']] = ",";
        $c++;
      }
      $db->free();
      $data['_ROWS'] = $perpage;
      return $data;    
  }
}

?>