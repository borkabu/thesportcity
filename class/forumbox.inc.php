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

class ForumBox extends Box{
  var $forum;
  var $topic_id;
  var $forum_id;
  var $cat_id;

  function ForumBox($langs, $lang) {
    parent::Box($langs, $lang);
    $this->forum = new Forum();
  }

  function getForums ($cat='',$page=1,$perpage=PAGE_SIZE) {
    global $db;
    global $_SESSION;
    global $smarty;

    // content
    $forums = $this->getForumsData($cat, $page, $perpage);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/forums.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/forums.smarty<br>'.($stop-$start);
    return $output;
  } 

  function getForumsSummary () {
    global $db;
    global $auth;
    global $_SESSION;
    global $smarty;

    // content
    $sql = "SELECT COUNT( DISTINCT F.FORUM_ID ) UNREAD, FD.LANG_ID, 
			L.LATIN_NAME, L.SHORT_CODE
     FROM 
	  forum_details FD
          left join languages L ON FD.LANG_ID=L.ID  , forum F
        left join topic T ON T.FORUM_ID=F.FORUM_ID AND T.LANG_ID > 0 AND T.PUBLISH ='Y'
        left join topic_track TT ON T.TOPIC_ID=TT.TOPIC_ID AND TT.USER_ID=".$auth->getUserId()."        
     WHERE
       F.PUBLISH='Y' 
	    AND F.FORUM_ID=FD.FORUM_ID AND FD.LANG_ID > 0
	    AND T.LAST_POSTER_ID <> ".$auth->getUserId()."
            AND FD.LANG_ID=T.LANG_ID
          AND ((TT.MARK_TIME < T.LAST_POSTED) OR (TT.MARK_TIME IS NULL) OR (TT.MARK_TIME IS NULL AND T.LAST_POSTED IS NULL))
        GROUP BY FD.LANG_ID, L.LATIN_NAME, L.SHORT_CODE";
    $db->query($sql);

    while ($row = $db->nextRow()) {
       $summary[$row['LANG_ID']] = $row;
    }

    if (isset($summary))
      $smarty->assign("summary", $summary);
  } 

  function getTopics ($forum_id, $page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    global $auth;
    global $_SESSION;
    global $langs;
    global $forums;  

    // content
    
    $key = array_search($forum_id, $forums); 
    if ($key=='MANAGER_LEAGUES' || $key=='RVS_MANAGER_LEAGUES' || $key=='MANAGER_TOURNAMENT' || $key=='SOLO_MANAGER_LEAGUES') {
      $forum = $this->getTopicsData($forum_id, $page, $perpage, $key);
      $forum['LEAGUE'] = 1;
    } else if ($key=='CLUBS') {
      $forum = $this->getTopicsData($forum_id, $page, $perpage, $key);
      $forum['CLUB'] = 1;
    } else if ($key=='CLANS_PUBLIC') {
      $forum = $this->getTopicsData($forum_id, $page, $perpage, $key);
      $forum['CLAN'] = 1;
    } else {
      $forum = $this->getTopicsData($forum_id, $page, $perpage);
    }

    $this->forum_id = $forum_id;
    if ($auth->userOn()) {
      $forumPermission = new ForumPermission();
      switch ($forumPermission->canStartTopic($forum_id)) {
       case 0: 
          $forum['FORUM_ID'] = $forum_id;
          $forum['LANG_ID'] = $_SESSION['lang_id'];
          $smarty->assign("can_edit", 1);
          break;
       case 1:
	 // flood protection
       case 2:
	 // not enough comment trust
          $forum['ERROR']['MSG']=$langs['LANG_ERROR_COMMENT_START_TOPIC_2_U'];
          break;
       case 3:
	 // forum cannot accept manual entries
          $forum['ERROR']['MSG']=$langs['LANG_ERROR_COMMENT_START_TOPIC_U'];
          break;
      }
    } else {
      $forum['ERROR']['MSG']=$langs['LANG_ERROR_COMMENT_LOGIN_U'];
    }


    $this->rows = $forum['_ROWS'];	
    $smarty->assign("forum", $forum);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/forum.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/forum.smarty'.($stop-$start);

    return $output;
  } 

  function getPosts ($topic_id, $page=1,$perpage=PAGE_SIZE, $forum_id=-1, $item_id=-1, $only_post=false) {
    global $tpl;
    global $db;
    global $_SESSION;
    global $_SERVER;
    global $auth;
    global $langs;
    global $forums;
    global $smarty;
    
//$db->showquery=true;
    // content
    $this->topic_id = $topic_id;

    $key = array_search($forum_id, $forums); 
    $topic = '';
    $flood_protection = array();
    $topic = $this->getPostsData($topic_id, $page, $perpage, $forum_id, $item_id, $key);

      if (!$only_post) {
        if (($forum_id > -1 || $topic_id > -1) && isset($topic['FORUM'])) {
          $topic['FORUM']['NAVIGATION'] = 1;
          if (!isset($topic['FORUM']['CAT_NAME']))
	    $topic['FORUM']['CAT_NAME'] = '';
          if (!isset($topic['FORUM']['TOPIC_NAME']))
	    $topic['FORUM']['TOPIC_NAME'] = '';
        }
      }
      if ($auth->userOn() && !$only_post) {
        $notification = new Notification();
        $notification->removeTopicEmails($topic_id, $auth->getUserId());
        if ($topic_id > 1  && isset($topic['FORUM'])) 
          $notification->trackTopic($topic_id, $auth->getUserId(), $topic['FORUM']['FORUM_ID']);

        $forumPermission = new ForumPermission();
        $can_add = $forumPermission->canAddComment($topic_id);

        switch ($can_add) {
          case 0:          
            $topic['FORUM']['LOGGED']['FORUM_ID'] = $topic['FORUM']['FORUM_ID'];
            if ($item_id > 0) 
              $topic['FORUM']['LOGGED']['ITEM_ID'] = $item_id;
            $topic['FORUM']['LOGGED']['TOPIC_ID'] = $topic_id;
            $topic['FORUM']['LOGGED']['LANG_ID'] = $_SESSION['lang_id'];
    	    break;
          case 1:
	    // flood protection
            $topic['FORUM']['LOGGED']['FORUM_ID'] = $topic['FORUM']['FORUM_ID'];
            $topic['FORUM']['LOGGED']['TOPIC_ID'] = $topic_id;
            $topic['FORUM']['LOGGED']['LANG_ID'] = $_SESSION['lang_id'];	    
  	    $flood_protection['TIMETAG'] = $forumPermission->timegap;
	    $flood_protection['TOPIC_ID'] = $topic_id;
	    $flood_protection['REQUEST_URI'] = $_SERVER["REQUEST_URI"];
            $topic['FORUM']['ERROR_FLOOD']['MSG']=$langs['LANG_ERROR_COMMENT_FLOOD_U'];
            break;
          case 2:
            // quote exceeded
            $topic['FORUM']['ERROR']['MSG']=$langs['LANG_ERROR_COMMENT_QUOTE_EXCEED_U'];
        }        
      } else if (!$auth->userOn()) {
          $topic['FORUM']['ERROR']['MSG']=$langs['LANG_ERROR_COMMENT_LOGIN_U'];
    }
    $this->rows = $topic['_ROWS'];	

    $tpl->setCacheLevel(TPL_CACHE_NOTHING);  
    $template_name = '';
    if ($key=='CLUBS') {
      $template_name = 'smarty_tpl/topic_club.smarty';
    } else {
      $template_name = 'smarty_tpl/topic.smarty';
    }
    $smarty->assign('topic', $topic);
    if (isset($flood_protection['TIMETAG']))
      $smarty->assign('flood_protection', $flood_protection);
    $start = getmicrotime();
    $output = $smarty->fetch($template_name);
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo $template_name.($stop-$start);

    return $output;
  } 

