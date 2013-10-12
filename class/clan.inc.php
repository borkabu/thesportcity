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

class Clan {
   var $clan_id;
   var $clan_data;
   var $clan_members;
  
   function Clan($clan_id = '') {
     $this->clan_id = $clan_id;
     $this->clan_data = '';
     $this->clan_members = '';
   }

   function getClanMembers() {
     global $db;

     $this->clan_members = array();	
     $sql="SELECT UA.USER_ID, U.USER_NAME, UA.DATE_JOINED, UA.CLAN_ID, UA.LEVEL, UA.STATUS
             FROM clan_members UA, users U
             WHERE UA.CLAN_ID = ".$this->clan_id."
		   and UA.USER_ID=U.USER_ID
            ORDER BY U.USER_NAME ASC";
     $db->query($sql);
//echo $sql;
     $members = '';
     while ($row = $db->nextRow()) {
       if ($row['STATUS'] == 1) {
         $members['OWNER'] = $row;
         $this->clan_members[$row['USER_ID']] = $row;
       }
       else if ($row['STATUS'] == 2) {
         $current_member = $row;
         if ($row['LEVEL'] == 2) {
           $current_member['CAN_REMOVE'] = $row; 
         }
         $members['CURRENT_MEMBERS'][$row['USER_ID']] = $current_member;
         $this->clan_members[$row['USER_ID']] = $row;
       }
       else if ($row['STATUS'] == 3) {
         $members['INVITED_MEMBERS'][] = $row;
       }
       else if ($row['STATUS'] == 4) {
         $members['FORMER_MEMBERS'][] = $row;
       }
       else if ($row['STATUS'] == 5) {
         $members['DECLINE_MEMBERS'][] = $row;
       }
     }

       $sql= "SELECT MLM.USER_ID, MLM.STATUS,
		sum(if (MS.END_DATE is NULL, 0, 1) * if (CTM.USER_ID is NULL, 0, 1) ) TEAMS
            FROM clan_members MLM
		   LEFT JOIN clan_teams CT ON MLM.CLAN_ID=CT.CLAN_ID
		   LEFT JOIN manager_seasons MS ON CT.SEASON_ID=MS.SEASON_ID AND MS.END_DATE > NOW()
		   LEFT JOIN clan_team_members CTM ON MLM.user_id=CTM.user_id and CTM.STATUS=1 and CT.TEAM_ID=CTM.TEAM_ID		
           WHERE MLM.CLAN_ID=".$this->clan_id."
                   and MLM.STATUS IN (1,2)
           GROUP BY MLM.USER_ID, MLM.STATUS"; 
     $db->query($sql);
     while ($row = $db->nextRow()) {
       $this->clan_members[$row['USER_ID']]['TEAMS'] = $row['TEAMS'];
       if ($row['STATUS'] == 2) {
         $members['CURRENT_MEMBERS'][$row['USER_ID']]['TEAMS'] = $row['TEAMS'];
         if ($row['TEAMS'] == 0)
           $members['CURRENT_MEMBERS'][$row['USER_ID']]['CAN_REMOVE'] = 1;
       }
       else if ($row['STATUS'] == 1)
         $members['OWNER']['TEAMS'] = $row['TEAMS'];
     }

     return $members;
   }

   function getClanData() {
     global $db;
     $sql = "SELECT ML.*, U.USER_NAME as OWNER
		from clans ML, users U
		WHERE ML.USER_ID=U.USER_ID
			AND ML.clan_ID = ".$this->clan_id;
     $db->query($sql);     
     if ($row = $db->nextRow()) {
       $this->clan_data = $row;
     }
     return $this->clan_data;
   }


   function getClanMembersData($moderate = false) {
     global $smarty;
     global $db;
     global $_GET;

     $sql="SELECT UA.USER_ID, U.USER_NAME, UA.DATE_JOINED, UA.CLAN_ID, UA.LEVEL
             FROM clan_members UA, users U
             WHERE UA.CLAN_ID = ".$this->clan_id."
		   and UA.USER_ID=U.USER_ID
            ORDER BY U.USER_NAME ASC";
     $db->query($sql);
     $group_members = array();
     while ($row = $db->nextRow()) {
       $group_member = $row;
       if ($row['LEVEL'] == 2)
         $group_member['REMOVE'] = 1;
       $group_members[] = $group_member;
     }

     if (count($group_members) > 0)
       $smarty->assign("group_members", $group_members);

     $start = getmicrotime();
     if ($moderate)
       $template = "bar_group_members_moderate";
     else  
       $template = "bar_group_members";

     $output = $smarty->fetch('smarty_tpl/'.$template.'.smarty');
     $stop = getmicrotime();
     if (isset($_GET['debugphp']))
       echo 'smarty_tpl/'.$template.'.smarty'.($stop-$start);    
     return $output;
   }

