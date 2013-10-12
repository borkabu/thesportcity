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

class ManagerRating {
  var $season_id;
  var $tournament_id;
  var $sport_id;
 
  function ManagerRating($season_id) {
    $this->season_id = $season_id;
    $this->init();
  }

  function init() {
    global $db;
    
    $sql = "SELECT S.TOURNAMENT_ID, S.SPORT_ID FROM
		manager_subseasons MS, seasons S
		WHERE MS.SEASON_ID=S.SEASON_ID
		      AND MS.MSEASON_ID=".$this->season_id;
    $db->query($sql);
    if ($row = $db->nextRow()) {
        $this->sport_id = $row['SPORT_ID'];
        $this->tournament_id = $row['TOURNAMENT_ID'];
    }
  }

  function updateSportRating() {
    global $db;
	
    $db->delete ( "manager_ratings", "SPORT_ID=" . $this->sport_id );
    $sql= "SELECT SUM(RATING_POINTS+BONUS)/SUM(TOURS) + COUNT( DISTINCT SEASON_ID ) RATING, USER_ID, COUNT( DISTINCT SEASON_ID ) SEASONS
		FROM (SELECT SUM(MUT.RATING_POINTS) RATING_POINTS, MUT.BONUS/100 as BONUS, USER_ID, MUT.SEASON_ID, COUNT(DISTINCT MUT.TOUR_END_DATE) TOURS
			FROM manager_rating_points MUT
			WHERE DATE_ADD(MUT.TOUR_END_DATE, INTERVAL 1 YEAR) > NOW()
		              AND MUT.SPORT_ID=".$this->sport_id."
			GROUP BY MUT.USER_ID, MUT.SEASON_ID
			HAVING TOURS >=3
		     ) S
		GROUP BY USER_ID
		HAVING SEASONS>1 
		ORDER BY RATING DESC";
    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
        $users[$c] = $row;
        $c++;
    }

    for ($i = 0; $i < $c; $i ++) {
	unset($sdata);	
	$sdata['USER_ID']=$users[$i]['USER_ID'];
	$sdata['POINTS']=$users[$i]['RATING'];
	$sdata['SPORT_ID'] = $this->sport_id;
	$sdata['TOURNAMENT_ID'] = 0;
	$sdata['PLACE'] = $i+1;
	$sdata['SEASONS']=$users[$i]['SEASONS'];
	$db->insert ( 'manager_ratings', $sdata);
    }   
  }

  function updateTournamentRating() {
    global $db;
	
    $db->delete ( "manager_ratings", "TOURNAMENT_ID=" . $this->tournament_id );
    $sql= "SELECT SUM(RATING_POINTS+BONUS)/SUM(TOURS) + COUNT( DISTINCT SEASON_ID ) RATING, USER_ID, COUNT( DISTINCT SEASON_ID ) SEASONS
		FROM (SELECT SUM(MUT.RATING_POINTS) RATING_POINTS, MUT.BONUS/100 as BONUS, USER_ID, MUT.SEASON_ID, COUNT(DISTINCT MUT.TOUR_END_DATE) TOURS
			FROM manager_rating_points MUT
			WHERE DATE_ADD(MUT.TOUR_END_DATE, INTERVAL 1 YEAR) > NOW()
			      AND MUT.TOURNAMENT_ID=".$this->tournament_id."
			GROUP BY MUT.USER_ID, MUT.SEASON_ID
			HAVING TOURS >=3
		     ) S
		GROUP BY USER_ID
		ORDER BY RATING DESC";

    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
        $users[$c] = $row;
        $c++;
    }

    for ($i = 0; $i < $c; $i ++) {
	unset($sdata);	
	$sdata['USER_ID']=$users[$i]['USER_ID'];
	$sdata['POINTS']=$users[$i]['RATING'];
	$sdata['SPORT_ID'] = 0;
	$sdata['TOURNAMENT_ID'] = $this->tournament_id;
	$sdata['PLACE'] = $i+1;
	$sdata['SEASONS']=$users[$i]['SEASONS'];
	$db->insert ( 'manager_ratings', $sdata);
    }   
  }

  function updateTotalRating() {
    global $db;
	
    $db->delete ( "manager_ratings", "TOURNAMENT_ID=0 AND SPORT_ID=0");
    $sql= "SELECT SUM(RATING_POINTS+BONUS)/SUM(TOURS) + COUNT( DISTINCT SEASON_ID ) RATING, USER_ID, COUNT( DISTINCT SEASON_ID ) SEASONS
		FROM (SELECT SUM(MUT.RATING_POINTS) RATING_POINTS, MUT.BONUS/100 as BONUS, USER_ID, MUT.SEASON_ID, COUNT(DISTINCT MUT.TOUR_END_DATE) TOURS
			FROM manager_rating_points MUT
			WHERE DATE_ADD(MUT.TOUR_END_DATE, INTERVAL 1 YEAR) > NOW()
			GROUP BY MUT.USER_ID, MUT.SEASON_ID
			HAVING TOURS >=3
		     ) S
		GROUP BY USER_ID
		HAVING SEASONS>1 
		ORDER BY RATING DESC";
    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
        $users[$c] = $row;
        $c++;
    }

    for ($i = 0; $i < $c; $i ++) {
	unset($sdata);	
	$sdata['USER_ID']=$users[$i]['USER_ID'];
	$sdata['POINTS']=$users[$i]['RATING'];
	$sdata['SEASONS']=$users[$i]['SEASONS'];
	$sdata['SPORT_ID'] = 0;
	$sdata['TOURNAMENT_ID'] = 0;
	$sdata['PLACE'] = $i+1;
	$db->insert ( 'manager_ratings', $sdata);
    }   
  }

}

?>