  function getComments ($item_id, $type, $page=1,$perpage=PAGE_SIZE) {
    global $forums;  

    $this->topic_id = '';
    if ($type == 'NEWS' || $type == 'BLOGS') {
      $this->topic_id = $this->forum->getTopicForNews($item_id);
    } else if ($type == 'MANAGER_LEAGUES') {
      $this->topic_id = $this->forum->getTopicForManagerLeagues($item_id);
    } else if ($type == 'RVS_MANAGER_LEAGUES') {
      $this->topic_id = $this->forum->getTopicForRvsManagerLeagues($item_id);
    } else if ($type == 'SOLO_MANAGER_LEAGUES') {
      $this->topic_id = $this->forum->getTopicForSoloManagerLeagues($item_id);
    } else if ($type == 'MANAGER_TOURNAMENT') {
      $this->topic_id = $this->forum->getTopicForManagerTournament($item_id);
    } else if ($type == 'MANAGER_BATTLES') {
      $this->topic_id = $this->forum->getTopicForManagerBattle($item_id);
    } else if ($type == 'SURVEYS') {
      $this->topic_id = $this->forum->getTopicForSurvey($item_id);
    } else if ($type == 'VIDEO') {
      $this->topic_id = $this->forum->getTopicForVideo($item_id);
    } else if ($type == 'WAGER_LEAGUES') {
      $this->topic_id = $this->forum->getTopicForWagerLeagues($item_id);
    } else if ($type == 'ARRANGER_LEAGUES') {
      $this->topic_id = $this->forum->getTopicForArrangerLeagues($item_id);
    } else if ($type == 'CLUBS') {
      $this->topic_id = $this->forum->getTopicForClub($item_id);
    } else if ($type == 'CLUBS_EVENTS') {
      $this->topic_id = $this->forum->getTopicForClubEvent($item_id);
    } else if ($type == 'CLANS_PUBLIC') {
      $this->topic_id = $this->forum->getTopicForClan($item_id);
    }

    return $this->getPosts($this->topic_id, $page, $perpage, $forums[$type], $item_id);
  } 