   function addNewMember($user_id) {
     global $db;

     $sdata['CLAN_ID'] = $this->clan_id;
     $sdata['USER_ID'] = $user_id;
     $sdata['LEVEL'] = 2;
     $sdata['DATE_JOINED'] = 'NOW()';
     $db->insert('clan_members', $sdata);
     unset($sdata);
     $sdata['MEMBERS'] = 'MEMBERS+1';
     $db->update('clans', $sdata, "CLAN_ID=".$this->clan_id);
   }

   function removeMember($user_id) {
     global $db;

     $sdata['STATUS'] = 4;
     $sdata['DATE_LEFT'] = 'NOW()';
     $db->update('clan_members', $sdata, 'CLAN_ID='.$this->clan_id.' AND USER_ID='.$user_id.' AND STATUS=2');
     unset($sdata);
     $sdata['MEMBERS'] = 'MEMBERS-1';
     $db->update('clans', $sdata, "CLAN_ID=".$this->clan_id);

     $sql="SELECT F.GROUP_ID
             FROM clans ML, clan_members MLM, forum F
             WHERE ML.CLAN_ID=MLM.CLAN_ID
	       AND ML.CLAN_ID=".$this->clan_id."
               AND MLM.USER_ID=".$user_id."
               AND MLM.STATUS=2
	       AND ML.FORUM_ID=F.FORUM_ID";
     $db->query($sql);    
     if ($row = $db->nextRow()) {
       $db->delete('forum_group_members', 'USER_ID='.$user_id.' AND GROUP_ID='.$row['GROUP_ID']);  
     }

     // log it
     $clan_log = new ClanLog();
     $clan_log->logEvent ($this->clan_id, 3, 0, $user_id, '');

     $clan_user_log = new ClanUserLog();
     $clan_user_log->logEvent ($user_id, 3, $this->clan_id);

     $credits = new Credits();
     $credits->updateClanCredits($this->clan_id, -1);

   }

   function awardMember($user_id, $amount) {
     global $db;

     if ($this->clan_data['CLAN_FUND'] >= $amount && $amount > 0) {
       $credits = new Credits();
       $credits->updateClanCredits($_POST['clan_id'], -1*$amount);
//       $this->clan_data['CLAN_FUND'] -= $amount;
  
       $clan_log = new ClanLog();
       $clan_log->logEvent ($this->clan_id, 8, $amount, $user_id, '');

       $credit_log = new CreditsLog();
       $credits->updateCredits($user_id, round($amount*95/100, 2));
       $credit_log->logEvent ($user_id, 27, round($amount*95/100, 2));
     }
   }


   function isGroupModerator($user_id) {
     global $db;

     $sql="SELECT UA.USER_ID, UA.LEVEL
             FROM forum_groups_members UA
             WHERE UA.GROUP_ID = ".$this->group_id."
		   and UA.USER_ID=".$user_id."
		   and UA.LEVEL in (1, 3)";
     $db->query($sql);
     if ($row = $db->nextRow()) {
       return true;
     }

     return false;
   }

   function getClanLeader() {
     global $db;

     $sql="SELECT UA.USER_ID, UA.LEVEL
             FROM clan_members UA
             WHERE UA.CLAN_ID = ".$this->clan_id."
		   and UA.LEVEL=3";
     $db->query($sql);
     if ($row = $db->nextRow()) {
       return $row['USER_ID'];
     }
     return 0;
   }

   function hasMember($user_id) {
     global $db;

     $sql="SELECT UA.USER_ID
             FROM clan_members UA
             WHERE UA.CLAN_ID = ".$this->clan_id."
		   and UA.status in (1,2)
		   and UA.USER_ID=".$user_id;
     $db->query($sql);
     if ($row = $db->nextRow()) {
       return true;
     }

     return false;
   }

