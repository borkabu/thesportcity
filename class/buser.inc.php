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

class Buser {
   var $user_id;
   var $sport_id;
//   var $data;
   var $season_id;
   var $first_name;
   var $last_name;
   
   function Buser($user_id) {
//      global $data;

      $this->user_id = $user_id; 
///      $this->data = $data;
      $this->getSportId();
   }

   function getSportId() {
      global $db;
      $db->select('busers', 'SPORT_ID, FIRST_NAME, LAST_NAME', 'USER_ID='.$this->user_id);
      if ($row = $db->nextRow()) {
        $this->sport_id = $row['SPORT_ID'];
        $this->first_name = $row['FIRST_NAME'];
        $this->last_name = $row['LAST_NAME'];   
      }
   }

   function getBuserStatsSeasons(&$stats, $except_id = '') {
      global $db;

      if ($this->sport_id == 1 || $this->sport_id == 2) {
        $sql = "SELECT DISTINCT SD.SEASON_TITLE, S.SEASON_ID
                FROM 
                  seasons S
		   left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id'].",
		   games G, results R 
                WHERE 
                  R.USER_ID=".$this->user_id."
                  AND R.GAME_ID=G.GAME_ID
                  AND G.SEASON_ID not IN (".$except_id.")
		  AND G.SEASON_ID = S.SEASON_ID
                ORDER BY
                  SD.SEASON_TITLE";
      } else {
        $sql = "SELECT DISTINCT SD.SEASON_TITLE, S.SEASON_ID
                FROM 
                  seasons S
		   left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id'].",
		   games_races G, results_races R 
                WHERE 
                  R.USER_ID=".$this->user_id."
                  AND R.GAME_ID=G.GAME_ID
                  AND G.SEASON_ID not IN (".$except_id.")
		  AND G.SEASON_ID = S.SEASON_ID
                ORDER BY
                  SD.SEASON_TITLE";
      }

        $db->query($sql);
        $c = 0;
        while ($row = $db->nextRow()) {                
          $row['PLAYER_ID'] = $this->user_id;
          $stats['SEASONS'][] = $row;
        }
   }
 
   function getBuserSeasonStats($season_id) {
      global $db;
      global $smarty;
      global $result_types_short;
      global $result_types;
      global $_SESSION;
      global $auth;
      $this->season_id = $season_id;
       
      // generate team statistics

      $stats['FIRST_NAME'] = $this->first_name;
      $stats['LAST_NAME'] = $this->last_name;   

      if ($this->sport_id==1)
	$this->getStatsBasketball($stats);
      else if ($this->sport_id==2)
	 $this->getStatsFootball($stats);
      else if ($this->sport_id==3)
	  $this->getStatsF1($stats);

      $this->getBuserStatsSeasons($stats, $season_id);
         
      $keys = array_keys($result_types_short[$this->sport_id]);
      for ($i = 0; $i < count($result_types_short[$this->sport_id]); $i++) {
	  $legend['KEY'] = $result_types_short[$this->sport_id][$keys[$i]];
	  $legend['LEGEND'] = $result_types[$this->sport_id][$keys[$i]];
          $legends[] = $legend;
      }
        
        // add data
      $smarty->assign("stats", $stats);
      $smarty->assign("legends", $legends);


//print_r($this->data);
     
        // add data

      $start = getmicrotime();
      $output = $smarty->fetch('smarty_tpl/playerseasonstats'.$this->sport_id.'.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/playerseasonstats'.$this->sport_id.'.smarty<br>'.($stop-$start);
      return $output;

   }

   function getStatsBasketball(&$stats) {
      global $db;
      global $_SESSION;
      global $auth;
      global $result_types_short;
      global $result_types;


        // build stats
        $sql = "SELECT SD.SEASON_TITLE, S.SEASON_ID, SUBSTRING(G.START_DATE, 1, 10) START_DATE, 
                  B.FIRST_NAME, B.LAST_NAME,
                  T1.TEAM_NAME AS TEAM_NAME1, 
                  T2.TEAM_NAME AS TEAM_NAME2, 
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
                  seasons S
		   left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id'].",
		  teams T1, teams T2,  games G, busers B, results R 
                  LEFT JOIN busers U ON R.USER_ID=U.USER_ID
                  LEFT JOIN teams T3 ON R.TEAM_ID=T3.TEAM_ID
                WHERE 
                  R.USER_ID=".$this->user_id."
                  AND B.USER_ID=R.USER_ID
                  AND R.GAME_ID=G.GAME_ID
                  AND G.SEASON_ID IN (".$this->season_id.")
	    AND G.TEAM_ID1=T1.TEAM_ID
                  AND G.TEAM_ID2=T2.TEAM_ID
	    AND S.SEASON_ID=G.SEASON_ID 
                ORDER BY
                  G.START_DATE";
//echo $sql;
        $db->query($sql);
        $t = 0;
        $tt=TRUE;
        $c = 0;
        while ($row = $db->nextRow()) {
          $i = 0;
                 
          $season[$t]['SEASON_TITLE'] = $row['SEASON_TITLE'];
      
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
            if ($i > 12) {
              // add column title
              if ($tt) {
                $title['TITLE'] = $result_types_short[$this->sport_id][$key];
                $season[$t]['ITEM'][] = $title;
              }

              if (!empty($val) || $val === '0') {
                $value['VALUE'][] = $val;
              }
              else {
                $value['VALUE'][] = '-';
              }              
            }
            $i++;      
           } 
          }
          $season[$t]['GAME'][] = $row;
          $season[$t]['GAME'][$c]['ITEM'] = $value;

          $tt = FALSE;
          $c++;
        }
    
