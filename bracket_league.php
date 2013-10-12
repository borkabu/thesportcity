<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
help.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows help item
  = shows help topic index

TABLES USED: 
  - BASKET.HELP

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
include('class/update.inc.php');

// classes
include('class/db.class.php');
include('class/template.class.php');
include('class/language.class.php');
include('class/form.class.php');

// connections
include('class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/bracket.inc.php');
include('class/bracket_user.inc.php');
// --- build content data -----------------------------------------------------

// include common header
$content = '';

//$db->showquery=true;

  $bracket = new Bracket();
  $bracketbox = new BracketBox($langs, $_SESSION["_lang"]);
  $bracket_user = new BracketUser($bracket->tseason_id);

  $bracket_filter_box = $bracketbox->getBracketFilterBox($bracket->tseason_id );

  $forumPermission = new ForumPermission();
  if (isset($_POST['post_comment']) && $auth->userOn() &&
     $forumPermission->canAddComment($_POST['topic_id']) == 0) {
     $forumbox->addPost($forums['ARRANGER_LEAGUES'], $_POST['topic_id'], $_POST['item_id']);
  }

  if (!$auth->userOn()) {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_ARRANGER_LOGIN');
  } 

     if (isset($_GET['league_id'])) {
       $last_tour_id = $bracket->getLastTour();
       // particular tournament requested
       $sql= "SELECT ML.*, MLM.*, U.USER_NAME, MSS.POINTS, MSS.PLACE, MUT.POINTS AS LAST_POINTS, 
		C.CCTLD, CD.COUNTRY_NAME
            FROM bracket_leagues ML, bracket_leagues_members MLM, 
	       bracket_seasons MS, users U
                 LEFT JOIN bracket_standings MSS ON MSS.USER_ID=U.USER_ID AND MSS.MSEASON_ID=".$bracket->tseason_id ."
                 LEFT JOIN bracket_users_tours MUT ON MUT.USER_ID=U.USER_ID AND MUT.SEASON_ID=".$bracket->tseason_id." AND MUT.TOUR_ID=".$last_tour_id."
	      , countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
           WHERE ML.LEAGUE_ID=".$_GET['league_id']."
	        AND MLM.LEAGUE_ID=".$_GET['league_id']."
                AND MS.SEASON_ID=ML.SEASON_ID   
                AND MLM.USER_ID=U.USER_ID
	        AND MLM.STATUS IN (1,2)
   	        AND U.COUNTRY = C.ID
           ORDER BY MSS.POINTS DESC"; 
      $db->query($sql);     
      $c=1;    
      $members = array(); 
      while ($row = $db->nextRow()) {
        $member = $row;
        $member['LOCAL_PLACE'] = $c;

        if (!empty($row['CCTLD'])) {
          $member['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
          $member['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
        }

        if ($row['STATUS'] ==1) {
          $member['OWNER'] = 1;
          $league['TITLE'] = $row['TITLE'];
          $league['OWNER'] = $row['USER_NAME'];
          $league['ENTRY_FEE'] = $row['ENTRY_FEE'];
	  $owner=$row['USER_ID'];
          $league['CHAT']['CHAT_CHANNEL'] = $row['TITLE']."_".$row['LEAGUE_ID']."_".$row['SEASON_ID'];
          if (!empty($row['RULES']))  
            $league['RULES'] = $row['RULES'];

          $c++;
        }
        else if ($row['STATUS'] == 2) {
          $member['CURRENT_MEMBERS'] = 1;
          $c++;
        } 
        $members[] = $member;
      }
      $league['SEASON_TITLE'] = $bracket->getTitle();
      $league['MEMBERS'] = $members;
  
      // get voting
      if ($auth->userOn()) { 
        $sql= "SELECT DISTINCT MLV.VOTE, MLM.LEAGUE_ID, MLM.USER_ID
            FROM bracket_leagues_members MLM
			left join bracket_leagues_votes MLV
					ON MLV.LEAGUE_ID=MLM.LEAGUE_ID
						AND MLV.USER_ID=".$auth->getUserId()."
           WHERE MLM.LEAGUE_ID=".$_GET['league_id']."
		AND MLM.STATUS <> 3
		AND MLM.USER_ID=".$auth->getUserId(); 

        $db->query($sql);     
        if ($row = $db->nextRow()) {
          // can vote
	  $league['VOTING'] = $row;
          if ($row['VOTE'] > 0)
  	    $league['VOTING']['THUMB_UP'] = 1;
          if ($row['VOTE'] < 0)
  	    $league['VOTING']['THUMB_DOWN'] = 1;
        }
      }

      $pleague = new League("bracket", $_GET['league_id']);
      $rating = $pleague->getOwnerRating($owner);
      if ($rating > -1) 
        $league['OWNER_RATING'] = $rating;
      else $league['OWNER_RATING'] = $langs['LANG_NONE_U'];

      // get league rating
      $rating = $pleague->getLeagueRating();
      if ($rating > -1) 
        $league['LEAGUE_RATING'] = $rating;
      else $league['LEAGUE_RATING'] = $langs['LANG_NONE_U'];

    } else {
    
      $filtering = "";
      $leagues_number = 0;
      if ((isset($_GET['all']) && $_GET['all'] == 'y') || !$auth->userOn()) {
        $_GET['all'] = 'y';
        $leagues = $bracket->getLeagues();
        $leagues_number = $bracket->leagues;
        $filtering['ALL_LEAGUES'] = 1;
      }
      else {
        $_GET['all'] = 'n';
        $leagues = $bracket_user->getLeagues();
        $leagues_number = $bracket_user->leagues;
        $filtering['MY_LEAGUES'] = 1;
      }

      if ($leagues_number > 0) {      
        $leagues_data['LEAGUES'] = $leagues;
        $leagues_data['LEAGUES_PAGING'] = $pagingbox->getPagingBox($leagues_number, isset($_GET['page2']) ? $_GET['page2'] : 1, $page_size, 'page2');
      }
      else $create_league_offer = 1;
    
    // add data
   }

  $smarty->clearAllAssign();  
//print_r($league);
  if (isset($league)) {
    $smarty->assign("league_item", $league);
  }
  if (isset($leagues_data))
    $smarty->assign("leagues", $leagues_data);
  if (isset($filtering))
    $smarty->assign("filtering", $filtering);

  if (isset($error))  
    $smarty->assign("error", $error);
  $smarty->assign("bracket_filter_box", $bracket_filter_box);
  if (isset($create_league_offer))
    $smarty->assign("create_league_offer", $create_league_offer);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/bracket_league.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/bracket_league.smarty'.($stop-$start);

//$db->showquery=true;
  if (isset($_GET['league_id']) && $_GET['league_id'] != '') {
    $content .= $forumbox->getComments($_GET['league_id'], 'ARRANGER_LEAGUES', isset($_GET['page']) ? $_GET['page'] : 1);
  }

  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 7);
// ----------------------------------------------------------------------------

  define("ARRANGER", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_arranger.inc.php');

// close connections
include('class/db_close.inc.php');
?>