   function hasInvite($user_id) {
     global $db;

     $sql="SELECT UA.USER_ID
             FROM clan_members UA
             WHERE UA.CLAN_ID = ".$this->clan_id."
		   and UA.status in (3)
		   and UA.USER_ID=".$user_id;
     $db->query($sql);
     if ($row = $db->nextRow()) {
       return true;
     }

     return false;
   }

   function getClanTeams($current=true) {
     global $db;

     $this->getClanMembers();
     $where = " AND MS.END_DATE < NOW()";
     if ($current)
       $where = " AND MS.END_DATE > NOW()";

     $sql="SELECT MS.*, MSD.SEASON_TITLE, UA.TEAM_ID, UA.STATUS, 1 as EVENT_TYPE, MCTS.PLACE
             FROM manager_seasons MS left join clan_teams UA ON
                     UA.SEASON_ID = MS.SEASON_ID
  		     and UA.EVENT_TYPE = 1 
		     AND UA.CLAN_ID = ".$this->clan_id."
		   LEFT JOIN manager_seasons_details MSD ON MS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."	   
		   LEFT JOIN manager_clan_teams_standings MCTS ON MS.SEASON_ID = MCTS.MSEASON_ID AND MCTS.TEAM_ID=UA.TEAM_ID
             WHERE MS.PUBLISH='Y' 
		AND MS.ALLOW_CLAN_TEAMS = 'Y'
		". $where;
//echo $sql;
     $db->query($sql);
     $teams = array();
     $team_ids = "";
     $pre = "";
     $seasons = array();
     while ($row = $db->nextRow()) {
       $row['CLAN_ID'] = $this->clan_id;
       $teams[$row['SEASON_ID']] = $row;
       if ($row['TEAM_ID'] != "") {
         $team_ids .= $pre.$row['TEAM_ID'];
         $pre =",";
       }
       $teams[$row['SEASON_ID']]['MEMBERS'] = $this->clan_members;
       $teams[$row['SEASON_ID']]['FREE_CHANGE'] = 1;
       $seasons[] = $row['SEASON_ID'];
     }

     foreach ($seasons as $season) {
       $manager = new Manager($season);
       $tour_id = $manager->getLastTour();   
       $manager->getNextTour();   
       $this->getClanMembersStandings($season, $teams[$season]['MEMBERS'], $tour_id);

       if ($manager->manager_trade_allow)
         $teams[$season]['ALLOW_CHANGE'] = true;

       if ($manager->season_over) {
         $teams[$season]['MARKET_STATUS']['SEASON_OVER'] = 1;      
       } else if (isset($manager->next_tour_date) && $manager->manager_trade_allow) {
         $teams[$season]['MARKET_STATUS']['MARKET_OPEN']['START_DATE'] = $manager->next_tour_date_utc;   
         $teams[$season]['MARKET_STATUS']['MARKET_OPEN']['UTC'] = $manager->utc;   
       } else if (isset($manager->current_tour_end_date)) {
         $teams[$season]['MARKET_STATUS']['NOMARKET']['START_DATE'] = $manager->current_tour_end_date;   
         $teams[$season]['MARKET_STATUS']['NOMARKET']['UTC'] = $manager->utc;   
       } else if (!$manager->manager_trade_allow)
         $teams[$season]['MARKET_STATUS']['NOMARKET_DELAY'] = 1;

     }

     if ($team_ids == "")
       $team_ids = "-1";

     // get clan team members

     $sql = "SELECT a.* , MSS.POINTS, MSS.PLACE, MU.LAST_REVIEWED, MU.COMPLETENESS
	     FROM (
		SELECT CT.SEASON_ID, CT.TEAM_ID, CTM.USER_ID, U.USER_NAME 
			FROM manager_seasons MS, clan_teams CT, clan_team_members CTM 
			LEFT JOIN users U on U.USER_ID=CTM.USER_ID			
		WHERE CT.TEAM_ID in (".$team_ids.")
			AND CTM.TEAM_ID=CT.TEAM_ID
			AND CTM.DATE_JOINED <= NOW() AND CTM.DATE_LEFT IS NULL
			AND MS.PUBLISH='Y' 
			AND MS.ALLOW_CLAN_TEAMS = 'Y'
			AND CT.SEASON_ID=MS.SEASON_ID) a
			LEFT JOIN manager_standings MSS ON MSS.USER_ID = a.USER_ID
				AND MSS.MSEASON_ID = a.SEASON_ID
			LEFT JOIN manager_users MU on a.USER_ID=MU.USER_ID
				AND MU.SEASON_ID = a.SEASON_ID";
     $db->query($sql);   
     while ($row = $db->nextRow()) {
       $teams[$row['SEASON_ID']]['ACTIVE_MEMBERS'][$row['USER_ID']] = $row;  
       $teams[$row['SEASON_ID']]['ACTIVE_MEMBERS'][$row['USER_ID']]['POINTS_LAST_TOUR'] = $teams[$row['SEASON_ID']]['MEMBERS'][$row['USER_ID']]['POINTS_LAST_TOUR'];  
       unset($teams[$row['SEASON_ID']]['MEMBERS'][$row['USER_ID']]);   
     }

     $sql = "SELECT CT.TEAM_ID
		FROM clan_teams CT, manager_clan_team_standings MCTS
		WHERE CT.TEAM_ID in (".$team_ids.")
                        AND CTM.TEAM_ID=MCTS.TEAM_ID";
     $db->query($sql);   
     if ($row = $db->nextRow()) {
       unset($teams[$row['SEASON_ID']]['FREE_CHANGE']);
     }

     return $teams;

   }