        /// totals
      
        $sql = 'SELECT G.SEASON_ID,
                  SUM(R.PT2_SCORED) PT2_S, SUM(R.PT2_THROWN) PT2_T,
                  SUM(R.PT3_SCORED) PT3_S, SUM(R.PT3_THROWN) PT3_T,
                  SUM(R.PT1_SCORED) PT1_S, SUM(R.PT1_THROWN) PT1_T,
                  COUNT(R.RESULT_ID) GAMES, 
                  ROUND(SUM(R.SCORE)) SCORE,
                  ROUND(SUM(R.PLAYED)) PLAYED,
                  (0) PT2,
                  (0) PT2_PERCENT,
                  (0) PT3,
                  (0) PT3_PERCENT,
                  (0) PT1,
                  (0) PT1_PERCENT,
                  ROUND(SUM(R.REBOUNDS)) REBOUNDS,
                  ROUND(SUM(R.ASSISTS)) ASISTS,
                  ROUND(SUM(R.BLOCKS)) BLOCKS,
                  ROUND(SUM(R.UNBLOCKS)) UNBLOCKS,
                  ROUND(SUM(R.STEALS)) STEALS,
                  ROUND(SUM(R.MISTAKES)) MISTAKES,
                  ROUND(SUM(R.FAULS)) FAULS,  
                  ROUND(SUM(R.UNFAULS)) UNFAULS,
                  ROUND(SUM(R.KOEFF)) KOEFF
                FROM 
                  games G, results R 
                  LEFT JOIN busers U ON R.USER_ID=U.USER_ID
                WHERE 
                  R.USER_ID='.$this->user_id.'
                  AND R.GAME_ID=G.GAME_ID
                  AND G.SEASON_ID IN ('.$this->season_id.')
                GROUP BY
                  U.FIRST_NAME, U.LAST_NAME, G.SEASON_ID
                ORDER BY
                  U.FIRST_NAME, U.LAST_NAME
                ';
      
        $db->query($sql);
        while ($row = $db->nextRow()) {
          $i = 0;
                 
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
           if(!is_numeric($key))
            {
            if ($i > 7) {       
              if (!empty($val) || $val === '0') {
                $value['VALUE'][] = $val;
              }
              else {
                $value['VALUE'][] = '-';
              }              
            }
            $i++;      
           } 
          }

