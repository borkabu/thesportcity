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
  $content = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);

  $manager = new Manager();
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);

  if ($auth->userOn())
    $manager_user = new ManagerUser($manager->mseason_id);

  $manager_filter_box = $managerbox->getManagerFilterBox($manager->mseason_id);
 
  if (empty($_GET['page']))
    $page = 1;
  else $page = $_GET['page'];
  if (empty($perpage))
    $perpage = 50; //$page_size;

  $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;

  $data['SEASON_ID'] = $manager->mseason_id;
  $tour_id = isset($_GET['tour_id']) ? $_GET['tour_id'] : '';

  $opt = array(
    'class' => 'input',
    'options' => array(
      'USER_NAME' => 'LANG_USER_NAME_U',
      'TEAM_NAME' => 'LANG_TEAM_NAME_U',
      'POINTS' => 'LANG_POINTS_U'
    )
  );
  
  
    // sorting
    if (empty($_GET['order']))
      $param['order'] = 'PLACE asc ';
    else $param['order'] = $_GET['order'];
    
    $order = $param['order'];
    $so_fields = array('POINTS', 'WEALTH', 'DATE_CREATED', 'USER_NAME', 'PLACE');
    for ($c=0; $c<sizeof($so_fields); $c++) {
      if ($param['order'] == $so_fields[$c].' desc') {
        $sort[$so_fields[$c].'_DESC_A']['URL'] = 'xxx';
        $sort[$so_fields[$c].'_ASC']['URL'] = url('order', $so_fields[$c].' asc');
      }
      elseif ($param['order'] == $so_fields[$c].' asc') {
        $sort[$so_fields[$c].'_DESC']['URL'] = url('order', $so_fields[$c].' desc');
        $sort[$so_fields[$c].'_ASC_A']['URL'] = 'xxx';
      }
      else {
        $sort[$so_fields[$c].'_DESC']['URL'] = url('order', $so_fields[$c].' desc');
        $sort[$so_fields[$c].'_ASC']['URL'] = url('order', $so_fields[$c].' asc');
      }
    }

    $sql= "SELECT DISTINCT C.ID, C.LATIN_NAME, C.ORIGINAL
		FROM users U, manager_users MU, countries C
         WHERE MU.USER_ID = U.USER_ID 
 	   AND MU.SEASON_ID=".$manager->mseason_id."
	   AND U.COUNTRY = C.ID
         ORDER BY C.LATIN_NAME";
//echo $sql;
    $db->query($sql);
    $country['class'] = 'input';
    $country['options'][0] = '';
    while ($row = $db->nextRow()) {
      $country['options'][$row['ID']] = $row['LATIN_NAME']. " (". $row['ORIGINAL'].")";
    }

