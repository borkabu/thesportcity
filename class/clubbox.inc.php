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

class ClubBox extends Box{

  function ClubBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getClubs ($page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    global $_SESSION;
    
    // content
    $clubs = $this->getClubsData($page, $perpage);
    $this->rows = count($clubs);	
//print_r($this->data['NEWS']);

    $smarty->assign("clubs", $clubs);
    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/clubs.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/clubs.smarty'.($stop-$start);

    return $content;
  } 

  function getClubsEvents ($page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    global $_SESSION;

    $this->getClubsEventsData(false, $page, $perpage);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/clubs_events.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/clubs_events.smarty<br>'.($stop-$start);
    return $output;
  } 

  function getClubsEventsBox ($page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    global $_SESSION;
    
    // content
    $this->getClubsEventsData(true, $page, $perpage);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_clubs_events.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_clubs_events.smarty<br>'.($stop-$start);
    return $output;
  } 

  function getClubItem ($club_id) {
    global $smarty;
    global $db;
    global $_SESSION;
    
    // content
    $data = $this->getClubItemData($club_id);

    if ($data != '') {
      $smarty->assign("data", $data);
      $start = getmicrotime();
      $output = $smarty->fetch('smarty_tpl/club_item.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/club_item.smarty<br>'.($stop-$start);
      return $output;
    }
    else {
      return '';
    }
  } 

  function getClubEventItem ($event_id) {
    global $smarty;
    
    // content
    $data = $this->getClubEventItemData($event_id);
    if ($data != '') {
      $smarty->assign("data", $data);
      $start = getmicrotime();
      $output = $smarty->fetch('smarty_tpl/clubs_events_item.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/clubs_events_item.smarty<br>'.($stop-$start);
      return $output;
    }
    else {
      return '';
    }   
  } 

  function getClubItemData($club_id) {
    global $db;
    global $_SESSION;
    global $auth;
    global $langs;
    global $html_page;
    global $PRESET_VARS;

    $data='';

    $forumpermissions = new ForumPermission();
    $can_vote = $forumpermissions->canVoteContent();
    if (is_numeric($club_id)) {
       $group = new Group($club_id);
       $sql= "SELECT ML.GROUP_ID, MLM.USER_ID, MLM.LEVEL, MS.GROUP_NAME, 
		U.USER_NAME, C.CCTLD, CD.COUNTRY_NAME, MS.DESCR
            FROM forum_groups ML, forum_groups_members MLM, 
	       forum_groups_details MS, users U, countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
           WHERE ML.GROUP_ID=".$club_id."
	        AND MLM.GROUP_ID=ML.GROUP_ID
	        AND MS.GROUP_ID=MLM.GROUP_ID
		AND MS.LANG_ID=".$_SESSION['lang_id']."
                AND MLM.USER_ID=U.USER_ID
   	        AND U.COUNTRY = C.ID
           ORDER BY U.USER_NAME ASC"; 
//echo $sql;
      $db->query($sql);     
      $c=1;    
      $prezident = false;      
      while ($row = $db->nextRow()) {
        $member = $row;
        $member['LOCAL_PLACE'] = $c;

        if (!empty($row['CCTLD'])) {
          $member['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
          $member['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
        }
        if ($row['LEVEL'] == 3 || $row['LEVEL'] == 1) {
          $data['CLUB']['GROUP_NAME'] = $row['GROUP_NAME'];
          if ($row['LEVEL'] == 3)
            $data['CLUB']['PREZIDENT'] = $row['USER_NAME'];
          $data['CLUB']['DESCR'] = $row['DESCR'];
	  $data['CLUB']['CLUB_ID'] = $club_id;
          if ($auth->userOn() && $row['USER_ID'] == $auth->getUserId()) {
            $prezident = true;
	    $data['CLUB']['MANAGEMENT'] = $row;
  	    $data['CLUB']['MANAGEMENT']['CLUB_ID'] = $club_id;
	    $data['CLUB']['MANAGEMENT']['LANG_ID'] = $_SESSION['lang_id'];
	    $data['CLUB']['ADD_CLUB_EVENT'] = $row;
	    $data['CLUB']['ADD_CLUB_EVENT']['CLUB_ID'] = $club_id;
	    $PRESET_VARS['descr'] = $row['DESCR'];
          }  
        }
        else if ($row['LEVEL'] == 2) {
          $member['CURRENT_MEMBERS'] = 1;
        }
        if (!empty($row['RULES']))  
          $data['CLUB']['RULES'] = $row['RULES'];
  
        $data['CLUB']['MEMBERS'][] = $member;
        $html_page->page_title = $row['GROUP_NAME'];
      }
      $db->free();

      if ($auth->userOn()) {
        if ($group->hasMember($auth->getUserId())) {
          $data['CLUB']['MEMBERSHIP']['MEMBER'] = $row;
        } else {
          $data['CLUB']['MEMBERSHIP']['NO_MEMBER'] = $row;
        }
      }

      $languages = '';
      $sql = "SELECT * FROM languages ORDER BY ID";
      $db->query($sql);
      while ($row = $db->nextRow()) {
        $languages[$row['ID']] = $row;
      }

      // get events
      $sql="SELECT FGED.TITLE, FGE.EVENT_ID, FGE.GROUP_ID, T.POSTS, FGE.FINISHED,
		FGE.PARTICIPANTS, COUNT(FGEM.USER_ID) EVENT_MEMBERS, GROUP_CONCAT(FGED2.LANG_ID) as LANGUAGES
		 FROM forum_groups_events FGE
		 	 LEFT JOIN topic T ON FGE.TOPIC_ID=T.TOPIC_ID
		 	 LEFT JOIN forum_groups_events_members FGEM ON FGEM.EVENT_ID=FGE.EVENT_ID
		         LEFT JOIN forum_groups_events_details FGED ON FGED.EVENT_ID=FGE.EVENT_ID AND FGED.LANG_ID=".$_SESSION['lang_id']."
                         LEFT JOIN forum_groups_events_details FGED2 ON FGED2.EVENT_ID=FGE.EVENT_ID
		WHERE FGE.GROUP_ID=".$club_id."
		GROUP BY FGE.EVENT_ID
		ORDER BY FGED.TITLE ASC";
//echo $sql;
      $db->query($sql);     
      $c=0;
      $p=0;    
      while ($row = $db->nextRow()) {
	if ($row['FINISHED'] == 'N') {
          $event = $row;
          if (empty($row['POSTS']))
            $event['POSTS'] = 0; 

          if ($prezident) {
             $used_langs = explode(",", $row['LANGUAGES']);
             foreach ($languages as $language) {
               if (in_array($language['ID'], $used_langs)) {
                 $event['LANGS'][$language['ID']]['USED'] = $language;
                 $event['LANGS'][$language['ID']]['USED']['EVENT_ID'] = $row['EVENT_ID'];
                 $event['LANGS'][$language['ID']]['USED']['CLUB_ID'] = $row['GROUP_ID'];
               } else {
                 $event['LANGS'][$language['ID']]['NOTUSED'] = $language;
                 $event['LANGS'][$language['ID']]['NOTUSED']['EVENT_ID'] = $row['EVENT_ID'];
                 $event['LANGS'][$language['ID']]['NOTUSED']['CLUB_ID'] = $row['GROUP_ID'];
               }
             }
          }
  	  $data['CLUB']['CURRENT_EVENTS'][] = $event;
        }
	else {
  	  $data['CLUB']['PAST_EVENTS'][] = $row;
        }
      }
      $data['LANG'] = $_SESSION['_lang']; 
    }      

//print_r($data);
    return $data;
  }

  function getClubEventItemData($event_id) {
    global $db;
    global $_SESSION;
    global $PRESET_VARS;
    global $auth;
    global $langs;
    global $html_page;

    $data='';
    $moderator = false;
    $forumpermissions = new ForumPermission();
    $can_vote = $forumpermissions->canVoteContent();
    if (is_numeric($event_id)) {
      $sql = "SELECT count(user_ID) USERS FROM forum_groups_events_members WHERE event_id=".$event_id;
      $db->query($sql);
      $row2 = $db->nextRow();

      $sql = "SELECT FGED.TITLE, FGE.EVENT_ID, ML.GROUP_ID, MS.GROUP_NAME, 
			FGED.DESCR, FGED.RESULTS, FGE.PARTICIPANTS, FGE.FINISHED,
			FGE.RECRUITMENT_ACTIVE, FGE.ENTRY_FEE
            FROM forum_groups ML, forum_groups_details MS, forum_groups_events_details FGED, forum_groups_events FGE
           WHERE FGE.EVENT_ID=".$event_id."
	        AND ML.GROUP_ID=FGE.GROUP_ID
	        AND MS.GROUP_ID=ML.GROUP_ID
		AND FGE.EVENT_ID=FGED.EVENT_ID			
		AND FGED.LANG_ID=".$_SESSION['lang_id']."
		AND MS.LANG_ID=".$_SESSION['lang_id']; 

//echo $sql;
      $db->query($sql);     
      $club_id = -1;
      if ($row = $db->nextRow()) {
        $data['EVENT'] = $row;
	$club_id = $row['GROUP_ID'];
        $html_page->page_title = $row['GROUP_NAME']." | ".$row['TITLE'];

        if ($auth->userOn()) {
          // get moderator
          $group = new Group($club_id);
          if ($group->isGroupModerator($auth->getUserId())) {
  	    $data['EVENT']['MANAGEMENT'] = $row;
  	    $data['EVENT']['MANAGEMENT']['CLUB_ID'] = $club_id;
  	    $data['EVENT']['MANAGEMENT']['EVENT_ID'] = $event_id;
	    $moderator = true;
          }
          if ($row['FINISHED'] == 'N') {
           if ($row['RECRUITMENT_ACTIVE'] == 'N' 
		|| ($row2['USERS'] >= $row['PARTICIPANTS'] && $row['PARTICIPANTS'] > 0)) {
             $data['EVENT']['PARTICIPATION']['REGISTRATION_CLOSED'] = 1;
             if ($row['RECRUITMENT_ACTIVE'] == 'Y') {
               unset($xdata);
               $xdata['RECRUITMENT_ACTIVE'] = "'N'";
  	       $db->update("forum_groups_events", $xdata, "event_id=".$event_id);
             }
           }
	   if ($moderator || $group->hasMember($auth->getUserId())) {
             if ($group->hasEventParticipant($auth->getUserId(), $event_id)) {
               $data['EVENT']['PARTICIPATION']['PARTICIPATING'] = 1;
             } else if ($row['RECRUITMENT_ACTIVE'] == 'Y') {
                if ($row['ENTRY_FEE'] > 0 ) {
                  if ($_SESSION['_user']['CREDIT'] >= $row['ENTRY_FEE']) {
		    $data['EVENT']['PARTICIPATION']['NOT_PARTICIPATING']['ENOUGH_CREDITS'] = 1;
                    $data['EVENT']['PARTICIPATION']['NOT_PARTICIPATING']['BUTTONS']['CLUB_ID'] = $club_id;
                    $data['EVENT']['PARTICIPATION']['NOT_PARTICIPATING']['BUTTONS']['EVENT_ID'] = $event_id;
                  } else {
		    $data['EVENT']['PARTICIPATION']['NOT_PARTICIPATING']['NOT_ENOUGH_CREDITS'] = 1;
                  }
                } else {
                    $data['EVENT']['PARTICIPATION']['NOT_PARTICIPATING']['BUTTONS']['CLUB_ID'] = $club_id;
                    $data['EVENT']['PARTICIPATION']['NOT_PARTICIPATING']['BUTTONS']['EVENT_ID'] = $event_id;
                }
             } 
           } else 
             $data['EVENT']['PARTICIPATION']['NOT_CLUB_MEMBER'] = 1;
          }             
          else $data['EVENT']['PARTICIPATION']['FINISHED'] = 1;
        }
	$PRESET_VARS['title'] = $row['TITLE'];
	$PRESET_VARS['descr'] = $row['DESCR'];
	$PRESET_VARS['results'] = str_replace( "\r\n", "", $row['RESULTS']);
	$PRESET_VARS['finished'] = $row['FINISHED'];
	$PRESET_VARS['recruitment_active'] = $row['RECRUITMENT_ACTIVE'];
      }

      $sql = "SELECT FGED.TITLE, FGE.EVENT_ID, ML.GROUP_ID, FGEM.USER_ID, MS.GROUP_NAME, U.USER_NAME, C.CCTLD, CD.COUNTRY_NAME, MS.DESCR
            FROM forum_groups ML, forum_groups_details MS, forum_groups_events FGE, forum_groups_events_details FGED, 
		forum_groups_events_members FGEM, users U, countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
           WHERE FGE.EVENT_ID=".$event_id."
	        AND ML.GROUP_ID=FGE.GROUP_ID
	        AND MS.GROUP_ID=ML.GROUP_ID
		AND MS.LANG_ID=".$_SESSION['lang_id']."
   	        AND U.COUNTRY = C.ID
		AND FGE.EVENT_ID=FGEM.EVENT_ID
		AND FGEM.USER_ID=U.USER_ID
		AND FGE.EVENT_ID=FGED.EVENT_ID			
		AND FGED.LANG_ID=".$_SESSION['lang_id']."
           ORDER BY U.USER_NAME ASC"; 

//echo $sql;
      $db->query($sql);     
      $c=0;    
      while ($row = $db->nextRow()) {
        $participant = $row;
        $participant['LOCAL_PLACE'] = $c+1;

        if (!empty($row['CCTLD'])) {
          $participant['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
          $participant['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
        }
        $participant['CURRENT_MEMBERS'] = 1; 
        if ($moderator) {
          $participant['REMOVE'] = $row;
          $participant['REMOVE']['CLUB_ID'] = $club_id;
        }
        $data['EVENT']['CURRENT_PARTICIPANTS']['PARTICIPANTS'][] = $participant;
      }
      if ($c == 0) {
        $data['EVENT']['NO_PARTICIPANTS'] = 1; 
      } else if ($moderator) {
        $data['EVENT']['CURRENT_PARTICIPANTS']['REMOVE_HEADER'] = 1;
      }
 
      $data['LANG'] = $_SESSION['_lang']; 
    }      

    return $data;
  }

  function getClubsData ($page=1,$perpage=PAGE_SIZE){
    global $db;
    global $_SESSION;

    $where = "1=1";
  
    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;
      
    $sql_count = "SELECT COUNT(N.GROUP_ID) ROWS
                   FROM forum_groups N, forum_groups_details ND 
                   WHERE ND.GROUP_ID=N.GROUP_ID
			AND N.TYPE=2
			AND ND.LANG_ID=".$_SESSION['lang_id']." AND ".$where; 

    $db->query($sql_count);
    if ($row = $db->nextRow()) {
      $count = $row['ROWS'];
    }

    $sql = "SELECT N.GROUP_ID, ND.GROUP_NAME, ND.DESCR, N.GROUP_MEMBERS, T.POSTS
              FROM forum_groups_details ND, forum_groups N
		     left join topic T ON T.TOPIC_ID=N.TOPIC_ID 
				AND T.LANG_ID=".$_SESSION['lang_id']."
            WHERE ND.GROUP_ID=N.GROUP_ID 
		AND ND.LANG_ID=".$_SESSION['lang_id']." 
		AND N.TYPE=2 AND	
              ".$where."
	    GROUP BY N.GROUP_ID 
            ORDER BY 
              ND.GROUP_NAME ASC, N.GROUP_ID DESC
            ".$limitclause;

    $db->query($sql);

    $clubs = array();
    while ($row = $db->nextRow()) {
      $club = $row; 
      $club['LANG'] = $_SESSION['_lang']; 
      if (empty($row['POSTS']))
        $club['POSTS'] = 0; 
      $clubs[] = $club;
    }

    return $clubs;
  }

  function getClubsEventsData ($box, $page=1,$perpage=PAGE_SIZE){
    global $smarty;
    global $db;
    global $_SESSION;

    if ($box)
      $where = " AND FGE.FINISHED = 'N' ";
    else       
      $where = " AND 1=1 "; 

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;
      
    $sql_count = "SELECT COUNT(N.GROUP_ID) ROWS
                   FROM forum_groups N, forum_groups_details ND, forum_groups_events FGE, forum_groups_events_details FGED
                   WHERE ND.GROUP_ID=N.GROUP_ID
			AND N.TYPE=2
			AND FGE.GROUP_ID=ND.GROUP_ID			
			AND FGE.EVENT_ID=FGED.EVENT_ID			
			AND FGED.LANG_ID=".$_SESSION['lang_id']."
			AND ND.LANG_ID=".$_SESSION['lang_id'].$where; 
    $db->query($sql_count);
    if ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

     $sql = "SELECT 
              N.GROUP_ID, ND.GROUP_NAME, N.GROUP_MEMBERS, T.POSTS, FGE.EVENT_ID, FGED.TITLE, FGE.PARTICIPANTS
              FROM forum_groups_details ND, forum_groups_events_details FGED, forum_groups N, forum_groups_events FGE
		     left join topic T ON T.TOPIC_ID=FGE.TOPIC_ID 
            WHERE ND.GROUP_ID=N.GROUP_ID 
		AND ND.LANG_ID=".$_SESSION['lang_id']." 
		AND N.TYPE=2
		AND FGE.GROUP_ID=ND.GROUP_ID			
		AND FGE.EVENT_ID=FGED.EVENT_ID			
		AND FGED.LANG_ID=".$_SESSION['lang_id'].$where."
	    GROUP BY FGE.EVENT_ID 
	    ORDER BY DATE_CREATED DESC
            ".$limitclause;
//echo $sql;
    $db->query($sql);
    $clubs = array();
    $club_events = array();
    while ($row = $db->nextRow()) {
      $club_event = $row; 
      $club_event['LANG'] = $_SESSION['_lang']; 
      if (empty($row['POSTS']))
        $club_event['POSTS'] = 0; 
      $clubs[$row['GROUP_ID']]['GROUP_ID'] = $row['GROUP_ID'];
      $clubs[$row['GROUP_ID']]['GROUP_NAME'] = $row['GROUP_NAME'];
      $clubs[$row['GROUP_ID']]['CLUB_EVENTS'][] = $club_event;
    }
    $db->free();
 
    $this->rows = $count;  
    $smarty->assign("clubs", $clubs);
    $db->free();
    return $clubs;
  }

  function addClubEventItem($club_id, $event_id = '') {
    global $db;
    global $_POST;
    global $langs;
    global $auth;
    global $_SESSION;
    global $conf_site_url;

    $error=FALSE;
    $r_fields=array('title', 'group_id');
    $s_fields='';
    $i_fields=array('group_id', 'participants');
    $d_fields='';
    $c_fields=array('recruitment_active');

    $s_fields_d=array('title', 'descr', 'results');
    $i_fields_d=array('lang_id');
    $d_fields_d='';
    $c_fields_d='';

    if(!requiredFieldsOk($r_fields, $_POST)){
	$error=TRUE;
	$data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];        
	return false;
    };
    if(!$error){
	$sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$_POST);
	$tdata=buildSaveData($s_fields_d,$i_fields_d,$d_fields_d,$c_fields_d,$_POST);
	// proceed to database updates
//$db->showquery=true;
	if(!empty($event_id)){
		// UPDATE
		$db->update('forum_groups_events', $sdata, "GROUP_ID=".$club_id." AND EVENT_ID=".$event_id);
		$tdata['event_id'] = $event_id;
		$db->select('forum_groups_events_details', "*", "EVENT_ID=".$event_id." AND LANG_ID=".$_POST['lang_id']);
		if ($row = $db->nextRow())
		  $db->update('forum_groups_events_details', $tdata, "EVENT_ID=".$event_id." AND LANG_ID=".$_POST['lang_id']);
		else $db->insert('forum_groups_events_details', $tdata);
	}else{
		// INSERT
		$sdata['DATE_CREATED'] = "NOW()";
		$db->insert('forum_groups_events', $sdata);
		$tdata['event_id'] = $db->id();
		$db->insert('forum_groups_events_details',$tdata);
	};
	// redirect to news page
    }
    return true;
  }

}   
?>