          $season[$t]['TOTAL'][] = $row;
          $season[$t]['TOTAL'][0]['ITEM'] = $value;
        }

        $stats['STATS'] = $season;
   }

   function getStatsFootball(&$stats) {
      global $db;
      global $_SESSION;
      global $auth;
      global $result_types_short;
      global $result_types;


        // build stats
        $sql = "SELECT SD.SEASON_TITLE, S.SEASON_ID, SUBSTRING(G.START_DATE, 1, 10) START_DATE, 
                  B.FIRST_NAME, B.LAST_NAME,
                  T1.TEAM_NAME AS TEAM_NAME1, 
                  T2.TEAM_NAME AS TEAM_NAME2, 
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
                  seasons S
		   left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id'].",
		  teams T1, teams T2,  games G, busers B, results R 
                  LEFT JOIN busers U ON R.USER_ID=U.USER_ID
                  LEFT JOIN teams T3 ON R.TEAM_ID=T3.TEAM_ID
                WHERE 
                  R.USER_ID=".$this->user_id."
                  AND B.USER_ID=R.USER_ID
                  AND R.GAME_ID=G.GAME_ID
                  AND G.SEASON_ID IN (".$this->season_id.")
	    AND G.TEAM_ID1=T1.TEAM_ID
                  AND G.TEAM_ID2=T2.TEAM_ID
	    AND S.SEASON_ID=G.SEASON_ID 
                ORDER BY
                  G.START_DATE";
//echo $sql;
        $db->query($sql);
        $t = 0;
        $tt=TRUE;
        $c = 0;
        while ($row = $db->nextRow()) {
          $i = 0;
                 
          $season[$t]['SEASON_TITLE'] = $row['SEASON_TITLE'];
                        
          $value = array();
          while (list($key, $val) = each($row)) {
           if(!is_numeric($key))
            {
            if ($i > 6) {
              // add column title
              if ($tt) {
                $title['TITLE'] = $result_types_short[$this->sport_id][$key];
                $season[$t]['ITEM'][] = $title;
              }

              if (!empty($val) || $val === '0') {
                $value['VALUE'][] = $val;
              }
              else {
                $value['VALUE'][] = '-';
              }              
            }
            $i++;      
           } 
          }

          $season[$t]['GAME'][] = $row;
          $season[$t]['GAME'][$c]['ITEM'] = $value;

	  $tt = FALSE;
          $c++;
        }
        
      
        /// totals
      
        $sql = 'SELECT G.SEASON_ID,
                  ROUND(SUM(R.SCORE)) SCORE,
                  ROUND(SUM(R.PLAYED)) PLAYED,
                  SUM(R.PT2_SCORED) PT2_SCORED, SUM(R.PT2_THROWN) PT2_THROWN,
                  SUM(R.PT3_SCORED) PT3_SCORED, SUM(R.PT3_THROWN) PT3_THROWN,
                  SUM(R.PT1_SCORED) PT1_SCORED, SUM(R.PT1_THROWN) PT1_THROWN,
                  ROUND(SUM(R.CONCEDED)) CONCEDED,
                  ROUND(SUM(R.OWN_GOALS)) OWN_GOALS,
                  ROUND(SUM(R.ASSISTS)) ASISTS,
                  ROUND(SUM(R.BLOCKS)) BLOCKS,
                  ROUND(SUM(R.STEALS)) STEALS,
                  ROUND(SUM(R.YELLOW)) YELLOW,
                  ROUND(SUM(R.RED)) RED,
                  ROUND(SUM(R.FAULS)) FAULS,  
                  ROUND(SUM(R.UNFAULS)) UNFAULS,
                  ROUND(SUM(R.KOEFF)) KOEFF
                FROM 
                  games G, results R 
                  LEFT JOIN busers U ON R.USER_ID=U.USER_ID
                WHERE 
                  R.USER_ID='.$this->user_id.'
                  AND R.GAME_ID=G.GAME_ID
                  AND G.SEASON_ID IN ('.$this->season_id.')
                GROUP BY
                  U.FIRST_NAME, U.LAST_NAME, G.SEASON_ID
                ORDER BY
                  U.FIRST_NAME, U.LAST_NAME
                ';
      
        $db->query($sql);
        while ($row = $db->nextRow()) {
          $i = 0;
                           
          $value = array();        
          while (list($key, $val) = each($row)) {
           if(!is_numeric($key))
            {
            if ($i > 0) {       
              if (!empty($val) || $val === '0') {
                $value['VALUE'][] = $val;
              }
              else {
                $value['VALUE'][] = '-';
              }              
            }
            $i++;      
           } 
          }
          $season[$t]['TOTAL'][] = $row;
          $season[$t]['TOTAL'][0]['ITEM'] = $value;

          $c++;
        }
        $stats['STATS'] = $season;
     }


   function getStatsF1(&$stats) {
      global $db;
      global $_SESSION;
      global $auth;
      global $result_types_short;
      global $result_types;


        // build stats
        $sql = "SELECT SD.SEASON_TITLE, S.SEASON_ID, SUBSTRING(G.START_DATE, 1, 10) START_DATE, 
                  B.FIRST_NAME, B.LAST_NAME, G.TITLE, 
                  T.TEAM_NAME, 
                  R.SCORE,
                  R.PLACE,
                  R.NOTE,
                  R.KOEFF
                FROM 
                  seasons S
		   left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id'].",
		  teams T,  games_races G, busers B, results_races R 
                  LEFT JOIN busers U ON R.USER_ID=U.USER_ID
                WHERE 
                  R.USER_ID=".$this->user_id."
                  AND B.USER_ID=R.USER_ID
                  AND R.GAME_ID=G.GAME_ID
                  AND G.SEASON_ID IN (".$this->season_id.")
	          AND S.SEASON_ID=G.SEASON_ID 
                  AND R.TEAM_ID=T.TEAM_ID
                ORDER BY
                  G.START_DATE";
//echo $sql;
        $db->query($sql);
        $t = 0;
        $tt=TRUE;
        $c = 0;
        while ($row = $db->nextRow()) {
          $i = 0;
                 
          $season[$t]['SEASON_TITLE'] = $row['SEASON_TITLE'];
                        
          $value = array();
          while (list($key, $val) = each($row)) {
           if(!is_numeric($key))
            {
            if ($i > 6) {
              // add column title
              if ($tt) {
                $title['TITLE'] = $result_types_short[$this->sport_id][$key];
                $season[$t]['ITEM'][] = $title;
              }

              if (!empty($val) || $val === '0') {
                $value['VALUE'][] = $val;
              }
              else {
                $value['VALUE'][] = '-';
              }              
            }
            $i++;      
           } 
          }

          $season[$t]['GAME'][] = $row;
          $season[$t]['GAME'][$c]['ITEM'] = $value;

	  $tt = FALSE;
          $c++;
        }
             
        /// totals
      
        $sql = 'SELECT G.SEASON_ID,
                  ROUND(SUM(R.SCORE)) SCORE,
                  \'-\' as PLACE,
                  \'-\' as NOTE,
                  ROUND(SUM(R.KOEFF), 2) KOEFF
                FROM 
                  games_races G, results_races R 
                  LEFT JOIN busers U ON R.USER_ID=U.USER_ID
                WHERE 
                  R.USER_ID='.$this->user_id.'
                  AND R.GAME_ID=G.GAME_ID
                  AND G.SEASON_ID IN ('.$this->season_id.')
                GROUP BY
                  U.FIRST_NAME, U.LAST_NAME, G.SEASON_ID
                ORDER BY
                  U.FIRST_NAME, U.LAST_NAME
                ';
//echo $sql;      
        $db->query($sql);
        while ($row = $db->nextRow()) {
          $i = 0;
                           
          $value = array();        
          while (list($key, $val) = each($row)) {
           if(!is_numeric($key))
            {
            if ($i > 0) {       
              if (!empty($val) || $val === '0') {
                $value['VALUE'][] = $val;
              }
              else {
                $value['VALUE'][] = '-';
              }              
            }
            $i++;      
           } 
          }
          $season[$t]['TOTAL'][] = $row;
          $season[$t]['TOTAL'][0]['ITEM'] = $value;

          $c++;
        }
        $stats['STATS'] = $season;
     }

}
?>