  function getTopicsData($forum_id,$page=1,$perpage=PAGE_SIZE, $type='') {
    global $db;
    global $_SESSION;
    global $auth;
    global $html_page;
    
    $moderator = false;
    if (isset($forum_id) && is_numeric($forum_id)) {
       $forumPermission = new ForumPermission();

       $where_groups = " AND F.GROUP_ID IS NULL";
       if ($auth->userOn()) {
         $user = new User($auth->getUserId());
         if ($user->getGroups() != '' && !$auth->isAdmin()) {
           $where_groups = " AND (F.GROUP_ID IS NULL OR F.GROUP_ID IN (".$user->group_str."))";
         } else if ($auth->isAdmin())
          $where_groups = "";
         $moderator = $auth->isForumModerator($forum_id);
       }

       $sql_count = "SELECT COUNT(T.TOPIC_ID) ROWS
                      FROM topic T, forum F
                      WHERE T.FORUM_ID='".$forum_id."' 
			AND T.FORUM_ID=F.FORUM_ID ".$where_groups."
			AND F.PUBLISH='Y'
			AND T.LANG_ID IN (".$_SESSION['lang_id'].",0)" ; 
       $db->query($sql_count);
       while ($row = $db->nextRow()) {
         $count = $row['ROWS'];
       }

       $extra_fields = '';
       $where_extra = '';
       if ($type == 'MANAGER_LEAGUES') {
         $extra_fields = ', MSD.SEASON_TITLE as DESCR';
         $where_extra = " LEFT JOIN manager_leagues ML ON ML.TOPIC_ID=T.TOPIC_ID AND ML.TOPIC_ID>0 
  			  LEFT JOIN manager_seasons_details MSD ON MSD.SEASON_ID=ML.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id'];
       } else if ($type == 'RVS_MANAGER_LEAGUES') {
         $extra_fields = ', MSD.SEASON_TITLE as DESCR';
         $where_extra = " LEFT JOIN rvs_manager_leagues ML ON ML.TOPIC_ID=T.TOPIC_ID AND ML.TOPIC_ID>0 
  			  LEFT JOIN manager_seasons_details MSD ON MSD.SEASON_ID=ML.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id'];
       } else if ($type == 'SOLO_MANAGER_LEAGUES') {
         $extra_fields = ', MSD.SEASON_TITLE as DESCR';
         $where_extra = " LEFT JOIN solo_manager_leagues ML ON ML.TOPIC_ID=T.TOPIC_ID AND ML.TOPIC_ID>0 
  			  LEFT JOIN manager_seasons_details MSD ON MSD.SEASON_ID=ML.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id'];
       } else if ($type == 'MANAGER_TOURNAMENT') {
         $extra_fields = ', MSD.SEASON_TITLE as DESCR';
         $where_extra = " LEFT JOIN manager_tournament ML ON ML.TOPIC_ID=T.TOPIC_ID AND ML.TOPIC_ID>0 
  			  LEFT JOIN manager_seasons_details MSD ON MSD.SEASON_ID=ML.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id'];
       } else if ($type == 'CLUBS') { 
         $extra_fields = ', FGD.GROUP_NAME as TOPIC_TITLE';
         $where_extra = " LEFT JOIN forum_groups FG ON FG.TOPIC_ID=T.TOPIC_ID AND FG.TOPIC_ID>0 
  			  LEFT JOIN forum_groups_details FGD ON FGD.GROUP_ID=FG.GROUP_ID AND FGD.LANG_ID=".$_SESSION['lang_id'];
       } else if ($type == 'CLANS_PUBLIC') { 
         $extra_fields = ', FG.CLAN_NAME as TOPIC_TITLE';
         $where_extra = " LEFT JOIN clans FG ON FG.TOPIC_ID=T.TOPIC_ID AND FG.TOPIC_ID>0";
       } /*else if ($type == 'CLUBS_EVENTS') { 
         $extra_fields = ', FGED.TITLE as TOPIC_TITLE';
         $where_extra = " LEFT JOIN forum_groups_events FGE ON FGE.TOPIC_ID=T.TOPIC_ID AND FGE.TOPIC_ID>0 
  			  LEFT JOIN forum_groups_events_details FGED ON FGED.EVENT_ID=FGE.EVENT_ID AND FGED.LANG_ID=".$_SESSION['lang_id'];
       }*/ 

//echo $page;
       $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;
       $utc = $auth->getUserTimezoneName();
       if ($auth->userOn()) {
          $sql = "SELECT FCD.CAT_NAME, FCD.CAT_ID, F.FORUM_ID, FD.FORUM_NAME, T.TOPIC_NAME, T.TOPIC_DESCR, T.TOPIC_ID, 
			DATE_ADD(T.DATE_POSTED, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) as DATE_POSTED,
			DATE_ADD(T.LAST_POSTED, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) as LAST_POSTED,
			T.POSTS, T.USER_ID TOPIC_OWNER, TT.MARK_TIME, UNIX_TIMESTAMP( TT.MARK_TIME ) AS TSTMP, PINNED,
			TT.MARK_TIME < T.LAST_POSTED AS TRACKER, T.LAST_POSTER_ID, U.USER_NAME,U2.USER_NAME as LAST_POSTER".$extra_fields."
               FROM 
                 forum_cats FC 
                    LEFT JOIN forum_cats_details FCD on FC.CAT_ID=FCD.CAT_ID AND FCD.LANG_ID=".$_SESSION['lang_id'].",
		 forum_details FD, forum F
                  left join topic T ON T.FORUM_ID=F.FORUM_ID AND T.LANG_ID IN (".$_SESSION['lang_id'].",0) AND T.PUBLISH ='Y'
		  ".$where_extra."
		  left join topic_track TT ON T.TOPIC_ID=TT.TOPIC_ID AND TT.USER_ID=".$auth->getUserId()."
		  left join users U ON U.USER_ID=T.USER_ID
		  left join users U2 ON U2.USER_ID=T.LAST_POSTER_ID
               WHERE
                 FC.CAT_ID=F.CAT_ID
                 AND F.FORUM_ID='".$forum_id."' 
                 AND F.PUBLISH='Y' ".$where_groups."
		    AND F.FORUM_ID=FD.FORUM_ID AND FD.LANG_ID IN (".$_SESSION['lang_id'].",0) 
	          GROUP BY T.TOPIC_ID
               ORDER BY T.PINNED DESC, T.LAST_POSTED DESC, T.DATE_POSTED DESC, FD.FORUM_NAME ".$limitclause;
       } else {
          $sql = "SELECT FCD.CAT_NAME, FCD.CAT_ID, F.FORUM_ID, FD.FORUM_NAME, T.TOPIC_NAME, T.TOPIC_DESCR, T.TOPIC_ID, T.DATE_POSTED, T.LAST_POSTED, 
		T.POSTS, T.USER_ID TOPIC_OWNER, 0 AS TRACKER, T.LAST_POSTER_ID, PINNED,
		U.USER_NAME,U2.USER_NAME as LAST_POSTER".$extra_fields."
               FROM 
                 forum_cats FC 
                    LEFT JOIN forum_cats_details FCD on FC.CAT_ID=FCD.CAT_ID AND FCD.LANG_ID=".$_SESSION['lang_id'].",
		 forum_details FD, forum F
                  left join topic T ON T.FORUM_ID=F.FORUM_ID AND T.LANG_ID IN (".$_SESSION['lang_id'].",0) AND T.PUBLISH ='Y'
		  ".$where_extra."
		  left join users U ON U.USER_ID=T.USER_ID
		  left join users U2 ON U2.USER_ID=T.LAST_POSTER_ID
               WHERE
                 FC.CAT_ID=F.CAT_ID
                 AND F.FORUM_ID='".$forum_id."' 
                 AND F.PUBLISH='Y' ".$where_groups."
		    AND F.FORUM_ID=FD.FORUM_ID AND FD.LANG_ID IN (".$_SESSION['lang_id'].",0) 
	          GROUP BY T.TOPIC_ID
               ORDER BY T.PINNED DESC, T.LAST_POSTED DESC, T.DATE_POSTED DESC, FD.FORUM_NAME ".$limitclause;
       }
//echo $sql;
       $db->query($sql);
/*       if ($db->rows() == 0) {
         header("Location: index.php");
         exit;
       }*/

       while ($row = $db->nextRow()) {
         $data['FORUM_NAME'] = $row['FORUM_NAME'];
         $data['FORUM_ID'] = $row['FORUM_ID'];
         $data['CAT_NAME'] = $row['CAT_NAME'];
         $data['CAT_ID'] = $row['CAT_ID'];
         $data['LANG_ID'] = $_SESSION['lang_id'];
         if ($count > 0) {
           $topic = $row;
           $topic['UTC'] = $utc;
         }
         else $data['FORUM']['NORECORDS'] = $row;
         if (!empty($row['TOPIC_ID']) && (empty($row['MARK_TIME']) || !isset($row['TRACKER']) || (isset($row['TRACKER']) && $row['TRACKER'] != 0 && $row['TRACKER'] != '')) && $row['LAST_POSTER_ID'] != $auth->getUserId()) {
	   $topic['TRACK']['TOPIC_ID'] = $row['TOPIC_ID'];
           if (!empty($row['TSTMP']))
  	     $topic['TRACK']['TSTMP'] = $row['TSTMP'];
           else $topic['TRACK']['TSTMP'] = -1;
         }

         if ($forumPermission->canDeleteTopic($forum_id, $row['TOPIC_ID'], $row['TOPIC_OWNER'], $row['POSTS'], $moderator)) {
           $topic['DELETE']['TOPIC_ID'] = $row['TOPIC_ID'];
           $topic['DELETE']['FORUM_ID'] = $forum_id;
         }

         if ($forumPermission->canEditTopic($forum_id, $row['TOPIC_ID'], $row['TOPIC_OWNER'], $moderator)) {
           $topic['EDIT']['TOPIC_ID'] = $row['TOPIC_ID'];
           $topic['EDIT']['FORUM_ID'] = $forum_id;
         }

         if ($forumPermission->canPinTopic($forum_id, $row['TOPIC_ID'], $moderator)) {
           if ($row['PINNED'] == 0) {
             $topic['PIN']['TOPIC_ID'] = $row['TOPIC_ID'];
             $topic['PIN']['FORUM_ID'] = $forum_id;
           } else {
             $topic['UNPIN']['TOPIC_ID'] = $row['TOPIC_ID'];
             $topic['UNPIN']['FORUM_ID'] = $forum_id;
             $topic['TOPIC_PINNED']['TOPIC_ID'] = $row['TOPIC_ID'];
           }
         }

         $html_page->page_title = $row['FORUM_NAME'];
         if (isset($topic))
           $data['TOPICS'][]= $topic;
       }

      $data['_ROWS'] = $count;
     return $data;
    }
 }


