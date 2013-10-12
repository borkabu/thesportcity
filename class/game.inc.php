<?php
/*
===============================================================================
events.inc.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - generate event list data

TABLES USED: 
  - BASKET.EVENTS

STATUS:
  - [STAT:INPRGS] in progress

TODO:
  - [TODO:PUBVAR] main functionality
===============================================================================
*/

class Game {
   var $game_id;
//   var $data;
   var $sport_id;
   var $team_names;
  
   function Game($game_id) {
  //    global $data;

      $this->game_id = $game_id;
    //  $this->data = $data;
   }

   function getGameStats() {
      global $db;
      global $smarty;
      global $result_types_short;
      global $result_types;
      global $_SESSION;
      global $auth;
    
        // generate team statistics
      
      $sql = "SELECT G.GAME_ID, G.PUBLISH, T1.TEAM_ID as TEAM_ID1, T2.TEAM_ID as TEAM_ID2,
		 IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
		 DATE_ADD(G.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE, 
              	 SD.SEASON_TITLE, S.SPORT_ID
               FROM seasons S
                   left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id']."
   		   , tournaments TR, games G
		    LEFT JOIN teams T1 ON G.TEAM_ID1 = T1.TEAM_ID
			LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
		    LEFT JOIN teams T2 ON G.TEAM_ID2 = T2.TEAM_ID
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
               WHERE G.GAME_ID = ".$this->game_id."
                     AND G.SEASON_ID=S.SEASON_ID 
                     AND S.TOURNAMENT_ID=TR.TOURNAMENT_ID";
      $db->query($sql);
      $row = $db->nextRow();
      $this->sport_id   = $row['SPORT_ID'];
    
        $stats['TEAM_NAME1']   = $row['TEAM_NAME1'];
        $stats['TEAM_NAME2']   = $row['TEAM_NAME2'];
        $this->team_names[$row['TEAM_ID1']] = $row['TEAM_NAME1'];
        $this->team_names[$row['TEAM_ID2']] = $row['TEAM_NAME2'];
        $stats['SEASON_TITLE'] = $row['SEASON_TITLE'];
        $stats['DATE']         = $row['START_DATE']." ".$auth->getUserTimezoneName();
        
        if ($this->sport_id==1)
	  $this->getStatsBasketball($stats);
        else if ($this->sport_id==2)
	  $this->getStatsFootball($stats);

	$keys = array_keys($result_types_short[$this->sport_id]);
	for ($i = 0; $i < count($result_types_short[$this->sport_id]); $i++) {
	  $legend['KEY'] = $result_types_short[$this->sport_id][$keys[$i]];
	  $legend['LEGEND'] = $result_types[$this->sport_id][$keys[$i]];
          $legends[] = $legend;
        }
        
        // add data
        $smarty->assign("stats", $stats);
        $smarty->assign("legends", $legends);

      $start = getmicrotime();
      $output = $smarty->fetch('smarty_tpl/gamestats'.$this->sport_id.'.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/gamestats'.$this->sport_id.'.smarty<br>'.($stop-$start);
      return $output;

   }

   function getStatsBasketball(&$stats) {
      global $db;
      global $result_types_short;
      global $result_types;
      global $_SESSION;
      global $auth;

        // build stats
      $sql = 'SELECT 
                  U.FIRST_NAME, U.LAST_NAME, 
                  R.TEAM_ID, T.TEAM_NAME, 
                  R.PT2_SCORED PT2_S, R.PT2_THROWN PT2_T,
                  R.PT3_SCORED PT3_S, R.PT3_THROWN PT3_T,
                  R.PT1_SCORED PT1_S, R.PT1_THROWN PT1_T,
                  R.SCORE,
                  R.PLAYED,
                  (0) PT2,
                  (0) PT2_PERCENT,
                  (0) PT3,
                  (0) PT3_PERCENT,
                  (0) PT1,
                  (0) PT1_PERCENT,
                  R.REBOUNDS,
                  R.ASSISTS,
                  R.BLOCKS,
                  R.UNBLOCKS,
                  R.STEALS,
                  R.MISTAKES,
                  R.FAULS,  
                  R.UNFAULS,
                  R.KOEFF
                FROM 
                  games G, results R
                  LEFT JOIN busers U ON R.USER_ID=U.USER_ID
                  LEFT JOIN teams T ON R.TEAM_ID=T.TEAM_ID
                WHERE 
                  G.GAME_ID='.$this->game_id.'
                  AND R.GAME_ID=G.GAME_ID
                ORDER BY
                  R.TEAM_ID, U.FIRST_NAME, U.LAST_NAME
                ';
        $db->query($sql);
        $t = -1;
        $teamid = 0;
        while ($row = $db->nextRow()) {
          $i = 0;
	  $statrow = array();
          $statrowitem = array();
          if ($teamid != $row['TEAM_ID']) {
            $teamid = $row['TEAM_ID'];
            $tt = TRUE;
            $c = 0;
            $t++;
          }
          
          if (empty($row['LAST_NAME'])) {
            // last name empty - team stats
            $prt = 'TEAM';
            $c--;
          }
          else {
            $prt = 'PLAYER';
          }
                   
          $row['PT2_S'] = evalInt($row['PT2_S']);
          $row['PT2_T'] = evalInt($row['PT2_T']);
          $row['PT3_S'] = evalInt($row['PT3_S']);
          $row['PT3_T'] = evalInt($row['PT3_T']);
          $row['PT1_S'] = evalInt($row['PT1_S']);
          $row['PT1_T'] = evalInt($row['PT1_T']);
          
          $row['PT2'] = round($row['PT2_S'], 2).'/'.$row['PT2_T'];
          $row['PT3'] = round($row['PT3_S'], 2).'/'.$row['PT3_T'];
          $row['PT1'] = round($row['PT1_S'], 2).'/'.$row['PT1_T'];
          
          // 2 point percentage calculation
          if ($row['PT2_T'] == 0)
            $row['PT2_PERCENT'] = '-';
          else
            $row['PT2_PERCENT'] = round($row['PT2_S']/($row['PT2_T']/100)).'%';
          
          // 3 point percentage calculation
          if ($row['PT3_T'] == 0)
            $row['PT3_PERCENT'] = '-';
          else
            $row['PT3_PERCENT'] = round($row['PT3_S']/($row['PT3_T']/100)).'%';
          
          // faul percentage calculation
          if ($row['PT1_T'] == 0)
            $row['PT1_PERCENT'] = '-';
          else
            $row['PT1_PERCENT'] = round($row['PT1_S']/($row['PT1_T']/100)).'%';
          $value = array();          
          while (list($key, $val) = each($row)) {
           if(!is_numeric($key)) {
            if ($i > 9) {
              // add column title
              if ($tt) {
                $title['TITLE'] = $result_types_short[$this->sport_id][$key];
                $team[$t]['ITEM'][] = $title;
                $team[$t]['TEAM_NAME'] = $this->team_names[$row['TEAM_ID']];
              }
              
              if (!empty($val) || $val === '0') {
                $value['VALUE'][] = $val;
              }
              else {
                $value['VALUE'][] = '-';
              }
            }
            $i++;
            
            // colorcoding of rows
            if ($c & 2 > 0)
              $statrow['ODD'] = 1;
            else
              $statrow['EVEN'] = 1;
           } 
          }

          if (empty($row['LAST_NAME'])) {
            // last name empty - team stats
            $team[$t]['TEAM'][0]['ITEM'] = $value;
          }
          else {
            $player['ITEM'] = $value;
            $team[$t]['PLAYER'][] = $row;
            $team[$t]['PLAYER'][$c]['ITEM'] = $value;
          }


          $tt = FALSE;
          $c++;
        }
        $stats['STATS'] = $team;
   }

   function getStatsFootball(&$stats) {
      global $db;
      global $result_types_short;
      global $result_types;
      global $_SESSION;
      global $auth;

        // build stats
      $sql = 'SELECT 
                  U.FIRST_NAME, U.LAST_NAME, 
                  R.TEAM_ID, T.TEAM_NAME, 
                  R.SCORE,
                  R.PLAYED,
                  R.PT2_SCORED, R.PT2_THROWN,
                  R.PT3_SCORED, R.PT3_THROWN,
                  R.PT1_SCORED, R.PT1_THROWN,
		  R.CONCEDED,
		  R.OWN_GOALS,
                  R.ASSISTS,
                  R.BLOCKS,
                  R.STEALS,
                  R.YELLOW,
                  R.RED,
                  R.FAULS,  
                  R.UNFAULS,
                  R.KOEFF
                FROM 
                  games G, results R
                  LEFT JOIN busers U ON R.USER_ID=U.USER_ID
                  LEFT JOIN teams T ON R.TEAM_ID=T.TEAM_ID
                WHERE 
                  G.GAME_ID='.$this->game_id.'
                  AND R.GAME_ID=G.GAME_ID
                ORDER BY
                  R.TEAM_ID, U.FIRST_NAME, U.LAST_NAME
                ';
        $db->query($sql);
        $t = -1;
        $teamid = 0;
        while ($row = $db->nextRow()) {
          $i = 0;
	  $statrow = array();
          $statrowitem = array();
          
          if ($teamid != $row['TEAM_ID']) {
            $teamid = $row['TEAM_ID'];
            $tt = TRUE;
            $c = 0;
            $t++;
          }
          
          if (empty($row['LAST_NAME'])) {
            // last name empty - team stats
            $prt = 'TEAM';
            $c--;
          }
          else {
            $prt = 'PLAYER';
          }         
          
          $value = array();          
          while (list($key, $val) = each($row)) {
           if(!is_numeric($key))
            {
            if ($i > 3) {
              // add column title
              if ($tt) {
                $title['TITLE'] = $result_types_short[$this->sport_id][$key];
                $team[$t]['ITEM'][] = $title;
                $team[$t]['TEAM_NAME'] = $this->team_names[$row['TEAM_ID']];
              }
              
              if (!empty($val) || $val === '0') {
                $value['VALUE'][] = $val;
              }
              else {
                $value['VALUE'][] = '-';
              }
            }
            $i++;
            
            // colorcoding of rows
            if ($c & 2 > 0)
              $statrow['ODD'] = 1;
            else
              $statrow['EVEN'] = 1;
           } 
          }
          if (empty($row['LAST_NAME'])) {
            // last name empty - team stats
            $team[$t]['TEAM'][0]['ITEM'] = $value;
          }
          else {
            $player['ITEM'] = $value;
            $team[$t]['PLAYER'][] = $row;
            $team[$t]['PLAYER'][$c]['ITEM'] = $value;
          }

          $tt = FALSE;
          $c++;
        }
        $stats['STATS'] = $team;
  }

  function getTimeReports() {
    global $db;
    global $_SESSION;
    global $auth;
    global $report_state;

    $sql="SELECT MPL.*, U.USER_NAME from games_reports MPL
		 left join users U on U.user_id = MPL.user_id
		where  MPL.game_id=".$this->game_id."
			AND finished=0 
  	  ORDER BY DATE_REPORTED ASC";
    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
        
        $report = $row;
        $report['REPORT_STATUS'] = $report_state[$row['REPORT_STATE']];
        if (substr($row['LINK'], 0, 4) != 'http')
  	  $report['LINK'] = "http://".$row['LINK'];

        $reports['REPORTS'][] = $report;
    }
    $reports['GAME_ID'] = $this->game_id;
    return $reports;
  }

}
?>