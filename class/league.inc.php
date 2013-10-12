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

class League {
  var $type;
  var $league_id;
  var $league_info;
 
  function League($type, $league_id) {
     $this->type = $type;
     $this->league_id = $league_id;
     $this->league_info = "";
  }

  function getOwnerRating($owner) {
     global $db;
     
     $sql = "SELECT SUM(T.RATING) AS RATING, SUM(T.VOTES) aS VOTES
		FROM(SELECT SUM(MLV.VOTE) RATING, COUNT(MLV.VOTE) VOTES
		from manager_leagues_votes MLV, manager_leagues ML
		WHERE MLV.league_id=ML.league_ID
			AND ML.USER_ID=".$owner."
		UNION
		SELECT SUM(MLV.VOTE) RATING, COUNT(MLV.VOTE) VOTES
		from wager_leagues_votes MLV, wager_leagues ML
		WHERE MLV.league_id=ML.league_ID
			AND ML.USER_ID=".$owner."
		UNION
		SELECT SUM(MLV.VOTE) RATING, COUNT(MLV.VOTE) VOTES
		from rvs_manager_leagues_votes MLV, rvs_manager_leagues ML
		WHERE MLV.league_id=ML.league_ID
			AND ML.USER_ID=".$owner."
		UNION
		SELECT SUM(MLV.VOTE) RATING, COUNT(MLV.VOTE) VOTES
		from bracket_leagues_votes MLV, bracket_leagues ML
		WHERE MLV.league_id=ML.league_ID
			AND ML.USER_ID=".$owner.") T";
     $db->query($sql);
     if ($row = $db->nextRow()) {
       if ($row['VOTES'] > 0) 
         return $row['RATING'];
       else return 0;
     }
     return 0;
  }

  function getLeagueRating() {
      global $db;

      $sql = "SELECT SUM(MLV.VOTE) RATING, COUNT(MLV.VOTE) VOTES
		from ".$this->type."_leagues_votes MLV, ".$this->type."_leagues ML
		WHERE MLV.league_id=ML.league_ID
			AND ML.league_ID = ".$this->league_id;
      $db->query($sql);     
      if ($row = $db->nextRow()) {
        if ($row['VOTES'] > 0) 
          return $row['RATING'];
        else return 0;
      }
      return 0;
  }

  function getLeagueInviteCode() {
     global $db;
     $sql = "SELECT INVITE_CODE
		from ".$this->type."_leagues ML
		WHERE ML.INVITE_TYPe=1 AND ML.league_ID = ".$this->league_id;

     $db->query($sql);     
     if ($row = $db->nextRow()) {
       return $row['INVITE_CODE'];
     } else return "";
  }

  function getLeagueInfo() {
     global $db;
     $sql = "SELECT *
		from ".$this->type."_leagues ML
		WHERE ML.league_ID = ".$this->league_id;
     $db->query($sql);     
     if ($row = $db->nextRow()) {
       $this->league_info = $row;
     }
  }

  function cancelAllInvites() {
     global $db;
     $db->delete('manager_leagues_members', 'STATUS=3 AND LEAGUE_ID='.$this->league_id);    
  }
}

?>