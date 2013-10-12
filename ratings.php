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
//$tpl->setCacheTtl(60);

  $content = '';
  $content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 4);


  if (empty($_GET['page']))
    $page = 1;
  else $page = $_GET['page'];
  if (empty($perpage))
    $perpage = 100; //$page_size;

  $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $query_input = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));

    $param['rating'] = ' AND SPORT_ID=0 AND TOURNAMENT_ID=0';

    $param['where'] = "";
    if (!empty($_GET['query'])) {
      $param['where'] = " AND UPPER(USER_NAME) like UPPER('%".$_GET['query']."%') ";
      $filtered = 1;
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
    if (empty($_GET['rating_date'])) {
      $sql="SELECT COUNT(DISTINCT MS.USER_ID) ROWCOUNT
          FROM manager_ratings MS, users U
         WHERE MS.USER_ID = U.USER_ID 
            ".$param['where'].$param['rating'];
    } else {
      $sql="SELECT COUNT(DISTINCT MS.USER_ID) ROWCOUNT
              FROM manager_rating_points MS, users U
            WHERE MS.USER_ID = U.USER_ID 
                  AND MS.TOUR_END_DATE <= '".$_GET['rating_date']."'
                  AND MS.TOUR_END_DATE >= DATE_ADD('".$_GET['rating_date']."', INTERVAL -1 YEAR)
               ".$param['where'];

    }

    $db->query($sql);
    $row = $db->nextRow();
    $user_count=$row['ROWCOUNT'];
    $db->free(); 
    if (empty($_GET['rating_date'])) {
      $sql="SELECT U.USER_NAME, U.USER_ID, MS.POINTS as KOEFF, MS.PLACE, C.CCTLD, CD.COUNTRY_NAME, MS.SEASONS
	          FROM users U, manager_ratings MS , countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
                     WHERE MS.USER_ID = U.USER_ID 
		    AND U.COUNTRY = C.ID
                      ".$param['where'].$param['rating']." 
                    ORDER BY MS.PLACE ASC, U.USER_NAME ".$limitclause;
    } else {
      $sql="SELECT U.USER_NAME, U.USER_ID, ROUND(SUM(RATING_POINTS+BONUS)/SUM(TOURS) + COUNT( DISTINCT SEASON_ID ), 2) as KOEFF, 0 as PLACE, C.CCTLD, CD.COUNTRY_NAME,COUNT( DISTINCT SEASON_ID ) SEASONS
		FROM (SELECT SUM(MUT.RATING_POINTS) RATING_POINTS, MUT.BONUS/100 as BONUS, USER_ID, MUT.SEASON_ID, COUNT(DISTINCT MUT.TOUR_END_DATE) TOURS
			FROM manager_rating_points MUT
			WHERE MUT.TOUR_END_DATE <= '".$_GET['rating_date']."'
		              AND MUT.TOUR_END_DATE >= DATE_ADD('".$_GET['rating_date']."', INTERVAL -1 YEAR)
			GROUP BY MUT.USER_ID, MUT.SEASON_ID
			HAVING TOURS >=3
		     ) MS, users U, countries C
			LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
	        WHERE MS.USER_ID = U.USER_ID 
		    AND U.COUNTRY = C.ID
		    ".$param['where']."
		GROUP BY USER_ID
		HAVING SEASONS>1 
		ORDER BY KOEFF DESC, U.USER_NAME ".$limitclause;
    }

      $db->query($sql);
      $users = array();
      $c = 0; 
      while ($row = $db->nextRow()) {
        $user = $row;
        if (!empty($row['CCTLD'])) {
          $user['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
          $user['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
        }
        if ($row['PLACE'] == 0)
          $user['PLACE'] = ++$c + ($page-1) * $perpage;
        $users[] = $user;
      }
    

  $paging = $pagingbox->getPagingBox($user_count);

  //$tpl->addData($data);  
  $smarty->assign("users", $users);
  $smarty->assign("paging", $paging);
  $smarty->assign("query_input", $query_input);  
  $smarty->assign("date_range", $date_range);
  $smarty->assign("date_set", $date_set);
  if (isset($filtered))
    $smarty->assign("filtered", true);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/ratings.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/ratings.smarty'.($stop-$start);

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