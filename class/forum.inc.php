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

class Forum {
  var $forum_id;
   
  function Forum() {
  }

 function getForumName($forum_id, $topic_id='') {
    global $db;
    global $_SESSION;
    if (isset($forum_id) && is_numeric($forum_id)) {

       $sql = "SELECT F.FORUM_ID, FD.FORUM_NAME
               FROM 
                 forum_details FD, forum F
               WHERE
                 F.FORUM_ID='".$forum_id."' 
  	         AND F.FORUM_ID=FD.FORUM_ID AND FD.LANG_ID IN (".$_SESSION['lang_id'].",0) 
               ORDER BY FD.FORUM_NAME";
       $db->query($sql);

       if ($row = $db->nextRow()) {
         return $row['FORUM_NAME'];
       }
       else return '';
    } else if (isset($topic_id) && is_numeric($topic_id)) {
       $sql = "SELECT F.FORUM_ID, FD.FORUM_NAME
               FROM 
                 forum_details FD, forum F, topic T 
               WHERE
                 T.TOPIC_ID='".$topic_id."'
                 AND F.FORUM_ID=T.FORUM_ID
                 AND T.LANG_ID IN (".$_SESSION['lang_id'].",0) 
                 AND F.FORUM_ID=FD.FORUM_ID AND FD.LANG_ID IN (".$_SESSION['lang_id'].",0)";
       $db->query($sql);
       if ($row = $db->nextRow()) {
         return $row['FORUM_NAME'];
       }
       else return '';

    }

 } 

 function getForumNameFromPostId($post_id) {
    global $db;
    global $_SESSION;
    if (isset($post_id) && is_numeric($post_id)) {
       $sql = "SELECT F.FORUM_ID, FD.FORUM_NAME
               FROM 
                 forum_details FD, forum F, topic T, post P 
               WHERE
                 P.POST_ID='".$post_id."'
		 AND T.TOPIC_ID=P.TOPIC_ID
                 AND F.FORUM_ID=T.FORUM_ID
                 AND T.LANG_ID IN (".$_SESSION['lang_id'].",0) 
                 AND F.FORUM_ID=FD.FORUM_ID AND FD.LANG_ID IN (".$_SESSION['lang_id'].",0)";
       $db->query($sql);
       if ($row = $db->nextRow()) {
         return $row['FORUM_NAME'];
       }
       else return '';
    }
 } 

 function getTopicName($topic_id) {
    global $db;
    global $_SESSION;
    if (isset($topic_id) && is_numeric($topic_id)) {
       $sql = "SELECT T.TOPIC_NAME
               FROM 
                 topic T 
               WHERE
                 T.TOPIC_ID='".$topic_id."'
                 AND T.LANG_ID IN (".$_SESSION['lang_id'].",0)";
       $db->query($sql);
       if ($row = $db->nextRow()) {
         return $row['TOPIC_NAME'];
       }
       else return '';

    }
 } 

 function getTopicNameFromPostId($post_id) {
    global $db;
    global $_SESSION;
    if (isset($post_id) && is_numeric($post_id)) {
       $sql = "SELECT T.TOPIC_NAME
               FROM 
                 topic T, post P 
               WHERE
                 P.POST_ID='".$post_id."'
		 AND T.TOPIC_ID=P.TOPIC_ID
                 AND T.LANG_ID IN (".$_SESSION['lang_id'].",0)";
       $db->query($sql);
       if ($row = $db->nextRow()) {
         return $row['TOPIC_NAME'];
       }
       else return '';

    }
 } 

