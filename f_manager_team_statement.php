<?php
/*
===============================================================================
toto.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows totalizator result archive

TABLES USED: 
  - BASKET.totalizators
  - BASKET.totalizator_votes
  - BASKET.users
  - BASKET.games

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
include('class/manager.inc.php');
include('class/manager_user.inc.php');

// --- build content data -----------------------------------------------------
//$db->showquery=true;
//$tpl->setCacheTtl(60);

  $content = '';
  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);

  $manager = new Manager();
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);

  $manager_filter_box = $managerbox->getManagerFilterBox($manager->mseason_id);

  if (!$auth->userOn()) {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_LOGIN');
  } 
  else {
    $manager_user = new ManagerUser($manager->mseason_id);

    if (empty($_GET['page']))
      $page = 1;
    else $page = $_GET['page'];
    if (empty($perpage))
      $perpage = 50; //$page_size;

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $param['where'] = '';
    if (!empty($_GET['where'])) {
      $param['where'] = " AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%') ";
      $data['FILTERED'][0]['X'] = 1;
    }

    $sql ="SELECT * FROM manager_users_tours MUT 
		WHERE MUT.SEASON_ID=" . $manager->mseason_id . "
			AND USER_ID=".$auth->getUserId()."
			ORDER BY TOUR_ID ASC";
    $db->query($sql);
//echo $sql;
    $users_tours = array();
    while ($row = $db->nextRow()) {
      $user_tour = $row;
      $users_tours[] = $user_tour;
    }
    $db->free();

    if (isset($_GET['tour_id'])) {
       $sql = "SELECT * FROM manager_tours MTR
		WHERE MTR.SEASON_ID=" . $manager->mseason_id . "
		      AND MTR.NUMBER=" . $_GET['tour_id'];
       $db->query ( $sql );
       $row = $db->nextRow ();
       $tour_start_date = $row['START_DATE'];
       $tour_end_date = $row['END_DATE'];

       $sql = "SELECT MTR.NUMBER, B.LAST_NAME, B.FIRST_NAME, B1.LAST_NAME as SUBST_LAST_NAME, B1.FIRST_NAME  as SUBST_FIRST_NAME, IF(MC.ENTRY_ID IS NULL,1,2)*MPS.TOTAL_POINTS as TOTAL_POINTS, MC.ENTRY_ID AS CAPTAIN, MPSS.TOTAL_POINTS AS TOTAL_SUBST_POINTS 
	 	 FROM busers B, manager_tours MTR,
		   manager_player_stats MPS, manager_teams MT
		   LEFT JOIN manager_captain MC ON MT.ENTRY_ID=MC.ENTRY_ID 
			     AND '".$tour_start_date."' > MC.START_DATE 
	 	      	     AND ('".$tour_end_date."' < MC.END_DATE OR MC.END_DATE IS NULL)
                   LEFT JOIN manager_player_substitute_stats MPSS
				ON MPSS.USER_ID=".$auth->getUserId()."
				  AND MPSS.PLAYER_ID=MT.PLAYER_ID
				  AND MPSS.SEASON_ID=".$manager->mseason_id."
				  AND MPSS.TOUR_ID=".$_GET['tour_id']."
		   LEFT JOIN busers B1 ON MPSS.SUBPLAYER_ID=B1.USER_ID
		 WHERE MT.PLAYER_ID=B.USER_ID
		and MT.user_id=".$auth->getUserId()."
		and MT.season_id=".$manager->mseason_id."
		AND MTR.number = ".$_GET['tour_id']." 
		AND MTR.season_id=".$manager->mseason_id."
		and MTR.START_DATE >= MT.BUYING_DATE 
		AND (MTR.END_DATE <= MT.SELLING_DATE OR MT.SELLING_DATE IS NULL)
		and MPS.tour_id=MTR.number 
		and MPS.season_id=".$manager->mseason_id."
		and MPS.player_id=MT.player_id
	      ORDER BY B.LAST_NAME, B.FIRST_NAME";
    } else {
       $sql = "SELECT M1.PLAYER_ID, M1.NUMBER, M1.LAST_NAME, M1.FIRST_NAME, B1.LAST_NAME as SUBST_LAST_NAME, B1.FIRST_NAME  as SUBST_FIRST_NAME, IF(M2.ENTRY_ID IS NULL,1,2)*M1.TOTAL_POINTS as TOTAL_POINTS, M2.ENTRY_ID AS CAPTAIN, MPSS.TOTAL_POINTS AS TOTAL_SUBST_POINTS from
		(SELECT DISTINCT MT.PLAYER_ID, MTR.NUMBER, B.LAST_NAME, B.FIRST_NAME, MPS.TOTAL_POINTS as TOTAL_POINTS, MT.ENTRY_ID
		 FROM busers B, manager_tours MTR, manager_player_stats MPS, manager_teams MT

		WHERE MT.PLAYER_ID=B.USER_ID
		and MT.user_id=".$auth->getUserId()."
		and MT.season_id=".$manager->mseason_id."
		AND MTR.season_id=".$manager->mseason_id."
		and MTR.START_DATE >= MT.BUYING_DATE 
		AND (MTR.END_DATE <= MT.SELLING_DATE OR MT.SELLING_DATE IS NULL)
		and MPS.tour_id=MTR.number 
		and MPS.season_id=".$manager->mseason_id."
		and MPS.player_id=MT.player_id) M1

		 LEFT JOIN (select DISTINCT MC.ENTRY_ID, MTR.NUMBER, MT.USER_ID from manager_captain MC, manager_tours MTR, manager_teams MT
				WHERE 
				MT.user_id=".$auth->getUserId()."
				and MT.season_id=".$manager->mseason_id."
				AND MTR.season_id=".$manager->mseason_id."
				and MTR.START_DATE >= MT.BUYING_DATE 
				AND (MTR.END_DATE <= MT.SELLING_DATE OR MT.SELLING_DATE IS NULL)
				and MC.START_DATE <= MTR.START_DATE 
				AND (MC.END_DATE >= MTR.END_DATE OR MC.END_DATE IS NULL)
				AND MT.ENTRY_ID=MC.ENTRY_ID) M2 
					ON M1.ENTRY_ID=M2.ENTRY_ID and M2.NUMBER=M1.NUMBER
                 LEFT JOIN manager_player_substitute_stats MPSS
				ON MPSS.USER_ID=".$auth->getUserId()."
				  AND MPSS.PLAYER_ID=M1.PLAYER_ID
				  AND MPSS.SEASON_ID=".$manager->mseason_id."
				  AND MPSS.TOUR_ID=M1.NUMBER
		 LEFT JOIN busers B1 ON MPSS.SUBPLAYER_ID=B1.USER_ID
	      ORDER BY M1.NUMBER, M1.LAST_NAME, M1.FIRST_NAME";
    }
    $db->query($sql);
//echo $sql;
    $users = array();
    while ($row = $db->nextRow()) {
      $user = $row;
      if (!empty($row['SUBST_LAST_NAME']) || !empty($row['SUBST_FIRST_NAME'])) {
	$user['SUBSTITUTE'] = $row;
	$user['TOTAL_POINTS'] = $row['TOTAL_SUBST_POINTS'];
      }
      if (!empty($row['CAPTAIN']))
        $user['CAPTAINCY'] = $row['CAPTAIN'];
      $users[] = $user;
    }
    $db->free();

    $sql = "SELECT DISTINCT MUT.TOUR_ID
		 FROM manager_users_tours MUT
		WHERE MUT.SEASON_ID=".$manager->mseason_id." 
                ORDER BY MUT.TOUR_ID";
    $db->query($sql);   
    $tours = array();
    while ($row = $db->nextRow()) {
      unset($tour);
      $state = 'NORMAL'; 
      if (isset($_GET['tour_id']) && $row['TOUR_ID'] == $_GET['tour_id'])
        $state = 'SELECTED'; 
      $tour[$state] = $row;
      $tour[$state]['NUMBER'] = $row['TOUR_ID'];
      $tour[$state]['MSEASON_ID'] = $manager->mseason_id;
      $tours[] = $tour;
    }
    if (isset($_GET['tour_id'])) {
      $all['NORMAL']['MSEASON_ID'] = $manager->mseason_id;;
    } else {
      $all['SELECTED'] = 1;
    }
  }

  $smarty->assign("manager_filter_box", $manager_filter_box);
  $smarty->assign("season_id", $manager->mseason_id);
  $smarty->assign("tours", $tours);
  $smarty->assign("all", $all);
  $smarty->assign("users_tours", $users_tours);
  $smarty->assign("users", $users);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_team_statement.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_team_statement.smarty'.($stop-$start);

// ----------------------------------------------------------------------------
    define("FANTASY_MANAGER", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager.inc.php');

// close connections
include('class/db_close.inc.php');
?>