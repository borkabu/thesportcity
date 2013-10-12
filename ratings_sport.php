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
//include('class/rating.inc.php');

// --- build content data -----------------------------------------------------
//$db->showquery=true;

  $content = '';
  $content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 4);

  if (empty($_GET['page']))
    $page = 1;
  else $page = $_GET['page'];
  if (empty($perpage))
    $perpage = 100; //$page_size;

  $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $query_input = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
    $filtering['WHERE_CLAN'] = $frm->getInput(FORM_INPUT_CHECKBOX, 'where_clan', 1, array('class' => 'input'), isset($_GET['where_clan']) ? $_GET['where_clan'] : '');
    $data['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

    if (isset($_GET['rating_id'])) {
      $param['rating'] = " AND SPORT_ID=".$_GET['rating_id']." AND TOURNAMENT_ID=0";      
    } 

    $sql="SELECT DATEDIFF(NOW(), DATE_ADD(min(TOUR_END_DATE), INTERVAL 1 YEAR)) DAY_RANGE, CURDATE() as CURRENT_DAY
		FROM manager_rating_points";
    $db->query($sql);
    $row = $db->nextRow();
    $date_range=-1*$row['DAY_RANGE'];
    if (empty($_GET['rating_date'])) 
      $date_set = $row['CURRENT_DAY'];
    else 
      $date_set = $_GET['rating_date'];

    $param['where'] = "";
    if (!empty($_GET['query'])) {
      $param['where'] = " AND UPPER(USER_NAME) like UPPER('%".$_GET['query']."%') ";
      $filtered['RATINGS'] = $_GET['rating_id'];
      $filtered['URL'] = "ratings_sport.php";
    }

    $having_seasons = "";
    if ($_GET['rating_id'] != 3)
      $having_seasons = " HAVING SEASONS > 1";

    $param['where_clan'] = "";
    if (!empty($_GET['where_clan'])) {
      $param['where_clan'] = " AND U.USER_ID IN (SELECT USER_ID FROM clan_members 
						WHERE CLAN_ID=".$auth->isClanMember()." AND STATUS in (1, 2))";
    }

    if (isset($_GET['rating_id'])) {
      if (empty($_GET['rating_date'])) {
        $sql="SELECT COUNT(DISTINCT MS.USER_ID) ROWCOUNT
            FROM manager_ratings MS, users U
           WHERE MS.USER_ID = U.USER_ID 
              ".$param['where'].$param['where_clan'].$param['rating'];
      } else {
        $sql="SELECT COUNT(DISTINCT MS.USER_ID) ROWCOUNT
                FROM manager_rating_points MS, users U
              WHERE MS.USER_ID = U.USER_ID 
	            AND SPORT_ID=".$_GET['rating_id']."
                    AND MS.TOUR_END_DATE <= '".$_GET['rating_date']."'
                    AND MS.TOUR_END_DATE >= DATE_ADD('".$_GET['rating_date']."', INTERVAL -1 YEAR)
                 ".$param['where'].$param['where_clan'];  
      }

      $db->query($sql);
      $row = $db->nextRow();
      $user_count=$row['ROWCOUNT'];
//echo $sql;
      $db->free(); 
//echo $user_count;

      if (empty($_GET['rating_date'])) {
        $sql="SELECT U.USER_NAME, U.USER_ID, MS.POINTS as KOEFF, MS.PLACE, C.CCTLD, CD.COUNTRY_NAME
	          FROM users U, manager_ratings MS , countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
                       WHERE MS.USER_ID = U.USER_ID 
		    AND U.COUNTRY = C.ID
                        ".$param['where'].$param['where_clan'].$param['rating']." 
                      ORDER BY MS.PLACE ASC, U.USER_NAME ".$limitclause;
      } else {
        $sql="SELECT U.USER_NAME, U.USER_ID, ROUND(SUM(RATING_POINTS+BONUS)/SUM(TOURS) + COUNT( DISTINCT SEASON_ID ), 2) KOEFF, COUNT( DISTINCT SEASON_ID ) SEASONS
		FROM (SELECT SUM(MUT.RATING_POINTS) RATING_POINTS, MUT.BONUS/100 as BONUS, USER_ID, MUT.SEASON_ID, COUNT(DISTINCT MUT.TOUR_END_DATE) TOURS
			FROM manager_rating_points MUT
			WHERE MUT.TOUR_END_DATE <= '".$_GET['rating_date']."'
		              AND MUT.TOUR_END_DATE >= DATE_ADD('".$_GET['rating_date']."', INTERVAL -1 YEAR)
			      AND MUT.SPORT_ID=".$_GET['rating_id']."
			GROUP BY MUT.USER_ID, MUT.SEASON_ID
			HAVING TOURS >=3
		     ) MS, users U, countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
                WHERE MS.USER_ID = U.USER_ID 
			AND U.COUNTRY = C.ID
	                ".$param['where'].$param['where_clan']."              
		GROUP BY U.USER_ID
		".$having_seasons."
               ORDER BY KOEFF DESC, U.USER_NAME ".$limitclause;
      }
//echo $sql;
      $db->query($sql);
      $users = array();
      $c = 0; 
      while ($row = $db->nextRow()) {
        $user = $row;
        if (!empty($row['CCTLD'])) {
          $user['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
          $user['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
        }
//        if ($row['PLACE'] == 0)
          $user['PLACE'] = ++$c + ($page-1) * $perpage;
        $users[] = $user;
      }
      $paging = $pagingbox->getPagingBox($user_count);
  } 
  if (!isset($_GET['rating_id']))
    $PRESET_VARS['rating_id'] = '0_0';
  else $PRESET_VARS['rating_id'] = $_GET['rating_id'];
  $rating_id = inputManagerSportTypes('rating_id', $PRESET_VARS['rating_id']);

  if ($auth->isClanMember()) {
    $smarty->assign("clan", true);
  }

  $smarty->assign("rating_id", $rating_id);
  if (isset($users)) {
    $smarty->assign("users", $users);
    $smarty->assign("paging", $paging);
  }
  $smarty->assign("query_input", $query_input);  
  $smarty->assign("filtering", $filtering);  
  $smarty->assign("date_range", $date_range);
  $smarty->assign("date_set", $date_set);
  if (isset($filtered))
    $smarty->assign("filtered", $filtered);


  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/ratings2.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/ratings2.smarty'.($stop-$start);

// ----------------------------------------------------------------------------

// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

// close connections
include('class/db_close.inc.php');
?>