  function getTopicForNews($news_id) {
    global $db;
    global $_SESSION;

    if (isset($news_id) && is_numeric($news_id)) {

      $sql = "SELECT ND.TOPIC_ID
		FROM news N, news_details ND 
		WHERE N.PUBLISH='Y' 
                      AND N.NEWS_ID=ND.NEWS_ID 
		      AND N.NEWS_ID='".$news_id."'
                      AND ND.LANG_ID='".$_SESSION['lang_id']."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }

  function getTopicForManagerLeagues($league_id) {
    global $db;
    global $_SESSION;

    if (isset($league_id) && is_numeric($league_id)) {

      $sql = "SELECT N.TOPIC_ID
		FROM manager_leagues N
		WHERE N.LEAGUE_ID='".$league_id."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }

  function getTopicForRvsManagerLeagues($league_id) {
    global $db;
    global $_SESSION;

    if (isset($league_id) && is_numeric($league_id)) {

      $sql = "SELECT N.TOPIC_ID
		FROM rvs_manager_leagues N
		WHERE N.LEAGUE_ID='".$league_id."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }

  function getTopicForSoloManagerLeagues($league_id) {
    global $db;
    global $_SESSION;

    if (isset($league_id) && is_numeric($league_id)) {

      $sql = "SELECT N.TOPIC_ID
		FROM solo_manager_leagues N
		WHERE N.LEAGUE_ID='".$league_id."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }

  function getTopicForWagerLeagues($league_id) {
    global $db;
    global $_SESSION;

    if (isset($league_id) && is_numeric($league_id)) {

      $sql = "SELECT N.TOPIC_ID
		FROM wager_leagues N
		WHERE N.LEAGUE_ID='".$league_id."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }

  function getTopicForArrangerLeagues($league_id) {
    global $db;
    global $_SESSION;

    if (isset($league_id) && is_numeric($league_id)) {

      $sql = "SELECT N.TOPIC_ID
		FROM bracket_leagues N
		WHERE N.LEAGUE_ID='".$league_id."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }

  function getTopicForManagerTournament($mt_id) {
    global $db;
    global $_SESSION;

    if (isset($mt_id) && is_numeric($mt_id)) {

      $sql = "SELECT N.TOPIC_ID
		FROM manager_tournament N
		WHERE N.MT_ID=".$mt_id;
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }

  function getTopicForManagerBattle($battle_id) {
    global $db;
    global $_SESSION;

    if (isset($battle_id) && is_numeric($battle_id)) {

      $sql = "SELECT N.TOPIC_ID
		FROM manager_battles N
		WHERE N.BATTLE_ID='".$battle_id."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }

  function getTopicForSurvey($id) {
    global $db;
    global $_SESSION;

    if (isset($id) && is_numeric($id)) {

      $sql = "SELECT N.TOPIC_ID
		FROM survey_details N
		WHERE N.ID='".$id."' AND N.LANG_ID=".$_SESSION['lang_id'];
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }

  function getTopicForClub($id) {
    global $db;
    global $_SESSION;

    if (isset($id) && is_numeric($id)) {

      $sql = "SELECT N.TOPIC_ID
		FROM forum_groups N
		WHERE N.GROUP_ID='".$id."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }

  function getTopicForClubEvent($id) {
    global $db;
    global $_SESSION;

    if (isset($id) && is_numeric($id)) {

      $sql = "SELECT N.TOPIC_ID
		FROM forum_groups_events N
		WHERE N.EVENT_ID='".$id."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }

  function doesTopicExist($topic_id) {
    global $db;
    global $_SESSION;
    global $auth;
    global $langs;

    if (isset($topic_id) && is_numeric($topic_id) && $topic_id > -1) {

      $sql_count = "SELECT *
                     FROM topic T
                     WHERE T.TOPIC_ID='".$topic_id."' AND T.LANG_ID IN (".$_SESSION['lang_id'].",0)" ; 
      $db->query($sql_count);
      if ($row = $db->nextRow()) {
        $this->forum_id = $row['FORUM_ID'];
        return true;
      }
    } 
    return false;
  }

  function getTopicForVideo($video_id) {
    global $db;
    global $_SESSION;

    if (isset($video_id) && is_numeric($video_id)) {

      $sql = "SELECT ND.TOPIC_ID
		FROM video N, video_details ND 
		WHERE N.PUBLISH='Y' 
                      AND N.VIDEO_ID=ND.VIDEO_ID 
		      AND N.VIDEO_ID='".$video_id."'
                      AND ND.LANG_ID='".$_SESSION['lang_id']."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }

  function getTopicForClan($id) {
    global $db;
    global $_SESSION;

    if (isset($id) && is_numeric($id)) {

      $sql = "SELECT N.TOPIC_ID
		FROM clans N
		WHERE N.CLAN_ID='".$id."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return !empty($row['TOPIC_ID']) ? $row['TOPIC_ID'] : -1;
      }  
    } 
    return -1;
  }


  function getForumFromTopic($topic_id) {
    global $db;
    global $_SESSION;
    global $auth;
    global $langs;

    if (isset($topic_id) && is_numeric($topic_id) && $topic_id > -1) {

      $sql_count = "SELECT *
                     FROM topic T
                     WHERE T.TOPIC_ID='".$topic_id."'" ; 
      $db->query($sql_count);
      if ($row = $db->nextRow()) {
        return $row['FORUM_ID'];
      }
    } 
    return -1;
  }


 function getPostInfo($post_id) {
    global $db;
    global $_SESSION;
    if (isset($post_id) && is_numeric($post_id)) {
       $sql = "SELECT F.FORUM_ID, FD.FORUM_NAME, T.TOPIC_ID, T.TOPIC_NAME, P.TEXT, U.USER_NAME
               FROM 
                 forum_details FD, forum F, topic T, post P, users U
               WHERE
                 P.POST_ID='".$post_id."'
                 AND P.USER_ID=U.USER_ID
		 AND T.TOPIC_ID=P.TOPIC_ID
                 AND F.FORUM_ID=T.FORUM_ID
                 AND T.LANG_ID IN (".$_SESSION['lang_id'].",0) 
                 AND F.FORUM_ID=FD.FORUM_ID AND FD.LANG_ID IN (".$_SESSION['lang_id'].",0)";
       $db->query($sql);
       if ($row = $db->nextRow()) {
         return $row;
       }
       else return '';
    }
 } 

}


?>