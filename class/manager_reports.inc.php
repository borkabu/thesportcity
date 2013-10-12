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

class ManagerReports {
  var $reports;

   function getRows() {
      return $this->reports;
   }
   function getReportList ($season_id = -1, $page=1,$perpage=PAGE_SIZE) {
      global $db;
      global $auth;
      global $_SESSION;
      global $player_state;
      global $smarty;

      $data = '';      
      if ($season_id != -1) {
        $sql = "SELECT count(MPL.REPORT_ID) REPORTS
                FROM manager_market MM, manager_player_reports MPL
                WHERE MM.SEASON_ID=".$season_id." 
			AND MPL.FINISHED=0 
			and MPL.REPORT_STATE=1
			AND MPL.PLAYER_ID=MM.USER_ID";
      } else {
        $sql = "SELECT count(MPL.REPORT_ID) REPORTS
                FROM mmanager_player_reports MPL
                WHERE MPL.FINISHED=0 
			and MPL.REPORT_STATE=1";
      }

      $db->query($sql);
      $row = $db->nextRow();
         
      $this->reports = $row['REPORTS'];

      $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;      
      if ($season_id != -1) {
        $sql = "SELECT MPL.*, MM.*, U.USER_NAME
                FROM manager_market MM, manager_player_reports MPL
			left join users U on U.user_id = MPL.user_id
                WHERE MM.SEASON_ID=".$season_id." 
			AND MPL.FINISHED=0 
			and MPL.REPORT_STATE=1
			AND MPL.PLAYER_ID=MM.USER_ID
            ORDER BY MPL.DATE_REPORTED DESC ".$limitclause;
      } else {
        $sql = "SELECT MPL.*, U.USER_NAME, B.FIRST_NAME, B.LAST_NAME
                FROM busers B, manager_player_reports MPL
			left join users U on U.user_id = MPL.user_id
                WHERE  MPL.FINISHED=0 
			and MPL.REPORT_STATE=1
			AND MPL.PLAYER_ID=B.USER_ID
            ORDER BY MPL.DATE_REPORTED DESC ".$limitclause;
      }
      $db->query($sql);
    

      $reports = array();
      while ($row = $db->nextRow()) {
        $report = $row;

        $report['STATE'] = $player_state[$row['STATUS']];
        $reports[] = $report;
      }
      return $reports;
  }

}
?>