  function getForumsData ($cat='',$page=1,$perpage=PAGE_SIZE){
    global $db;
    global $_SESSION;
    global $auth;
    global $html_page;
    global $smarty;

    $where = " AND F.PUBLISH='Y' ";
  
    $limitclause = "";//"LIMIT ".(($page-1)*$perpage).",".$perpage;
  
    // limit by category
    if ($cat > 0  && is_numeric($cat)) {
      $this->cat_id= $cat;
      $where .= " AND (F.CAT_ID='".$cat."')";
    }

    $where_groups = " AND F.GROUP_ID IS NULL";
    if ($auth->userOn()) {
      $user = new User($auth->getUserId());
      if ($user->getGroups() != '' && !$auth->isAdmin()) {
         $where_groups = " AND (F.GROUP_ID IS NULL OR F.GROUP_ID IN (".$user->group_str."))";
      } else if ($auth->isAdmin())
        $where_groups = "";
    }
 
    $sql_count = 'SELECT COUNT(F.FORUM_ID) ROWS
                   FROM forum_details FD, forum F
                   WHERE FD.FORUM_ID=F.FORUM_ID 
			'.$where.$where_groups; 
    $db->query($sql_count);
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }
  
    if ($auth->userOn()) {
      $sql = "SELECT distinct
              F.FORUM_ID, FD.FORUM_NAME, F.CAT_ID, FCD.CAT_NAME, count(T.TOPIC_ID) as TOPICS, sum(T.POSTS) POSTS, MAX( TT.MARK_TIME )< MAX(  T.LAST_POSTED ) AS TRACKER
            FROM forum_details FD, forum F
		      left join topic T ON T.FORUM_ID=F.FORUM_ID AND T.LANG_ID in (".$_SESSION['lang_id'].", 0)
                      left join topic_track TT on TT.TOPIC_ID=T.TOPIC_ID AND TT.FORUM_ID=F.FORUM_ID and TT.USER_ID=".$auth->getUserId().", 
	      forum_cats CF
                left join forum_cats_details FCD ON FCD.CAT_ID=CF.CAT_ID AND FCD.LANG_ID=".$_SESSION['lang_id']."
            WHERE CF.CAT_ID=F.CAT_ID 
		AND FD.FORUM_ID=F.FORUM_ID 
		AND FD.LANG_ID in (".$_SESSION['lang_id'].", 0)
              ".$where.$where_groups."
            GROUP BY F.FORUM_ID, FD.FORUM_NAME, F.CAT_ID, FCD.CAT_NAME, FD.TOPICS
            ORDER BY 
              CF.PRIORITY, CF.CAT_ID, F.FORUM_ID
            ".$limitclause;
    } else {
      $sql = "SELECT distinct
              F.FORUM_ID, FD.FORUM_NAME, F.CAT_ID, FCD.CAT_NAME, count(T.TOPIC_ID) as TOPICS, sum(T.POSTS) POSTS, 0 AS TRACKER
            FROM 
              forum F left join forum_details FD ON FD.FORUM_ID=F.FORUM_ID AND FD.LANG_ID in (".$_SESSION['lang_id'].", 0)
		      left join topic T ON T.FORUM_ID=F.FORUM_ID AND T.LANG_ID in (".$_SESSION['lang_id'].", 0),
	      forum_cats CF
                left join forum_cats_details FCD ON FCD.CAT_ID=CF.CAT_ID AND FCD.LANG_ID=".$_SESSION['lang_id']."
            WHERE CF.CAT_ID=F.CAT_ID
              ".$where.$where_groups."
            GROUP BY F.FORUM_ID, FD.FORUM_NAME, F.CAT_ID, FCD.CAT_NAME, FD.TOPICS
            ORDER BY 
              CF.PRIORITY, CF.CAT_ID, F.FORUM_ID
            ".$limitclause;
    }
//echo $sql;
//    $db->showquery = true;
    $db->query($sql);
    $cats = array();
    $forums = '';
    $c = 0;
    $prev_cat_id = -1;
    while ($row = $db->nextRow()) {      
      if ($prev_cat_id != $row['CAT_ID']) {
        if ($prev_cat_id > -1) {
          $cat_item['FORUMS'] = $forums;
          $cats[] = $cat_item;  
          $cat_item = $row; 
        }
        else {
          $cat_item = $row; 
        }
        $forums = array();
	$prev_cat_id = $row['CAT_ID'];
      }

      $forum_item = $row; 
      if ($row['TOPICS'] > 0 && ($row['TRACKER'] > 0 || $row['TRACKER'] == ''))
        $forum_item['TRACK'] = 1; 
      $forums[] = $forum_item;

      $c++;
    }
    $cat_item['FORUMS'] = $forums;
    $cats[] = $cat_item;  
    $db->free();
   