   function getClanTeam($team_id) {
     global $db;

     $this->getClanMembers();
     $team['MEMBERS'] = $this->clan_members;

     // get clan team members
     $sql = "SELECT CT.SEASON_ID
		FROM clan_teams CT
		WHERE CT.TEAM_ID =".$team_id;
     $db->query($sql);   
     while ($row = $db->nextRow()) {
       $team['SEASON_ID'] = $row['SEASON_ID'];
     }

     $sql = "SELECT CT.TEAM_ID, CTM.USER_ID, CT.SEASON_ID, U.USER_NAME
		FROM clan_teams CT, clan_team_members CTM
			LEFT JOIN users U on U.USER_ID=CTM.USER_ID			
		WHERE CT.TEAM_ID in (".$team_id.")
                        AND CTM.TEAM_ID=CT.TEAM_ID
			AND CTM.DATE_JOINED <= NOW() AND CTM.DATE_LEFT IS NULL";
     $db->query($sql);   
     while ($row = $db->nextRow()) {
       $team['ACTIVE_MEMBERS'][$row['USER_ID']] = $row;  
       unset($team['MEMBERS'][$row['USER_ID']]);   
     }

      $sql = "SELECT CT.TEAM_ID
		FROM clan_teams CT, manager_clan_team_standings MCTS
		WHERE CT.TEAM_ID in (".$team_id.")
                        AND CTM.TEAM_ID=MCTS.TEAM_ID";
      $db->query($sql);   
      if ($row = $db->nextRow()) {
      } else {
        $team['FREE_CHANGE'] = 1;
      }

//print_r($team);
     return $team;

   }

