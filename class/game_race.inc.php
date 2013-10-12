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

class GameRace {
   var $game_id;
   var $sport_id;
   var $team_names;
  
   function GameRace($game_id) {
  //    global $data;

      $this->game_id = $game_id;
   }

   function getGameStats() {
      global $db;
      global $smarty;
      global $result_types_short;
      global $result_types;
      global $_SESSION;
      global $auth;
    
        // generate team statistics
      
      $sql = "SELECT G.GAME_ID, G.PUBLISH, G.TITLE,
		 DATE_ADD(G.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE, 
              	 SD.SEASON_TITLE, S.SPORT_ID
               FROM seasons S
                   left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id']."
   		   , tournaments TR, games_races G
               WHERE G.GAME_ID = ".$this->game_id."
                     AND G.SEASON_ID=S.SEASON_ID 
                     AND S.TOURNAMENT_ID=TR.TOURNAMENT_ID";
      $db->query($sql);
      $row = $db->nextRow();
      $this->sport_id   = $row['SPORT_ID'];
    

        $stats['SEASON_TITLE'] = $row['SEASON_TITLE'];
        $stats['TITLE'] = $row['TITLE'];
        $stats['DATE']         = $row['START_DATE']." ".$auth->getUserTimezoneName();
        
      if ($this->sport_id==3)
        $this->getStatsFormula1($stats);

      $keys = array_keys($result_types_short[$this->sport_id]);
      for ($i = 0; $i < count($result_types_short[$this->sport_id]); $i++) {
        $legend['KEY'] = $result_types_short[$this->sport_id][$keys[$i]];
        $legend['LEGEND'] = $result_types[$this->sport_id][$keys[$i]];
        $legends[] = $legend;
      }
        
      // add data
      $smarty->assign("stats", $stats);
      $smarty->assign("legends", $legends);

//print_r($stats);
      $start = getmicrotime();
      $output = $smarty->fetch('smarty_tpl/gameracestats'.$this->sport_id.'.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/gameracestats'.$this->sport_id.'.smarty<br>'.($stop-$start);
      return $output;

   }

   function getStatsFormula1(&$stats) {
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
                  R.PLACE,
		  R.NOTE
                FROM 
                  games_races G, results_races R
                  LEFT JOIN busers U ON R.USER_ID=U.USER_ID
                  LEFT JOIN teams T ON R.TEAM_ID=T.TEAM_ID
                WHERE 
                  G.GAME_ID='.$this->game_id.'
                  AND R.GAME_ID=G.GAME_ID
                ORDER BY
                  R.PLACE, U.FIRST_NAME, U.LAST_NAME
                ';

        $db->query($sql);
        $t = -1;
        $teamid = 0;
        $c = 0;
        $tt = TRUE;
        while ($row = $db->nextRow()) {
          $i = 0;
          $statrow = array();
          $statrowitem = array();
                    
          $value = array();                    
          while (list($key, $val) = each($row)) {
           if(!is_numeric($key))
            {
            if ($i > 3) {
              // add column title
              if ($tt) {
                $title['TITLE'] = $result_types_short[$this->sport_id][$key];
                $team[$t]['ITEM'][] = $title;
              }

              if (!empty($val) || $val === '0') {
                $value['VALUE'][] = $val;
              }
              else {
                $value['VALUE'][] = $val;
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

          $player['ITEM'] = $value;
          $team[$t]['PLAYER'][] = $row;
          $team[$t]['PLAYER'][$c]['ITEM'] = $value;

          $tt = FALSE;
          $c++;
        }
        $stats['STATS'] = $team;
  }
}
?>