  //  echo $count;
    $this->rows = $count;
    $smarty->assign("cats", $cats);
      // no records?
  }

  function getPostsData($topic_id='',$page=1,$perpage=PAGE_SIZE, $forum_id=-1, $item_id=-1, $type=-1) {
    global $db;
    global $_SESSION;
    global $auth;
    global $html_page;
    global $langs;
    global $_GET;
    global $smarty;

    $data = '';

    $forumpermissions = new ForumPermission();
    $can_vote = $forumpermissions->canVoteComment();

    $moderator = false;
    if (isset($forum_id) && is_numeric($forum_id)) {
      $moderator = $auth->isForumModerator($forum_id);
    }
    if (isset($topic_id) && is_numeric($topic_id) && $topic_id > -1) {

      $where_groups = " AND F.GROUP_ID IS NULL";
      if ($auth->userOn()) {
         $user = new User($auth->getUserId());
         if ($user->getGroups() != '' && !$auth->isAdmin()) {
           $where_groups = " AND (F.GROUP_ID IS NULL OR F.GROUP_ID IN (".$user->group_str."))";
         } else if ($auth->isAdmin())
           $where_groups = "";
      }

      $sql_count = "SELECT COUNT(P.POST_ID) ROWS
                     FROM post P, forum F ,topic T
                     WHERE P.TOPIC_ID='".$topic_id."'
			 AND P.TOPIC_ID=T.TOPIC_ID
                         AND T.FORUM_ID=F.FORUM_ID
			 AND F.PUBLISH='Y' ".$where_groups; 
      $db->query($sql_count);
      while ($row = $db->nextRow()) {
        $count = $row['ROWS'];
      }

      // find page number
      $target_post_id = 0;
      if ($auth->userOn() && isset($_GET['tstmp'])) {
        $sql = "SELECT min(P.POST_ID) UNREAD_POST_ID, COUNT(P.POST_ID) POSTS
                     FROM post P
                     WHERE P.TOPIC_ID='".$topic_id."'
			 AND UNIX_TIMESTAMP(P.DATE_POSTED) > ".$_GET['tstmp']; 
        $db->query($sql);
	$row = $db->nextRow();
        if (!empty($row['UNREAD_POST_ID']) && $row['POSTS'] > 0) {
          $target_post_id = $row['UNREAD_POST_ID'];
          $page = ceil($row['POSTS'] / $perpage);
  	  $data['FORUM']['UNREAD_POST']['UNREAD_POST_ID'] = $target_post_id;
        }
      }

      $post_ids = '';
      $pre = '';
      $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;
      if ($count > 0) {
	$order= " ORDER BY P.POST_ID DESC ";
        if ($auth->userOn()) {
  	  $order = " ORDER BY P.PINNED DESC, P.POST_ID DESC ";
  	}
        $sql="SELECT P.POST_ID
		FROM post P WHERE P.TOPIC_ID=".$topic_id.
	     $order.$limitclause;
        $db->query($sql);
        while ($row = $db->nextRow()) {
          $post_ids .= $pre.$row['POST_ID'];
          $pre = ","; 
        }
      }

      $extra_fields = '';
      $where_extra = '';
      if ($type == 'MANAGER_LEAGUES') {
        $extra_fields = ', MSD.SEASON_TITLE as DESCR';
        $where_extra = " LEFT JOIN manager_leagues ML ON ML.TOPIC_ID=T.TOPIC_ID AND ML.TOPIC_ID>0 
  			  LEFT JOIN manager_seasons_details MSD ON MSD.SEASON_ID=ML.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id'];
      } else if ($type == 'RVS_MANAGER_LEAGUES') {
        $extra_fields = ', MSD.SEASON_TITLE as DESCR';
        $where_extra = " LEFT JOIN rvs_manager_leagues ML ON ML.TOPIC_ID=T.TOPIC_ID AND ML.TOPIC_ID>0 
  			  LEFT JOIN manager_seasons_details MSD ON MSD.SEASON_ID=ML.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id'];
      } else if ($type == 'SOLO_MANAGER_LEAGUES') {
        $extra_fields = ', MSD.SEASON_TITLE as DESCR';
        $where_extra = " LEFT JOIN solo_manager_leagues ML ON ML.TOPIC_ID=T.TOPIC_ID AND ML.TOPIC_ID>0 
  			  LEFT JOIN manager_seasons_details MSD ON MSD.SEASON_ID=ML.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id'];
      } else if ($type == 'MANAGER_TOURNAMENT') {
        $extra_fields = ', MSD.SEASON_TITLE as DESCR';
        $where_extra = " LEFT JOIN manager_tournament ML ON ML.TOPIC_ID=T.TOPIC_ID AND ML.TOPIC_ID>0 
  			  LEFT JOIN manager_seasons_details MSD ON MSD.SEASON_ID=ML.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id'];
      } else if ($type == 'CLUBS') { 
         $extra_fields = ', FGD.GROUP_NAME as TOPIC_TITLE';
         $where_extra = " LEFT JOIN forum_groups FG ON FG.TOPIC_ID=T.TOPIC_ID AND FG.TOPIC_ID>0 
  			  LEFT JOIN forum_groups_details FGD ON FGD.GROUP_ID=FG.GROUP_ID AND FGD.LANG_ID=".$_SESSION['lang_id'];
      } else if ($type == 'CLANS_PUBLIC') { 
         $extra_fields = ', FG.CLAN_NAME as TOPIC_TITLE';
         $where_extra = " LEFT JOIN clans FG ON FG.TOPIC_ID=T.TOPIC_ID AND FG.TOPIC_ID>0";
      } 

      $where_posts = "";
      if ($count > 0)
	$where_posts = " and P.POST_ID in (".$post_ids.")";
  
      $sql = "";
      $utc = $auth->getUserTimezoneName();
      if ($auth->userOn()) {
	$order = " ORDER BY P.PINNED DESC, P.POST_ID DESC ";
        if (isset($_SESSION['_user']['TOPIC_SORTING']) && $_SESSION['_user']['TOPIC_SORTING'] == 1) 
  	  $order = " ORDER BY P.PINNED DESC, P.POST_ID ASC ";

        $sql = "SELECT FCD.CAT_ID, FCD.CAT_NAME, F.FORUM_ID, FD.FORUM_NAME, T.TOPIC_NAME, T.TOPIC_DESCR, T.TOPIC_ID, P.POST_ID, P.TEXT,
		     U.USER_NAME, U.COMMENT_TRUST, U.CONTENT_TRUST, U.TOWN,
		     DATE_ADD(P.DATE_POSTED, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) as DATE_POSTED,
		     DATE_ADD(P.DATE_EDITED, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) as DATE_EDITED,
		     P.EDITED, P.REVIEWED, P.VOTED, P.VOTES, P.PINNED, F.GROUP_ID,
                     P.VISIBLE, P.USER_ID, PV.USER_ID as VOTER_ID, PV.VOTE, P.CCTL, C.CCTLD, CD.COUNTRY_NAME ".$extra_fields."
                FROM forum_details FD, forum F, topic T 
			left join post P ON P.TOPIC_ID=T.TOPIC_ID ".$where_posts."
			left join users U ON P.USER_ID=U.USER_ID
			".$where_extra."
		     	left join post_votes PV ON PV.POST_ID=P.POST_ID AND PV.USER_ID=".$auth->getUserId()."
			left join countries C ON U.COUNTRY = C.ID
			LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id'].",
	         forum_cats CF
                   left join forum_cats_details FCD ON FCD.CAT_ID=CF.CAT_ID AND FCD.LANG_ID=".$_SESSION['lang_id']."
                WHERE
                    F.PUBLISH='Y'
		    AND CF.CAT_ID=F.CAT_ID
		    AND F.FORUM_ID=FD.FORUM_ID AND FD.LANG_ID IN (".$_SESSION['lang_id'].",0) 
		    AND T.FORUM_ID=F.FORUM_ID ".$where_groups."
		    AND T.PUBLISH ='Y' AND T.TOPIC_ID='".$topic_id."'
	             ".$order;
      } else {
        $sql = "SELECT FCD.CAT_ID, FCD.CAT_NAME, F.FORUM_ID, FD.FORUM_NAME, T.TOPIC_NAME, T.TOPIC_DESCR, T.TOPIC_ID, P.POST_ID, P.TEXT,
		     U.USER_NAME, U.COMMENT_TRUST, U.CONTENT_TRUST, P.DATE_POSTED, P.DATE_EDITED, P.EDITED, 
		     P.REVIEWED, P.VOTED, P.VOTES, P.PINNED, F.GROUP_ID,
                     P.VISIBLE, P.USER_ID, P.CCTL, C.CCTLD, CD.COUNTRY_NAME, U.TOWN ".$extra_fields."
              FROM forum_details FD, forum F, countries C
			LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
                 , topic T left join post P ON P.TOPIC_ID=T.TOPIC_ID ".$where_posts."
  			   ".$where_extra."
			   left join users U ON P.USER_ID=U.USER_ID,
	         forum_cats CF
                    left join forum_cats_details FCD ON FCD.CAT_ID=CF.CAT_ID AND FCD.LANG_ID=".$_SESSION['lang_id']."
              WHERE
                F.PUBLISH='Y'
		    AND CF.CAT_ID=F.CAT_ID
		    AND F.FORUM_ID=FD.FORUM_ID AND FD.LANG_ID IN (".$_SESSION['lang_id'].",0) 
		    AND T.FORUM_ID=F.FORUM_ID ".$where_groups."
		    AND T.PUBLISH ='Y' AND T.TOPIC_ID='".$topic_id."'
   	            AND U.COUNTRY = C.ID
	            ORDER BY P.POST_ID DESC ";
      } 
//echo $sql;
      $db->query($sql);
      $posts = array();
      $c=0;      
      while ($row = $db->nextRow()) {
	$data['FORUM']['FORUM_ID'] = $row['FORUM_ID'];
        $data['FORUM']['FORUM_NAME'] = $row['FORUM_NAME'];
        $data['FORUM']['CAT_ID'] = $row['CAT_ID'];
        $data['FORUM']['CAT_NAME'] = $row['CAT_NAME'];
        $data['FORUM']['TOPIC_NAME'] = $row['TOPIC_NAME'];
        $data['FORUM']['TOPIC_ID'] = $row['TOPIC_ID'];
	$data['FORUM']['TOPIC_TITLE'] = isset($row['TOPIC_TITLE']) ? $row['TOPIC_TITLE'] : '';
        $data['FORUM']['TOPIC']['TOPIC_ID'] = $row['TOPIC_ID'];
        $data['FORUM']['TOPIC']['TOPIC_NAME'] = $row['TOPIC_NAME'];
        $data['FORUM']['TOPIC_DESCR'] = $row['TOPIC_DESCR'];
        if (isset($row['DESCR']))
          $data['FORUM']['DESCR'] = $row['DESCR'];

          if ($count > 0) {
            $post = $row;
            $post['UTC'] = $utc;
	    if (!empty($row['CCTLD'])) {
              $post['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
              $post['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
	    }
	    if (!empty($row['TOWN'])) {
              $post['CITY']['TOWN'] = $row['TOWN'];
            }

            if ($row['EDITED'] != 0) {
	      $post['EDITED_POST'] = $row;
              $post['EDITED_POST']['UTC'] = $utc;
            }
            if ($row['VISIBLE'] == 'Y' || $row['GROUP_ID'] > 0) 
              $post['VISIBLE_TEXT'] = $row['TEXT'];
  	    else {
              if ($auth->userOn()) {
                if ($row['USER_ID'] == $auth->getUserId()) {
                  $post['VISIBLE_TEXT'] = $row['TEXT'];
                }
              } 
              if ($row['REVIEWED'] == 'Y') 
                $post['INVISIBLE_POST_REVIEWED'] = 1;
              else
                $post['INVISIBLE_POST'] = 1;
            }

            if ($auth->userOn()) {
	      $post['QUOTE'] = 1;

              if ($row['USER_ID'] == $auth->getUserId() && $row['VOTED'] == 'N') {
                $post['EDIT']['POST_ID'] = $row['POST_ID'];
                $post['EDIT']['TOPIC_ID'] = $row['TOPIC_ID'];
              }

	      if ($forumpermissions->canDeletePost($row['POST_ID'], $row['USER_ID'], $row['VOTED'])) {
                $post['DELETE'] = 1;
              }

              if ($forumpermissions->canPinPost($row['POST_ID'], $moderator)) {
                if ($row['PINNED'] == 0) {
                   $post['PIN']['TOPIC_ID'] = $row['TOPIC_ID'];
                   $post['PIN']['PORT_ID'] = $row['POST_ID'];
                 } else {
                   $post['UNPIN']['TOPIC_ID'] = $row['TOPIC_ID'];
                   $post['UNPIN']['POST_ID'] = $row['POST_ID'];
                   $post['POST_PINNED']['POST_ID'] = $row['POST_ID'];
                }
              }


              if ($can_vote && $row['VOTER_ID'] != $auth->getUserId()
                  && $row['USER_ID'] != $auth->getUserId()
                  && $row['VISIBLE'] == 'Y'
		  && $row['VOTED'] == 'N'
                  && $forumpermissions->canCommentBeVoted($row['CCTL'])) {
  	        $post['VOTING'] = 1;
              }
              else if ($row['VOTED'] == 'Y') {
		if ($row['VOTES'] > 0) 
    	          $post['VOTED_PLUS'] = 1;
                else {
    	          $post['VOTED_MINUS'] = 1;
    	          $post['HIDDEN_POST']['POST_ID'] = $row['POST_ID'];
		  $post['HIDDEN_POST']['VISIBLE'] = 1;
		  $post['INVISIBLE_DIV']['X'] = 1;
                } 
              }
              else if ($row['VOTE'] > 0) {
  	        $post['THUMB_UP']['POST_ID'] = $row['POST_ID'];
              } 
              else if ($row['VOTE'] < 0) {
  	        $post['THUMB_DOWN']['POST_ID'] = $row['POST_ID'];
              }

              // mark unread
              if ($target_post_id > 0 && $row['POST_ID'] == $target_post_id) {
		$post['UNREAD_POST'] = 1;
              }
            } 
  	    $posts[] = $post;
          }

         $html_page->page_title = $row['FORUM_NAME']." | ".$row['TOPIC_NAME'];
         $c++;        
      }
      if ($c == 0) {
        if (isset($forum) && $forum > -1)
          $data['FORUM']['FORUM_ID'] = $forum;
      } 
      $data['_ROWS'] = $count;
    }
    else {
      $sql = "SELECT F.FORUM_ID, FD.FORUM_NAME
              FROM 
                 forum_details FD, forum F
              WHERE
                F.PUBLISH='Y' 
                    AND F.FORUM_ID=".$forum_id."
		    AND F.FORUM_ID=FD.FORUM_ID AND FD.LANG_ID IN (".$_SESSION['lang_id'].",0) ";
      $db->query($sql);

      $c=0;      
      if ($row = $db->nextRow()) {
        $data['FORUM'] = $row;
      }
      $data['FORUM']['TOPIC_ID'] = $topic_id;
      if ($item_id > -1)
        $data['FORUM']['ITEM_ID'] = $item_id; 
      $data['_ROWS'] = 0;
    }

    unset($_POST['forum_id']);
    unset($_POST['topic_id']);
    $pagingbox = new PagingBox($langs, $_SESSION['_lang']);
    $data['FORUM']['PAGING'] = $pagingbox->getPagingBox($data['_ROWS'], $page);

    if (!isset($_SESSION['_user']['EDITOR_WINDOW']))      
      $_SESSION['_user']['EDITOR_WINDOW'] = 1;  
    $smarty->assign("editor_window_position", $_SESSION['_user']['EDITOR_WINDOW']);
    if (isset($posts))
      $smarty->assign("posts", $posts);
    return $data;
  }


  function addTopic($forum_id, $item_id ='') {
    global $db;
    global $_POST;
    global $langs;
    global $_SESSION;
    global $forums;

    $key = array_search($forum_id, $forums); 
    if (($key == 'NEWS' || $key == 'BLOGS') && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT TITLE, TOPIC_ID FROM news_details ND WHERE ND.news_id='".$item_id."' AND ND.LANG_ID=".$_SESSION['lang_id'];
      $db->query($sql);
      $row = $db->nextRow();      
      $_POST['topic_name'] = mysql_real_escape_string($row['TITLE']);
      if ($row['TOPIC_ID'] > 0)
        $_POST['topic_id'] = $row['TOPIC_ID'];
    } else if ($key == 'MANAGER_LEAGUES' && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT TITLE FROM manager_leagues ND WHERE ND.league_id='".$item_id."'";
      $db->query($sql);
      $row = $db->nextRow();
      $_POST['topic_name'] = mysql_real_escape_string($row['TITLE']);
    } else if ($key == 'RVS_MANAGER_LEAGUES' && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT TITLE FROM rvs_manager_leagues ND WHERE ND.league_id='".$item_id."'";
      $db->query($sql);
      $row = $db->nextRow();
      $_POST['topic_name'] = mysql_real_escape_string($row['TITLE']);
    } else if ($key == 'SOLO_MANAGER_LEAGUES' && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT TITLE FROM solo_manager_leagues ND WHERE ND.league_id='".$item_id."'";
      $db->query($sql);
      $row = $db->nextRow();
      $_POST['topic_name'] = mysql_real_escape_string($row['TITLE']);
    } else if ($key == 'MANAGER_TOURNAMENT' && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT TITLE FROM manager_tournament ND WHERE ND.mt_id='".$item_id."'";
      $db->query($sql);
      $row = $db->nextRow();
      $_POST['topic_name'] = mysql_real_escape_string($row['TITLE']);
    } else if ($key == 'MANAGER_BATTLES' && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT BATTLE_ID FROM manager_battles ND WHERE ND.battle_id='".$item_id."'";
      $db->query($sql);
      $row = $db->nextRow();
      $_POST['topic_name'] = $row['BATTLE_ID'];
    } else if ($key == 'SURVEYS' && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT TITLE FROM survey_details ND WHERE ND.id='".$item_id."' AND ND.LANG_ID=".$_SESSION['lang_id'];
      $db->query($sql);
      $row = $db->nextRow();
      $_POST['topic_name'] = mysql_real_escape_string($row['TITLE']);
    } else if ($key == 'VIDEO' && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT TITLE FROM video_details ND WHERE ND.video_id='".$item_id."' AND ND.LANG_ID=".$_SESSION['lang_id'];
      $db->query($sql);
      $row = $db->nextRow();
      $_POST['topic_name'] = mysql_real_escape_string($row['TITLE']);
    } else if ($key == 'WAGER_LEAGUES' && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT TITLE FROM wager_leagues ND WHERE ND.league_id='".$item_id."'";
      $db->query($sql);
      $row = $db->nextRow();
      $_POST['topic_name'] = mysql_real_escape_string($row['TITLE']);
    } else if ($key == 'ARRANGER_LEAGUES' && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT TITLE FROM bracket_leagues ND WHERE ND.league_id='".$item_id."'";
      $db->query($sql);
      $row = $db->nextRow();
      $_POST['topic_name'] = mysql_real_escape_string($row['TITLE']);
    } else if ($key == 'CLUBS' && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT GROUP_NAME as TITLE FROM forum_groups_details ND WHERE ND.GROUP_id='".$item_id."' AND ND.LANG_ID=".$_SESSION['lang_id'];
      $db->query($sql);
      $row = $db->nextRow();
      $_POST['topic_name'] = mysql_real_escape_string($row['TITLE']);
    } else if ($key == 'CLUBS_EVENTS' && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT TITLE FROM forum_groups_events_details ND WHERE ND.EVENT_id='".$item_id."' AND ND.LANG_ID=".$_SESSION['lang_id'];
      $db->query($sql);
      $row = $db->nextRow();
      $_POST['topic_name'] = mysql_real_escape_string($row['TITLE']);
    } else if ($key == 'CLANS_PUBLIC' && !empty($item_id) && is_numeric($item_id)) {
      $sql = "SELECT CLAN_NAME as TITLE FROM clans ND WHERE ND.CLAN_id='".$item_id."'";
      $db->query($sql);
      $row = $db->nextRow();
      $_POST['topic_name'] = mysql_real_escape_string($row['TITLE']);
    }


    if (!empty($forum_id)) {
      $_POST['forum_id'] = $forum_id;  
    }

    $error=FALSE;
    if (isset($_POST['topic_descr']))        
      $s_fields=array('topic_name', 'topic_descr');
    else 
      $s_fields=array('topic_name');
    $i_fields=array('forum_id', 'lang_id');
    $d_fields='';
    $c_fields='';
    $r_fields=array('topic_name');
    if(!requiredFieldsOk($r_fields, $_POST)){
	$error=TRUE;
	$data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
    };
    if(!$error){
	// get save data
	$sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$_POST);
        $sdata['PUBLISH'] = "'Y'";
	// proceed to database updates
//$db->showquery=true;
	if ($key == 'MANAGER_LEAGUES' || $key == 'RVS_MANAGER_LEAGUES' || $key == 'WAGER_LEAGUES' || $key == 'ARRANGER_LEAGUES' || $key == 'CLUBS'  || $key == 'CLUBS_EVENTS' || $key == 'CLANS_PUBLIC' || $key == 'MANAGER_BATTLES' || $key == 'MANAGER_TOURNAMENT' || $key == 'SOLO_MANAGER_LEAGUES')
          $sdata['lang_id'] = 0;

	if(!empty($_POST['topic_id']) && $_POST['topic_id'] != -1){
		// UPDATE 
		$db->update('topic', $sdata, "TOPIC_ID=".$_POST['topic_id']);
	}else{
		// INSERT
                $sdata['USER_ID'] = $_SESSION['_user']['USER_ID'];
		$sdata['DATE_POSTED'] = 'SYSDATE()';
		$db->insert('topic',$sdata);
		$topic_id = $db->id(); 
 	        
                unset($sdata);
		$sdata['TOPICS'] ='TOPICS+1';
		$db->update('forum_details',$sdata, "FORUM_ID='".$_POST['forum_id']."' AND LANG_ID=".$_SESSION['lang_id']);

		if (($key == 'NEWS' || $key == 'BLOGS') && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('news_details', $sdata, "news_id='".$item_id."' AND LANG_ID='".$_SESSION['lang_id']."'");
                } else if ($key == 'MANAGER_LEAGUES' && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('manager_leagues', $sdata, "league_id='".$item_id."'");
                } else if ($key == 'RVS_MANAGER_LEAGUES' && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('rvs_manager_leagues', $sdata, "league_id='".$item_id."'");
                } else if ($key == 'SOLO_MANAGER_LEAGUES' && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('solo_manager_leagues', $sdata, "league_id='".$item_id."'");
                } else if ($key == 'MANAGER_TOURNAMENT' && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('manager_tournament', $sdata, "mt_id='".$item_id."'");
                } else if ($key == 'MANAGER_BATTLES' && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('manager_battles', $sdata, "battle_id='".$item_id."'");
                } else if ($key == 'SURVEYS' && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('survey_details', $sdata, "id='".$item_id."' AND LANG_ID='".$_SESSION['lang_id']."'");
                } else if ($key == 'VIDEO' && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('video_details', $sdata, "video_id='".$item_id."' AND LANG_ID='".$_SESSION['lang_id']."'");
                } else if ($key == 'WAGER_LEAGUES' && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('wager_leagues', $sdata, "league_id='".$item_id."'");
                } else if ($key == 'ARRANGER_LEAGUES' && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('bracket_leagues', $sdata, "league_id='".$item_id."'");
                } else if ($key == 'CLUBS' && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('forum_groups', $sdata, "group_id='".$item_id."'");
                } else if ($key == 'CLUBS_EVENTS' && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('forum_groups_events', $sdata, "event_id='".$item_id."'");
                } else if ($key == 'CLANS_PUBLIC' && isset($item_id) && is_numeric($item_id)) {
		  unset($sdata);
		  $sdata['TOPIC_ID'] = $topic_id;
		  $db->update('clans', $sdata, "clan_id='".$item_id."'");
                } 

		return  $topic_id;
	};
		// redirect to news page
    } else return -1;
  }

  function addPost($forum_id, $topic_id ='', $item_id = '') {
    global $db;
    global $_POST;
    global $langs;
    global $_SESSION;
    global $auth;
    global $conf_site_url;

    if (empty($topic_id) || $topic_id == -1) {
      $topic_id = $this->addTopic($forum_id, $item_id);
      if ($topic_id == -1)
        return -1;
    }    
    $error=FALSE;
//    if ($_POST['text'] == "" && isset($_POST['simple_text']))
    $_POST['text'] .= $_POST['simple_text'];
    $s_fields=array('text');
    $i_fields=array('lang_id');
    $d_fields='';
    $c_fields='';
    $r_fields=array('text');
    if(!requiredFieldsOk($r_fields, $_POST)){
	$error=TRUE;
	$data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
    };
    if(!$error){
	// get save data
	$sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$_POST);
	// proceed to database updates
//$db->showquery=true;
        $sdata['TOPIC_ID']=$topic_id;
	$trust = new Trust();
        $send_mail = false;
        $actkey = gen_rand_string(0, 10);
	if(!empty($_POST['post_id'])){
		// UPDATE 
                $post_id = $_POST['post_id'];
		$sdata['DATE_EDITED'] = 'SYSDATE()';
		$sdata['EDITED'] = 'EDITED+1';
		$db->update('post', $sdata, "POST_ID='".$_POST['post_id']."'");
		$tdata['LAST_POSTED'] = 'NOW()';
		$sdata['LAST_POSTER_ID'] = $auth->getUserId();
		$db->update('topic',$tdata, "TOPIC_ID='".$topic_id."' AND LANG_ID=".$_SESSION['lang_id']);
	}else{
		// INSERT
                $sdata['USER_ID'] = $auth->getUserId();
		$sdata['DATE_POSTED'] = 'SYSDATE()';
                $cctl = $trust->getCommentTrustLevel($_SESSION['_user']['COMMENT_TRUST']);
                if ($cctl == 0 || $cctl == 1) {
		  $sdata['ACTKEY'] = "'".$actkey."'";
                  $sdata['VISIBLE'] = "'N'";
                  $sdata['REVIEWED'] = "'N'";
		  $send_mail = true;
                  // send email to admin
                } else {
                  $sdata['REVIEWED'] = "'N'";
                  $sdata['VISIBLE'] = "'Y'";
		}
	        $sdata['CCTL'] = $cctl;
		$db->insert('post',$sdata);
		$post_id = $db->id();

                unset($sdata);
		$sdata['POSTS'] ='POSTS+1';
		$sdata['LAST_POSTED'] = 'NOW()';
		$sdata['LAST_POSTER_ID'] = $auth->getUserId();
		$db->update('topic',$sdata, "TOPIC_ID='".$topic_id."'");
	};

        if ($send_mail &&
            ($trust->getCommentTrustLevel($_SESSION['_user']['COMMENT_TRUST']) == 0 ||
	     $trust->getCommentTrustLevel($_SESSION['_user']['COMMENT_TRUST']) == 1)) {
          $edata['USER_NAME'] = $_SESSION['_user']['USER_NAME'];
	  $edata['FORUM_NAME'] = $this->forum->getForumName($forum_id, $topic_id);
	  $edata['TOPIC_NAME'] = $this->forum->getTopicName($topic_id);
          $edata['TEXT'] = $_POST['text'];
	  $edata['URL_APPROVE'] = $conf_site_url."user_activation.php?mode=post_approve&post_id=".$post_id."&actkey=".$actkey;
	  $edata['URL_ALLOW'] = $conf_site_url."user_activation.php?mode=post_allow&post_id=".$post_id."&actkey=".$actkey;
	  $edata['URL_DISAPPROVE'] = $conf_site_url."user_activation.php?mode=post_disapprove&post_id=".$post_id."&actkey=".$actkey;

	  $email = new Email($langs, $_SESSION['_lang']);
	  $email->getEmailFromTemplate ('email_comment_approve', $edata) ;
          $subject = $langs['LANG_EMAIL_COMMENT_APPROVE_LINE_1'];
	  $email->sendAdmin($subject);
        }
        else {
          $notification = new Notification();
	  $notification->populateTopicEmails($topic_id, $auth->getUserId());
        }
	// redirect to news page
    }
    return $topic_id;
  }


  function editTopic($topic_id) {
    global $db;
    global $_POST;

    $error=FALSE;
    $s_fields=array('topic_name', 'topic_descr');
    $i_fields='';
    $d_fields='';
    $c_fields='';
    $r_fields=array('topic_name');
    if(!requiredFieldsOk($r_fields, $_POST)){
	$error=TRUE;
	$data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
    };
    if(!$error){
	// get save data
	$sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$_POST);
	// proceed to database updates
//$db->showquery=true;

	// UPDATE 
	$db->update('topic', $sdata, "TOPIC_ID=".$topic_id);
		// redirect to news page
    };
  }

  function getTopicBox() {
    global $smarty;
    global $auth;
    if ($auth->userOn() && isset($this->topic_id) && $this->topic_id > 0) {
      $topic['SETTINGS'] = $this->getTopicSettings();
      $smarty->assign("topic", $topic);
      $start = getmicrotime();
      $output = $smarty->fetch('smarty_tpl/bar_topic.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/bar_topic.smarty<br>'.($stop-$start);
      return $output;

    }
    return '';

  }

  function getForumBox() {
    global $smarty;
    global $auth;
    global $_SESSION;
    if ($auth->userOn() && 
         (((isset($this->forum_id) && $this->forum_id > 0) 
	  || (isset($this->cat_id) && $this->cat_id > 0))
	  || (!isset($topic_id)))) {
      $forum['FORUM_ID'] = $this->forum_id;
      $forum['CAT_ID'] = $this->cat_id;
      $smarty->assign("forum", $forum);
      $start = getmicrotime();
      $output = $smarty->fetch('smarty_tpl/bar_forum.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/bar_forum.smarty<br>'.($stop-$start);
      return $output;
    }
    return '';
  }

  function getTopicSettings() {
    global $db;
    global $smarty;
    global $auth;

    if ($auth->userOn() && isset($this->topic_id) && $this->topic_id > 0) {
      $sql = "SELECT * FROM topic_subscribe WHERE USER_ID=".$auth->getUserId()."
							AND TOPIC_ID=".$this->topic_id;
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $smarty->assign("unsubscribe", $this->topic_id);
      } else {
        $smarty->assign("subscribe", $this->topic_id);
      }
      $start = getmicrotime();
      $output = $smarty->fetch('smarty_tpl/bar_topic_settings.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/bar_topic_settings.smarty<br>'.($stop-$start);
      return $output;

    }
    return '';
  }


}   
?>