//print_r($country);
    $search['COUNTRY'] = $frm->getInput(FORM_INPUT_SELECT, 'country', isset($_GET['country']) ? $_GET['country'] : '', $country, isset($_GET['country']) ? $_GET['country'] : '');

    $search['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
    $search['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
    $search['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));
  
    $param['where'] = '';
    if (!empty($_GET['where'])) {
      $param['where'] = " AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%') ";
      $search['FILTERED'] = 1;
    }

    if (!empty($_GET['country'])) {
      $param['where'] .= " AND U.COUNTRY=".$_GET['country'];
      $search['FILTERED'] = 1;
    }

    $where_table=" manager_standings MS ";
    $external_source = "";
    if (isset($_SESSION['external_user']) && !isset($_GET['all'])) {
      $where_table=" manager_standings_external MS ";
      $external_source = " AND MU.SOURCE='".$_SESSION['external_user']['SOURCE']."'";
    }

    if (!empty($_GET['tour_id'])) {
       $sql = "SELECT COUNT(*) ROWCOUNT
		 FROM manager_users_tours MUT, users U, manager_users MU
		WHERE U.USER_ID = MUT.USER_ID
                      AND MU.USER_ID = MUT.USER_ID
                      AND MU.SEASON_ID=".$manager->mseason_id." 
		      AND MUT.TOUR_ID = ".$_GET['tour_id']." 
                      AND MUT.SEASON_ID=".$manager->mseason_id." 
                      ".$param['where'].$external_source;
    } else {
      $sql="SELECT COUNT(*) ROWCOUNT
          FROM users U, manager_users MU, manager_standings MS
         WHERE MS.USER_ID = U.USER_ID 
	   AND MS.MSEASON_ID=".$manager->mseason_id."
 	   AND MU.SEASON_ID=".$manager->mseason_id."
           AND MU.USER_ID = MS.USER_ID ".$param['where'].$external_source;
    }
    $db->query($sql);
    $row = $db->nextRow();
    $user_count=$row['ROWCOUNT'];
//echo "<!--".$sql."-->";
    $db->free(); 
//echo $user_count;
    if ($user_count > 0) {
      if (!empty($_GET['tour_id'])) {
        $sql = "SELECT U.USER_NAME, U.USER_ID, MUT.POINTS AS KOEFF, MUT.PLACE_TOUR AS PLACE, MU.SEASON_ID, ROUND(MUT.RATING, 2) as RATING, 
			MUT.USER_ID AS PLAYER_ID, MU.TEAM_NAME, MU.ALLOW_VIEW, MUT.MONEY as WEALTH, 
			C.CCTLD, CD.COUNTRY_NAME, MU.CHALLENGES_WON, MU.CHALLENGES_LOST, MU.CHALLENGES_WON + MU.CHALLENGES_LOST CHALLENGES_SUM,
			MU.BATTLES_WON, MU.BATTLES_LOST, MU.BATTLES_WON + MU.BATTLES_LOST BATTLES_SUM, MU.SOURCE
		 FROM manager_users_tours MUT, users U, manager_users MU, countries C
			LEFT JOIN countries_details CD ON C.ID=CD.ID aND CD.LANG_ID=".$_SESSION['lang_id']."
		WHERE U.USER_ID = MUT.USER_ID
                      AND MU.USER_ID = MUT.USER_ID
                      AND MU.SEASON_ID=".$manager->mseason_id." 
		      AND MUT.TOUR_ID = ".$_GET['tour_id']." 
                      AND MUT.SEASON_ID=".$manager->mseason_id." 
		      AND U.COUNTRY = C.ID
                      ".$param['where'].$external_source." 
                ORDER BY ".$param['order'].$limitclause;
         $db->query($sql);   
//echo $sql;
      } else {
        $sql="SELECT U.USER_NAME, U.USER_ID, MU.ALLOW_VIEW, MU.TEAM_NAME, 
                   MS.POINTS AS KOEFF, MS.PLACE, MS.PLACE_PREV, MU.SEASON_ID,
  		   MS.WEALTH, C.CCTLD, CD.COUNTRY_NAME, MU.CHALLENGES_WON, MU.CHALLENGES_LOST, MU.CHALLENGES_WON + MU.CHALLENGES_LOST CHALLENGES_SUM,
		   MU.BATTLES_WON, MU.BATTLES_LOST, MU.BATTLES_WON + MU.BATTLES_LOST BATTLES_SUM, MU.SOURCE
	          FROM users U, manager_users MU, ".$where_table.", countries C
			LEFT JOIN countries_details CD ON C.ID=CD.ID aND CD.LANG_ID=".$_SESSION['lang_id']."
                   WHERE MS.USER_ID = U.USER_ID 
	 	    AND MS.MSEASON_ID=".$manager->mseason_id."
 	  	    AND MU.SEASON_ID=".$manager->mseason_id."
                    AND MU.USER_ID = MS.USER_ID
		    AND U.COUNTRY = C.ID
                    ".$param['where'].$external_source." 
                  GROUP BY MU.USER_ID
                  ORDER BY ".$param['order'].$limitclause;
      }
      $db->query($sql);

    } else {
        $sql="SELECT COUNT(*) ROWCOUNT
          FROM users U, manager_users MU
         WHERE MU.SEASON_ID=".$manager->mseason_id."
           AND MU.USER_ID = U.USER_ID ".$param['where'].$external_source;

        $db->query($sql);
        $row = $db->nextRow();
        $user_count=$row['ROWCOUNT'];
        $db->free(); 

        if (empty($_GET['order']))
          $param['order'] = " DATE_CREATED asc ";

        $sql="SELECT U.USER_NAME, U.USER_ID, MU.ALLOW_VIEW, MU.TEAM_NAME, 0 as RATING,
		0 AS KOEFF, 0 As POINTS, 0 AS PLACE, 0 as PLACE_PREV, MU.SEASON_ID, C.CCTLD, CD.COUNTRY_NAME, 
		MU.CHALLENGES_WON, MU.CHALLENGES_LOST, MU.BATTLES_WON, MU.BATTLES_LOST, 0 as WEALTH, MU.SOURCE
          FROM users U, manager_users MU, countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
         WHERE MU.SEASON_ID=".$manager->mseason_id."
           AND MU.USER_ID = U.USER_ID
	    AND U.COUNTRY = C.ID
           ".$param['where'].$external_source." 
         ORDER BY ".$param['order'].$limitclause;

       $db->query($sql);
     }
//echo $sql;
    $c = 0;
    $users = array();
    while ($row = $db->nextRow()) {
      $user = $row;
      if (isset($row['RATING'])) {
        $rating_header = 1;
        $user['RATING'] = $row['RATING'];
      } 
      if (isset($user['SOURCE']) && $user['SOURCE'] != "")
        $user['SOURCE_LOGO'] = $clients[$user['SOURCE']]['small_logo'];

      if (!empty($row['CCTLD'])) {
        $user['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $user['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }
      if (isset($user['CHALLENGES_SUM']) && $user['CHALLENGES_SUM'] > 0) {
        $user['VIEW_CHALLENGES']['CHALLENGES_SUM'] = $user['CHALLENGES_SUM'];
        $user['VIEW_CHALLENGES']['USER_ID'] = $user['USER_ID'];
        $user['VIEW_CHALLENGES']['SEASON_ID'] = $user['SEASON_ID'];
      } else {
        $user['NOT_VIEW_CHALLENGES']['CHALLENGES_SUM'] = 0;
      }

      if (isset($user['BATTLES_SUM']) && $user['BATTLES_SUM'] > 0) {
        $user['VIEW_BATTLES']['BATTLES_SUM'] = $user['BATTLES_SUM'];
        $user['VIEW_BATTLES']['USER_ID'] = $user['USER_ID'];
        $user['VIEW_BATTLES']['SEASON_ID'] = $user['SEASON_ID'];
      } else {
        $user['NOT_VIEW_BATTLES']['BATTLES_SUM'] = 0;
      }

      if (!empty($row['PLACE_PREV'])) {
        if ($row['PLACE'] < $row['PLACE_PREV']) {
          $user['UP'] = 1;
        } else if ($row['PLACE'] > $row['PLACE_PREV']) {
          $user['DOWN'] = 1;
        }
      }

      if ($auth->getUserId() == $row['USER_ID']) {          
        if ($row['ALLOW_VIEW'] == '-1') {
          $user['NOTALLOW'] = 1;
	  $user['CURRENT'] = 1;
        }
        else {
          $user['ALLOW'] = 1;
	  $user['CURRENT'] = 1;
        }
      }
      else {
        if (($auth->hasSupporter() && (!$manager->manager_trade_allow || $manager->season_over)) || $row['ALLOW_VIEW'] == '1') {
          $user['ALLOW'] = 1;
	  $user['NONCURRENT'] = 1;
        }
        else {
          $user['NOTALLOW'] = 1;
	  $user['NONCURRENT'] = 1;
        }
      }
      $users[] = $user;
    }
    $rows = $user_count;
    $db->free();

  $paging = $pagingbox->getPagingBox($rows);

  $tours = array();
  if ($user_count > 0) {
       $sql = "SELECT DISTINCT MUT.TOUR_ID
		 FROM manager_users_tours MUT
		WHERE MUT.SEASON_ID=".$manager->mseason_id." 
                ORDER BY MUT.TOUR_ID";
       $db->query($sql);   
        while ($row = $db->nextRow()) {
           $state = 'NORMAL'; 
           if (!empty($_GET['tour_id']) && $row['TOUR_ID'] == $_GET['tour_id'])
             $state = 'SELECTED'; 
           $tour = $row;
	   $tour[$state] = 1;
           $tour['NUMBER'] = $row['TOUR_ID'];
           $tour['MSEASON_ID'] = $manager->mseason_id;
           $tours[] = $tour;
        }
        if (isset($_GET['tour_id'])) {
          $all['NORMAL']['MSEASON_ID'] = $manager->mseason_id;;
        } else {
          $all['SELECTED'] = 1;
        }
   }

  if (isset($_SESSION['external_user']) && !isset($_GET['all']))
    $smarty->assign("external_site", true);
  else if (isset($_SESSION['external_user']) && isset($_GET['all']))
    $smarty->assign("local_site", $_SESSION['external_user']['SOURCE']);
  $smarty->assign("manager_filter_box", $manager_filter_box);
  $smarty->assign("season_id", $manager->mseason_id);
  $smarty->assign("tour_id", $tour_id);
  $smarty->assign("paging", $paging);
  $smarty->assign("users", $users);
  $smarty->assign("tours", $tours);
  $smarty->assign("all", $all);
  if (isset($rating_header))
    $smarty->assign("rating_header", $rating_header);
  $smarty->assign("search", $search);
  $smarty->assign("order", $order);
  $smarty->assign("sort", $sort);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_standings.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_standings.smarty'.($stop-$start);

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