  function saveTeam() {
    global $_POST;
    global $db;
    global $auth;

//$db->showquery=true;
    $free_change = true;
      $sql = "SELECT CT.TEAM_ID
		FROM clan_teams CT, manager_clan_teams_standings MCTS
		WHERE CT.TEAM_ID in (".$_POST['team_id'].")
                        AND CT.TEAM_ID=MCTS.TEAM_ID";
     $db->query($sql);   
     if ($row = $db->nextRow()) {
        $free_change = false;
     }

    if (isset($_POST['save_team']) && isset($_POST['team_id']) && isset($_POST['clan_id']) && isset($_POST['season_id'])
	&& $_POST['clan_id'] == $this->clan_id && ($this->clan_data['CLAN_FUND'] >= 1 || $free_change)) {
      $team = $this->getClanTeam($_POST['team_id']);
      $manager = new Manager($team['SEASON_ID']);
      $tour_id = $manager->getCurrentTour();   

      if ($manager->manager_trade_allow) {
        $changes = false;
        // is race active
        if (isset($_POST['members'])) {
          for ($c = 0; $c < sizeof($_POST['members']); $c++) {
            if (!isset($team['ACTIVE_MEMBERS'][$_POST['members'][$c]])) {
              unset($sdata);
              $sdata['team_id'] = $_POST['team_id'];
              $sdata['DATE_JOINED'] = "NOW()";
              $member = $_POST['members'][$c];
              $sdata['user_id'] = $member;
              $db->insert("clan_team_members", $sdata);
    	      unset($team['MEMBERS'][$member]);
              $changes = true;
            } else {
              unset($team['ACTIVE_MEMBERS'][$_POST['members'][$c]]);
            }
          }
        } 
  
        if (isset($team['ACTIVE_MEMBERS'])) {
          foreach ($team['ACTIVE_MEMBERS'] as $member) {
            unset($sdata);
            $sdata['DATE_LEFT'] = "NOW()";
  	    $sdata['STATUS'] = "2";
            $db->update("clan_team_members", $sdata, "TEAM_ID=".$_POST['team_id']." AND USER_ID=".$member['USER_ID']." AND DATE_LEFT IS NULL");
            $changes = true;
          }
        }
  
        if ($changes && !$free_change) {
          $credits = new Credits();
          $credits->updateClanCredits($_POST['clan_id'], -1);
  
          $clan_log = new ClanLog();
          $clan_log->logEvent ($_POST['clan_id'], 5, 1, '', $_POST['team_id']);
  
          // update manager_clan_teams_tours
          // if not started do nothing
          // 
          return 1;
        }
      } else return -1;
    } else return -1;
  }


   function getClanMembersStandings($mseason_id, &$team, $tour_id, $filter_active = false) {
     global $db;

     $sql="SELECT MSS.PLACE, MSS.POINTS, UA.USER_ID, MUT.POINTS as POINTS_LAST_TOUR, MU.LAST_REVIEWED, MU.COMPLETENESS
             FROM clan_members UA
		 LEFT JOIN manager_standings MSS ON MSS.USER_ID = UA.USER_ID
				AND MSS.MSEASON_ID = ".$mseason_id."
		 LEFT JOIN manager_users MU ON MU.USER_ID = UA.USER_ID
				AND MU.SEASON_ID = ".$mseason_id."
		 LEFT JOIN manager_users_tours MUT ON MUT.USER_ID = UA.USER_ID
				AND MUT.SEASON_ID = ".$mseason_id."
				AND MUT.TOUR_ID=".$tour_id."
             WHERE UA.CLAN_ID = ".$this->clan_id."
		AND UA.STATUS in (1,2)";
//echo $sql;
     $db->query($sql);
     while ($row = $db->nextRow()) {
       if (!$filter_active || isset($team[$row['USER_ID']])) {
         $team[$row['USER_ID']]['PLACE'] = $row['PLACE'];
         $team[$row['USER_ID']]['POINTS'] = $row['POINTS'];
         $team[$row['USER_ID']]['POINTS_LAST_TOUR'] = $row['POINTS_LAST_TOUR'];
         $team[$row['USER_ID']]['COMPLETENESS'] = $row['COMPLETENESS'];
         $team[$row['USER_ID']]['LAST_REVIEWED'] = $row['LAST_REVIEWED'];
       }
     }

     //print_r($team);
  }

  function getClanUserTeam($user_id) {
     global $db;
     global $auth;
     global $_SESSION;

     $teams = array();
     $sql = "SELECT CT.SEASON_ID, CT.TEAM_ID, CTM.USER_ID, MSD.SEASON_TITLE
			FROM manager_seasons MS
                        		   LEFT JOIN manager_seasons_details MSD ON MS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."	   
			, clan_teams CT, clan_team_members CTM 
		WHERE  CTM.TEAM_ID=CT.TEAM_ID
			AND CT.CLAN_ID=".$this->clan_id."
			AND CTM.DATE_JOINED <= NOW() AND CTM.DATE_LEFT IS NULL
			AND MS.PUBLISH='Y' 
			AND MS.END_DATE > NOW()
			AND MS.ALLOW_CLAN_TEAMS = 'Y'
			AND CT.SEASON_ID=MS.SEASON_ID
			AND CTM.USER_ID=".$auth->getUserId();
//echo $sql;
     $db->query($sql);   
     while ($row = $db->nextRow()) {
       $team = $row;  
       $teams[] = $team;  
     }
     return $teams;
  }
}
?>