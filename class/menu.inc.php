<?php
/*
===============================================================================
menu.inc.php
-------------------------------------------------------------------------------
Generates site menu
===============================================================================
*/

class Menu {
  var $mainmenu;

  function Menu($mainmenu) {
     $this->mainmenu = $mainmenu;
  }

  function getMenu ($page, $align = "left", $width=150, $rows=1, $top=0, $height=0) {
    global $db;
    global $smarty;
    global $langs;
    global $GET;

    $items = array();
    $c = 0;
    while (list($key, $val) = each($this->mainmenu)) {
      $sel = FALSE;
      $new = FALSE; 
      if (isset($val['item']) && is_array($val['item'])) {
        // submenu
        $i = 0;
        unset($submenu);
        while (list($key2, $val2) = each($val['item'])) {
          $sel2= FALSE;
          if ($val2['link'] == $page)
            $sel = TRUE;
          if ($_SERVER['QUERY_STRING'] != '' && $val2['link'] == $page."?".$_SERVER['QUERY_STRING'])
           {
            $sel2 = TRUE;
            $sel = TRUE;
           }
          else if ($_SERVER['QUERY_STRING']=='' && $val2['link'] == $page)
             {
               $sel2 = TRUE;
               $sel = TRUE;
             }
        }
      }
      
      // add menu line
  
      if (!isset($val['ignore']) || (isset($val['ignore']) && $val['ignore'] != true)
          || (isset($val['show_if']) && isset($_GET[$val['show_if']]))) {
        unset($item);
        $item['LINK'] = $val['link'];
        if (isset($langs[$val['title']]))
          $item['TITLE'] = $langs[$val['title']];
        else $item['TITLE'] = $val['title'];

        if (isset($val['tip']) && isset($langs[$val['tip']]))
          $item['TIP'] = $langs[$val['tip']];
      
        // highlight selected menu item
        if ($val['link'] == $page || $sel)
          $item['SEL'] = 1;     
        $items[] = $item;
      }
//      $c++;
  //    if (isset($item))

    }
    //echo arrSlice($data);
    // content
    if ($rows == 1)
      $smarty->assign("items", $items);
    if ($rows == 2) {
      $split_items = array_chunk($items, 4);
      $smarty->assign("items1", $split_items[0]);
      $smarty->assign("items2", $split_items[1]);
    }
    $smarty->assign("align", $align);
    $smarty->assign("width", $width);
    $smarty->assign("top", $top);
    $smarty->assign("height", $height);

    if ($rows == 1)
      $template = 'smarty_tpl/menu.smarty';
    else 
      $template = 'smarty_tpl/menu_two.smarty';
    $start = getmicrotime();
    $output = $smarty->fetch($template);    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo $template.($stop-$start);
    return $output;

  }

  function getSubmenu ($page, $menu_index = '', $ajax = false) {
    global $db;
    global $smarty;
    global $langs;
    global $_GET;
    global $auth;

    $main_submenu = array();
    $c = 0;

    if ($menu_index > 0)
      $val = $this->mainmenu[$menu_index];
      $sel = FALSE;
      $new = FALSE;
      unset($submenu);
      if (isset($val['item']) && is_array($val['item'])) {
        // submenu
        $i = 0;
        unset($submenu_item);
        while (list($key2, $val2) = each($val['item'])) {
          $val2['LINK'] = $val2['link'];
          $sel2= FALSE;

          if ($val2['link'] == $page || ($_SERVER['QUERY_STRING'] != '' && $val2['link'] == $page."?".$_SERVER['QUERY_STRING'])
              || ($_SERVER['QUERY_STRING']=='' && $val2['link'] == $page)) {
            $sel2 = TRUE;
            $sel = TRUE;
          }

          if (!$sel2) {
            $output = parse_url($val2['link'], PHP_URL_QUERY);
            if ($output != "") {
              parse_str($output, $output2);
              $all_vars = true;
              foreach ($output2 as $key =>$vrb) {
                if (!isset($_GET[$key]) || $_GET[$key] != $vrb) {
                  $all_vars = false;
                  break;
                }
              }
              if ($all_vars) {
                $sel2 = TRUE;
                $sel = TRUE;
              }
            }
          }

          if ((!isset($val2['ignore']) || !$val2['ignore']
               || (isset($val2['show_if']) && isset($_GET[$val2['show_if']])))
               && (!isset($val2['auth']) || (isset($val2['auth']) && $auth->userOn())) ) {
            if (isset($langs[ $val2['title']]))
              $val2['TITLE'] = $langs[ $val2['title']];
            else $val2['TITLE'] = $val2['title'];
            if (isset($val2['tip']) && isset($langs[$val2['tip']]))
              $val2['TIP'] = $langs[ $val2['tip']];

            $submenu_item = $val2;

            if ($sel2)
    	    {
              $submenu_item['SEL'] = 1;
  	    }

            // if ($sel && $i > 0) {
              $submenu[] = $submenu_item;
            //}
  
            $i++;
          }
          if (isset($val2['new']) && $val2['new']) {
            $new = TRUE;
          }

        }

        if ($i > 0) {
          $main_submenu = $submenu;
        }

      }
      elseif ($val['link'] == $page && isset($val['item'])) {
        // sql query
        $i = 0;
        unset($submenu_item);
        $db->query($val['item']);
        while ($row = $db->nextRow()) {
          // build querystring
          $sel2= FALSE;
          $pre = '?';
          $qs = '';
          while (list($key2, $val2) = each($row)) {
            $qs .= $pre.strtolower($key2).'='.urlencode($val2);
            $pre = '&';
          }
          $submenu_item['LINK'] = $val['link'].$qs;

          if ($temptitle == $row['TITLE']) {
            $sel2 = TRUE;
          }

          if (isset($val['func'])) {
            // apply custom function to title
            eval('$title = '.$val['func']."('".$row['TITLE']."');");
            $submenu_item['TITLE'] = 1;//$title;
          }
          else {
//          $submenu[$i]['TITLE'] = $row['TITLE'];
            if (isset($langs[ $row['TITLE']]))
              $submenu_item['TITLE'] = $langs[ $row['TITLE']];
            else $submenu_item['TITLE'] = $row['TITLE'];
          }

          if ($submenu_item['LINK'] == $page) 
            $sel2 = TRUE;

          if ($sel2)
  	  {
            $submenu_item['SEL'] = $submenu[$i];
	  }
          else 
            {
              $submenu_item['NORM'] = $submenu[$i];
            }
          $submenu[] = $submenu_item;
  
          $i++;
        }     
  
      }
      
    // content
    $smarty->assign("submenu", $main_submenu);
    $start = getmicrotime();
    if ($ajax)
      $output = $smarty->fetch('smarty_tpl/submenu_tabs.smarty');    
    else $output = $smarty->fetch('smarty_tpl/submenu.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/submenu.smarty'.($stop-$start);
    return $output;
  }

  function getMenuFromArray ($menu_array, $variable) {
    global $tpl;
    global $smarty;
    global $langs;

    $main_submenu = array();
    $c = 0;
    $i = 0;      
    while (list($key, $val) = each($menu_array)) {
      $sel = FALSE;
      if (isset($val)) {
        // submenu

        unset($submenu);
        if (isset($_GET[$variable]) && $_GET[$variable] == $key)
          $submenu['SEL'][0]['X'] = 1;

        $submenu['TITLE'] = $val;
        $submenu['LINK'] = url($variable, $key);
        $main_submenu[] = $submenu;
      }
      $i++;      
    }
    //echo arrSlice($data);
    // content
    $smarty->assign("submenu", $main_submenu);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/submenu.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/submenu.smarty'.($stop-$start);
    return $output;
  }

  function getMenuFromQuery ($query, $variable) {
    global $db;
    global $tpl;
    global $_SESSION;

    $data = array();
    $i = 0;      
    
    $db->query($query);
    while ($row = $db->nextRow()) {
        unset($submenu);
        if (isset($_GET[$variable]) && $_GET[$variable] == $row['TYPE'])
          $submenu['SEL'][0]['X'] = 1;

        $submenu['TITLE'] = $row['VALUE'];
        $submenu['LINK'] = url($variable, $row[strtoupper($variable)]);
        $data['SUBMENU'][0]['SUBITEM'][$i] = $submenu;

      $i++;      
    }
    //echo arrSlice($data);
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    $tpl->setTemplateFile('tpl/submenu.tpl.html');
    $tpl->addData($data);
    return $tpl->parse();
  }

}
  
$gamesmenu = array(
    1 => array(
      'link' => 'index.php',
      'title' => 'LANG_MENU_HOME',
    ),
    2 => array(
      'link' => 'f_manager_dashboard.php',
      'title' => 'LANG_MENU_FANTASY_MANAGER',
      'tip' => 'LANG_MENU_HINT_RVM_FANTASY_MANAGER',
      'item' => array(
      -1 => array(
	  'link' => 'f_manager_season_dashboard.php',
          'title' => 'LANG_DASHBOARD_U'
      ),
      0 => array(
	  'link' => 'f_manager.php',
          'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'f_manager_control.php',
        'title' => 'LANG_TEAM_MANAGEMENT_U'
      ),
      2 => array(
        'link' => 'f_manager_standings.php',
        'title' => 'LANG_STANDINGS_U'
      ),
      3 => array(
        'link' => 'f_manager_standings_clans.php',
        'title' => 'LANG_STANDINGS_CLANS_U'
      ),
      4 => array(
        'link' => 'manager_players_tours.php',
        'title' => 'Žaidėjų statistika',
	'ignore' => true
      ),
      5 => array(
        'link' => 'f_manager_tours.php',
        'title' => 'LANG_TOURS_U'
      ),
      6 => array(
        'link' => 'f_manager_league.php?all=y',
        'title' => 'LANG_ALL_LEAGUES_U'
      ),
      7 => array(
        'link' => 'f_manager_league.php?all=n',
        'title' => 'LANG_MY_LEAGUES_U',
	'auth' => true
      ),
      8 => array(
        'link' => 'f_manager_league.php',
        'title' => 'LANG_LEAGUE_U',
	'ignore' => true,
	'show_if' => 'league_id'
      ),
      9 => array(
        'link' => 'f_manager_duk.php',
        'title' => 'LANG_FAQ_U'
      ),
      10 => array(
        'link' => 'f_manager_prizes.php',
        'title' => 'LANG_PRIZES_U'
      ), 
      11 => array(
        'link' => 'forum.php?cat_id=4',
        'title' => 'LANG_FORUM_U'
      ),
      12 => array(
        'link' => 'manager_details.php',
        'title' => '***',
        'ignore' => true 
      ),
      13 => array(
        'link' => 'f_manager_league_control.php',
        'title' => 'LANG_LEAGUE_MANAGEMENT_U'
      ),
      14 => array(
        'link' => 'manager_standings_tours.php',
        'title' => '***',
        'ignore' => true 
      ),
      15 => array(
        'link' => 'f_manager_injury_list.php',
        'title' => 'LANG_INJURY_LIST_U'
      ),
      16 => array(
        'link' => 'f_manager_log.php',
        'title' => 'LANG_LOG_U'
      ),
      17 => array(
        'link' => 'f_manager_user_log.php',
        'title' => 'LANG_PERSONAL_LOG_U'
      ),
      18 => array(
        'link' => 'f_manager_market_stats.php',
        'title' => 'LANG_MARKET_STATS_U'
      ),
      19 => array(
        'link' => 'f_manager_team_statement.php',
        'title' => 'LANG_TEAM_STATEMENT_U'
      ),
      20 => array(
        'link' => 'f_manager_challenges.php',
        'title' => 'LANG_CHALLENGES_U'
      ),
      21 => array(
        'link' => 'f_manager_battles.php',
        'title' => 'LANG_BATTLES_U'
      ),
      22 => array(
        'link' => 'f_manager_report_list.php',
        'title' => 'LANG_REPORTS_U'
      ),
     )
  ),	 
  3 => array(
      'link' => 'rvs_manager_dashboard.php',
      'title' => 'LANG_FANTASY_LEAGUE_U',
      'tip' => 'LANG_MENU_HINT_RVS_FANTASY_MANAGER',
      'item' => array(
      -1 => array(
	  'link' => 'rvs_manager_season_dashboard.php',
          'title' => 'LANG_DASHBOARD_U'
      ),
      0 => array(
          'link' => 'rvs_manager.php',
          'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'rvs_manager_league_control.php',
        'title' => 'LANG_LEAGUE_MANAGEMENT_U'
      ),
      2 => array(
        'link' => 'rvs_manager_duk.php',
        'title' => 'LANG_FAQ_U'
      ),
      3 => array(
        'link' => 'rvs_manager_league.php?all=y',
        'title' => 'LANG_ALL_LEAGUES_U'
      ),
      4 => array(
        'link' => 'rvs_manager_league.php?all=n',
        'title' => 'LANG_MY_LEAGUES_U',
	'auth' => true
      ),

      5 => array(
        'link' => 'rvs_manager_log.php',
        'title' => 'LANG_LEAGUE_LOG_U',
	'ignore' => true,
      ),
      6 => array(
        'link' => 'rvs_manager_user_log.php',
        'title' => 'LANG_PERSONAL_LOG_U',
	'ignore' => true,
      ),
      7 => array(
        'link' => 'rvs_manager_league.php',
        'title' => 'LANG_LEAGUE_U',
	'ignore' => true,
        'show_if' => 'league_id'
      ),
     )
  ),	 
  4 => array(
    'link' => 'f_manager_tournaments_dashboard.php',
    'title' => 'LANG_TOURNAMENTS_U',
    'item' => array(
      -1 => array(
	  'link' => 'f_manager_tournament_dashboard.php',
          'title' => 'LANG_DASHBOARD_U'
      ),
      0 => array(
        'link' => 'f_manager_tournament.php',
        'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'f_manager_tournament_control.php',
        'title' => 'LANG_TOURNAMENT_MANAGEMENT_U'
      ),
      2 => array(
        'link' => 'f_manager_tournaments.php?all=y',
        'title' => 'LANG_ALL_TOURNAMENTS_U'
      ),
      3 => array(
        'link' => 'f_manager_tournaments.php?all=n',
        'title' => 'LANG_MY_TOURNAMENTS_U',
	'auth' => true
      ),
      4 => array(
        'link' => 'f_manager_tournament_duk.php',
        'title' => 'LANG_FAQ_U'
      ),
      5 => array(
        'link' => 'f_manager_tournaments.php',
        'title' => 'LANG_TOURNAMENT_U',
	'ignore' => true,
        'show_if' => 'mt_id'
      ),

    )
  ),

  5 => array(
    'link' => 'ss_manager.php',
    'title' => 'LANG_MENU_ROLE_PLAY_MANAGER',
    'ignore' => true,
    'item' => array(
      0 => array(
        'link' => 'ss_manager.php',
        'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'ss_outside.php',
        'title' => 'LANG_MENU_ROLE_PLAY_MANAGER'
      ),
      2 => array(
        'link' => 'ss_duk.php',
        'title' => 'LANG_FAQ_U'
      ),
      3 => array(
        'link' => 'forum.php?cat_id=5',
        'title' => 'LANG_FORUM_U'
      )
    )
  ),
  6 => array(
    'link' => 'wager_dashboard.php',
    'title' => 'LANG_WAGER_U',
    'item' => array(
      -1 => array(
        'link' => 'wager_season_dashboard.php',
        'title' => 'LANG_DASHBOARD_U'
      ),
      0 => array(
        'link' => 'wager.php',
        'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'wager_control.php',
        'title' => 'LANG_WAGER_CONTROL_U'
      ),
      2 => array(
        'link' => 'wager_challenges.php',
        'title' => 'LANG_CHALLENGES_U'
      ),
      3 => array(
        'link' => 'wager_standings.php',
        'title' => 'LANG_STANDINGS_U'
      ),
      4 => array(
        'link' => 'wager_league.php?all=y',
        'title' => 'LANG_ALL_LEAGUES_U',
	'ignore' => true
      ),
      5 => array(
        'link' => 'wager_league.php?all=n',
        'title' => 'LANG_MY_LEAGUES_U',
        'auth' => true,
	'ignore' => true
      ),
      6 => array(
        'link' => 'wager_league.php',
        'title' => 'LANG_LEAGUE_U',
        'ignore' => true,
        'show_if' => 'league_id'
      ),
      7 => array(
        'link' => 'wager_duk.php',
        'title' => 'LANG_FAQ_U'
      ),
      8 => array(
        'link' => 'wager_prizes.php',
        'title' => 'LANG_PRIZES_U'
      ), 
      9 => array(
        'link' => 'forum.php?cat_id=8',
        'title' => 'LANG_FORUM_U'
      ),
      11 => array(
        'link' => 'wager_league_control.php',
        'title' => 'LANG_LEAGUE_MANAGEMENT_U',
	'ignore' => true
      ),
      14 => array(
        'link' => 'wager_log.php',
        'title' => 'LANG_LOG_U'
      ),
      15 => array(
        'link' => 'wager_user_log.php',
        'title' => 'LANG_PERSONAL_LOG_U',
        'auth' => true
      )
    )
  ),
  7 => array(
    'link' => 'bracket_dashboard.php',
    'title' => 'LANG_ARRANGER_U',
    'item' => array(
      -1 => array(
        'link' => 'bracket_season_dashboard.php',
        'title' => 'LANG_DASHBOARD_U'
      ),
      0 => array(
        'link' => 'bracket.php',
        'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'bracket_control.php',
        'title' => 'LANG_ARRANGER_CONTROL_U'
      ),
      2 => array(
        'link' => 'bracket_standings.php',
        'title' => 'LANG_STANDINGS_U'
      ),
      3 => array(
        'link' => 'bracket_tours.php',
        'title' => 'LANG_TOURS_U'
      ),
      4 => array(
        'link' => 'bracket_league.php?all=y',
        'title' => 'LANG_ALL_LEAGUES_U'
      ),
      5 => array(
        'link' => 'bracket_league.php?all=n',
        'title' => 'LANG_MY_LEAGUES_U',
	'auth' => true
      ),
      6 => array(
        'link' => 'bracket_league.php',
        'title' => 'LANG_LEAGUE_U',
	'ignore' => true,
	'show_if' => 'league_id'
      ),
      7 => array(
        'link' => 'bracket_duk.php',
        'title' => 'LANG_FAQ_U'
      ),
      8 => array(
        'link' => 'bracket_prizes.php',
        'title' => 'LANG_PRIZES_U'
      ), 
      9 => array(
        'link' => 'forum.php?cat_id=11',
        'title' => 'LANG_FORUM_U'
      ),
      11 => array(
        'link' => 'bracket_league_control.php',
        'title' => 'LANG_LEAGUE_MANAGEMENT_U'
      ),
      14 => array(
        'link' => 'bracket_log.php',
        'title' => 'LANG_LOG_U'
      ),
      15 => array(
        'link' => 'bracket_user_log.php',
        'title' => 'LANG_PERSONAL_LOG_U',
	'auth' => true
      )
    )
  ),

  8 => array(
    'link' => 'solo_manager_dashboard.php',
    'title' => 'LANG_SOLO_MANAGER_U',
    'item' => array(
      -1 => array(
        'link' => 'solo_manager_season_dashboard.php',
        'title' => 'LANG_DASHBOARD_U'
      ),
      0 => array(
        'link' => 'solo_manager.php',
        'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'solo_manager_control.php',
        'title' => 'LANG_TEAM_MANAGEMENT_U'
      ),
      2 => array(
        'link' => 'solo_manager_standings.php',
        'title' => 'LANG_STANDINGS_U'
      ),
      3 => array(
        'link' => 'solo_manager_tours.php',
        'title' => 'LANG_TOURS_U'
      ),
      4 => array(
        'link' => 'solo_manager_league.php?all=y',
        'title' => 'LANG_ALL_LEAGUES_U'
      ),
      5 => array(
        'link' => 'solo_manager_league.php?all=n',
        'title' => 'LANG_MY_LEAGUES_U',
	'auth' => true
      ),
      6 => array(
        'link' => 'solo_manager_league.php',
        'title' => 'LANG_LEAGUE_U',
	'ignore' => true,
	'show_if' => 'league_id'
      ),
      7 => array(
        'link' => 'solo_manager_duk.php',
        'title' => 'LANG_FAQ_U'
      ),
      8 => array(
        'link' => 'solo_manager_prizes.php',
        'title' => 'LANG_PRIZES_U'
      ), 
      9 => array(
        'link' => 'forum.php?cat_id=11',
        'title' => 'LANG_FORUM_U'
      ),
      11 => array(
        'link' => 'solo_manager_league_control.php',
        'title' => 'LANG_LEAGUE_MANAGEMENT_U'
      ),
      14 => array(
        'link' => 'solo_manager_log.php',
        'title' => 'LANG_LOG_U'
      ),
      15 => array(
        'link' => 'solo_manager_user_log.php',
        'title' => 'LANG_PERSONAL_LOG_U',
	'auth' => true
      )
    )
  ),

);


$commmenu= array(
  1 => array(
    'link' => 'news.php',
    'title' => 'LANG_MENU_NEWS',
    'item' => array(
      0 => array(
        'link' => 'news.php',
        'title' => 'LANG_ALL_NEWS_U'
      ),

      1 => array(
        'link' => 'news.php?genre=1',
        'title' => 'LANG_MENU_NEWS'
      ),
      
      2 => array(
        'link' => 'news.php?genre=3',
        'title' => 'LANG_ANNOUNCEMENTS_U'
      ),
      
      3 => array(
        'link' => 'video.php',
        'title' => 'LANG_VIDEO_NEWS_U'
      ),

      4 => array(
        'link' => 'blogs.php',
        'title' => 'LANG_BLOGS_U'
      ),
      
    )
  ),
  
  2 => array(
    'link' => 'forum.php',
    'title' => 'LANG_MENU_FORUM',
    'item' => array(
      0 => array(
        'link' => 'forum.php',
        'title' => 'LANG_MENU_FORUM'
      ),
      2 => array(
        'link' => 'forum_settings.php',
        'title' => 'LANG_SETTINGS_U'
      )
    )
  ),
  3 => array(
    'link' => 'survey.php?all=y',
    'title' => 'LANG_SURVEYS_U',
    'item' => array(
      0 => array(
        'link' => 'survey.php?all=y',
        'title' => 'LANG_SURVEYS_U'
      ),
      1 => array(
        'link' => 'survey.php',
        'title' => 'LANG_SURVEY_U',
	'ignore' => true,
	'show_if' => 'survey_id'
      )
    )
  ),
  4 => array(
    'link' => 'ratings.php',
    'title' => 'LANG_RATINGS_U',
    'item' => array(
      0 => array(
        'link' => 'ratings_descr.php',
        'title' => 'LANG_DESCRIPTION_U'
      ),
      1 => array(
        'link' => 'ratings.php',
        'title' => 'LANG_RATING_COMMON_U'
      ),
      2 => array(
        'link' => 'ratings_sport.php',
        'title' => 'LANG_RATING_SPORT_U'
      ),
      3 => array(
        'link' => 'ratings_tournament.php',
        'title' => 'LANG_RATING_TOURNAMENT_U'
      ),
      4 => array(
        'link' => 'ratings_league_owner.php',
        'title' => 'LANG_RATING_LEAGUE_OWNER_U'
      )

    )
  ),
  5 => array(
    'link' => 'shop.php',
    'title' => 'LANG_SHOP_U',
    'ignore' => true,
    'item' => array(
      0 => array(
        'link' => 'shop.php',
        'title' => 'LANG_STOCK_U'
      ),
      1 => array(
        'link' => 'basket.php',
        'title' => 'LANG_BASKET_U'
      ),
      2 => array(
        'link' => 'shop_checkout.php',
        'title' => 'LANG_BASKET_U',
        'ignore' => 'true'
      ),
      3 => array(
        'link' => 'shop_user_orders.php',
        'title' => 'LANG_ORDERS_U'
      ),
      4 => array(
        'link' => 'forum.php?forum_id=27',
        'title' => 'LANG_FORUM_U'
      ),
    )
  ),
  6 => array(
    'link' => 'clubs.php',
    'title' => 'LANG_CLUBS_U',
    'item' => array(
      0 => array(
        'link' => 'clubs_rules.php',
        'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'clubs.php?all=y',
        'title' => 'LANG_CLUBS_U'
      ),
      2 => array(
        'link' => 'clubs.php',
        'title' => 'LANG_CLUB_U',
	'ignore' => true,
	'show_if' => 'club_id'
      ),
      3 => array(
        'link' => 'clubs_events.php',
        'title' => 'LANG_CLUBS_EVENTS_U'
      ),
      4 => array(
        'link' => 'club_event_add.php',
        'title' => 'LANG_CREATE_CLUB_EVENT_U',
	'ignore' => true,
      )
    )
  ),
  7 => array(
    'link' => 'clans.php',
    'title' => 'LANG_CLANS_U',
    'item' => array(
      0 => array(
        'link' => 'clans_rules.php',
        'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'clans.php?all=y',
        'title' => 'LANG_CLANS_U'
      ),
      2 => array(
        'link' => 'clans.php',
        'title' => 'LANG_CLAN_U',
	'ignore' => true,
	'show_if' => 'clan_id'
      ),
      3 => array(
        'link' => 'clan_management.php',
        'title' => 'LANG_CLAN_MANAGEMENT_U',
	'auth' => true
      ),
    )
  ),

  14 => array(
    'link' => 'contacts.php',
    'title' => 'LANG_MENU_CONTACT',
    'ignore' => true,
  ),
  15 => array(
    'link' => 'about_us.php',
    'title' => 'LANG_MENU_ABOUT_US',
    'ignore' => true,
  ),

  16 => array(
    'link' => 'user_management_panel.php',
    'title' => 'Sport City',
    'ignore' => true,
    'item' => array(
      0 => array(
        'link' => 'user_management_panel.php',
        'title' => 'LANG_DASHBOARD_U'
      ),
      1 => array(
        'link' => 'user_management_panel_comments.php',
        'title' => 'LANG_COMMENT_PATH_U'
      ),
      2 => array(
        'link' => 'user_management_panel_contents.php',
        'title' => 'LANG_CONTENT_PATH_U',
      ),
      3 => array(
        'link' => 'user_management_panel_games.php',
        'title' => 'LANG_GAMES_U',
	'ignore' =>true
      ),
      4 => array(
        'link' => 'user_subscription_manager.php',
        'title' => 'LANG_SUBSCRIPTION_U'
      ),
      5 => array(
        'link' => 'user_credits_manager.php',
        'title' => 'LANG_CREDITS_U'
      ),
      6 => array(
        'link' => 'user_management_panel_manager.php',
        'title' => 'LANG_MANAGER_U'
      ),
      7 => array(
        'link' => 'user_management_panel_settings.php',
        'title' => 'LANG_SETTINGS_U'
      ),
      8 => array(
        'link' => 'user_management_panel_groups.php',
        'title' => 'LANG_GROUPS_U'
      ),
      9 => array(
        'link' => 'user_management_panel_clans.php',
        'title' => 'LANG_CLANS_U'
      ),
      10 => array(
        'link' => 'change_password.php',
        'title' => 'LANG_CHANGE_PASSWORD_U'
      )

    )
  ),

  17 => array(
    'link' => 'moderator_panel.php',
    'title' => 'LANG_SPORT_CITY_U',
    'ignore' => true,
    'item' => array(
      0 => array(
        'link' => 'moderator_panel.php',
        'title' => 'LANG_COMMENTS_U'
      ),
      1 => array(
        'link' => 'shop_orders.php',
        'title' => 'LANG_INCOMPLETE_ORDERS_U'
      ),
    )
  )

);


$external_menu= array(
  1 => array(
      'link' => 'f_manager_dashboard.php',
      'title' => 'LANG_MENU_FANTASY_MANAGER',
      'item' => array(
        -2 => array(
          'link' => 'external_index.php',
          'title' => 'LANG_MENU_FANTASY_MANAGER',
	  'ignore' =>true
        ),
        -1 => array(
	  'link' => 'f_manager_season_dashboard.php',
            'title' => 'LANG_DASHBOARD_U'
        ),
        0 => array(
	  'link' => 'f_manager.php',
            'title' => 'LANG_RULES_U'
        ),
        1 => array(
          'link' => 'f_manager_control.php',
          'title' => 'LANG_TEAM_MANAGEMENT_U'
        ),
        2 => array(
          'link' => 'f_manager_standings.php',
          'title' => 'LANG_STANDINGS_U'
        ),
        3 => array(
          'link' => 'manager_players_tours.php',
          'title' => 'Žaid�-j�3 statistika',
	'ignore' => true
        ),
        4 => array(
          'link' => 'f_manager_tours.php',
          'title' => 'LANG_TOURS_U'
        ),
        5 => array(
          'link' => 'f_manager_league.php?all=y',
          'title' => 'LANG_ALL_LEAGUES_U'
        ),
        6 => array(
          'link' => 'f_manager_league.php?all=n',
          'title' => 'LANG_MY_LEAGUES_U',
	'auth' => true
        ),
        7 => array(
          'link' => 'f_manager_league.php',
          'title' => 'LANG_LEAGUE_U',
	'ignore' => true,
	'show_if' => 'league_id'
        ),
        8 => array(
          'link' => 'f_manager_duk.php',
          'title' => 'LANG_FAQ_U'
        ),
        9 => array(
          'link' => 'f_manager_prizes.php',
          'title' => 'LANG_PRIZES_U'
        ), 
        10 => array(
          'link' => 'forum.php?cat_id=4',
          'title' => 'LANG_FORUM_U'
        ),
        11 => array(
          'link' => 'manager_details.php',
          'title' => '***',
          'ignore' => true 
        ),
        12 => array(
          'link' => 'f_manager_league_control.php',
          'title' => 'LANG_LEAGUE_MANAGEMENT_U'
        ),
        13 => array(
          'link' => 'manager_standings_tours.php',
          'title' => '***',
          'ignore' => true 
        ),
        14 => array(
          'link' => 'f_manager_injury_list.php',
          'title' => 'LANG_INJURY_LIST_U'
        ),
        15 => array(
          'link' => 'f_manager_log.php',
          'title' => 'LANG_LOG_U'
        ),
        16 => array(
          'link' => 'f_manager_user_log.php',
          'title' => 'LANG_PERSONAL_LOG_U'
        ),
        17 => array(
          'link' => 'f_manager_market_stats.php',
          'title' => 'LANG_MARKET_STATS_U'
        ),
        18 => array(
          'link' => 'f_manager_team_statement.php',
          'title' => 'LANG_TEAM_STATEMENT_U'
        ),
        19 => array(
          'link' => 'f_manager_challenges.php',
          'title' => 'LANG_CHALLENGES_U'
        ),
        20 => array(
          'link' => 'f_manager_battles.php',
          'title' => 'LANG_BATTLES_U'
        ),
        21 => array(
          'link' => 'f_manager_report_list.php',
          'title' => 'LANG_REPORTS_U'
        ),
      ),
  ),
  2 => array(
      'link' => 'rvs_manager_dashboard.php',
      'title' => 'LANG_FANTASY_LEAGUE_U',
      'item' => array(
        -1 => array(
	  'link' => 'rvs_manager_season_dashboard.php',
            'title' => 'LANG_DASHBOARD_U'
        ),
        0 => array(
            'link' => 'rvs_manager.php',
            'title' => 'LANG_RULES_U'
        ),
        1 => array(
          'link' => 'rvs_manager_league_control.php',
          'title' => 'LANG_LEAGUE_MANAGEMENT_U'
        ),
        2 => array(
          'link' => 'rvs_manager_duk.php',
          'title' => 'LANG_FAQ_U'
        ),
        3 => array(
          'link' => 'rvs_manager_league.php?all=y',
          'title' => 'LANG_ALL_LEAGUES_U'
        ),
        4 => array(
          'link' => 'rvs_manager_league.php?all=n',
          'title' => 'LANG_MY_LEAGUES_U',
	'auth' => true
        ),
  
        5 => array(
          'link' => 'rvs_manager_log.php',
          'title' => 'LANG_LEAGUE_LOG_U',
	'ignore' => true,
        ),
        6 => array(
          'link' => 'rvs_manager_user_log.php',
          'title' => 'LANG_PERSONAL_LOG_U',
	'ignore' => true,
        ),
        7 => array(
          'link' => 'rvs_manager_league.php',
          'title' => 'LANG_LEAGUE_U',
	'ignore' => true,
          'show_if' => 'league_id'
        ),
     )
  ),
  3 => array(
    'link' => 'f_manager_tournaments_dashboard.php',
    'title' => 'LANG_TOURNAMENTS_U',
    'item' => array(
      -1 => array(
	  'link' => 'f_manager_tournament_dashboard.php',
          'title' => 'LANG_DASHBOARD_U'
      ),
      0 => array(
        'link' => 'f_manager_tournament.php',
        'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'f_manager_tournament_control.php',
        'title' => 'LANG_TOURNAMENT_MANAGEMENT_U'
      ),
      2 => array(
        'link' => 'f_manager_tournaments.php?all=y',
        'title' => 'LANG_ALL_TOURNAMENTS_U'
      ),
      3 => array(
        'link' => 'f_manager_tournaments.php?all=n',
        'title' => 'LANG_MY_TOURNAMENTS_U',
	'auth' => true
      ),
      4 => array(
        'link' => 'f_manager_tournament_duk.php',
        'title' => 'LANG_FAQ_U'
      ),
      5 => array(
        'link' => 'f_manager_tournaments.php',
        'title' => 'LANG_TOURNAMENT_U',
	'ignore' => true,
        'show_if' => 'mt_id'
      ),

    )

  ),
  4 => array(
    'link' => 'solo_manager_dashboard.php',
    'title' => 'LANG_SOLO_MANAGER_U',
    'item' => array(
      -1 => array(
        'link' => 'solo_manager_season_dashboard.php',
        'title' => 'LANG_DASHBOARD_U'
      ),
      0 => array(
        'link' => 'solo_manager.php',
        'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'solo_manager_control.php',
        'title' => 'LANG_TEAM_MANAGEMENT_U'
      ),
      2 => array(
        'link' => 'solo_manager_standings.php',
        'title' => 'LANG_STANDINGS_U'
      ),
      3 => array(
        'link' => 'solo_manager_tours.php',
        'title' => 'LANG_TOURS_U'
      ),
      4 => array(
        'link' => 'solo_manager_league.php?all=y',
        'title' => 'LANG_ALL_LEAGUES_U'
      ),
      5 => array(
        'link' => 'solo_manager_league.php?all=n',
        'title' => 'LANG_MY_LEAGUES_U',
	'auth' => true
      ),
      6 => array(
        'link' => 'solo_manager_league.php',
        'title' => 'LANG_LEAGUE_U',
	'ignore' => true,
	'show_if' => 'league_id'
      ),
      7 => array(
        'link' => 'solo_manager_duk.php',
        'title' => 'LANG_FAQ_U'
      ),
      8 => array(
        'link' => 'solo_manager_prizes.php',
        'title' => 'LANG_PRIZES_U'
      ), 
      9 => array(
        'link' => 'forum.php?cat_id=11',
        'title' => 'LANG_FORUM_U'
      ),
      11 => array(
        'link' => 'solo_manager_league_control.php',
        'title' => 'LANG_LEAGUE_MANAGEMENT_U'
      ),
      14 => array(
        'link' => 'solo_manager_log.php',
        'title' => 'LANG_LOG_U'
      ),
      15 => array(
        'link' => 'solo_manager_user_log.php',
        'title' => 'LANG_PERSONAL_LOG_U',
	'auth' => true
      )
    )
  ),

  5 => array(
    'link' => 'wager_dashboard.php',
    'title' => 'LANG_WAGER_U',
    'item' => array(
      -1 => array(
        'link' => 'wager_season_dashboard.php',
        'title' => 'LANG_DASHBOARD_U'
      ),
      0 => array(
        'link' => 'wager.php',
        'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'wager_control.php',
        'title' => 'LANG_WAGER_CONTROL_U'
      ),
      2 => array(
        'link' => 'wager_challenges.php',
        'title' => 'LANG_CHALLENGES_U'
      ),
      3 => array(
        'link' => 'wager_standings.php',
        'title' => 'LANG_STANDINGS_U'
      ),
      4 => array(
        'link' => 'wager_league.php?all=y',
        'title' => 'LANG_ALL_LEAGUES_U'
      ),
      5 => array(
        'link' => 'wager_league.php?all=n',
        'title' => 'LANG_MY_LEAGUES_U',
        'auth' => true
      ),
      6 => array(
        'link' => 'wager_league.php',
        'title' => 'LANG_LEAGUE_U',
        'ignore' => true,
        'show_if' => 'league_id'
      ),
      7 => array(
        'link' => 'wager_duk.php',
        'title' => 'LANG_FAQ_U'
      ),
      8 => array(
        'link' => 'wager_prizes.php',
        'title' => 'LANG_PRIZES_U'
      ), 
      9 => array(
        'link' => 'forum.php?cat_id=8',
        'title' => 'LANG_FORUM_U'
      ),
      11 => array(
        'link' => 'wager_league_control.php',
        'title' => 'LANG_LEAGUE_MANAGEMENT_U'
      ),
      14 => array(
        'link' => 'wager_log.php',
        'title' => 'LANG_LOG_U'
      ),
      15 => array(
        'link' => 'wager_user_log.php',
        'title' => 'LANG_PERSONAL_LOG_U',
        'auth' => true
      )
    )

  ),
  6 => array(
    'link' => 'forum.php',
    'title' => 'LANG_MENU_FORUM',
    'item' => array(
      0 => array(
        'link' => 'forum.php',
        'title' => 'LANG_MENU_FORUM'
      ),
      2 => array(
        'link' => 'forum_settings.php',
        'title' => 'LANG_SETTINGS_U'
      )
    )

  ),
  7 => array(
    'link' => 'clans.php',
    'title' => 'LANG_CLANS_U',
    'item' => array(
      0 => array(
        'link' => 'clans_rules.php',
        'title' => 'LANG_RULES_U'
      ),
      1 => array(
        'link' => 'clans.php?all=y',
        'title' => 'LANG_CLANS_U'
      ),
      2 => array(
        'link' => 'clans.php',
        'title' => 'LANG_CLAN_U',
	'ignore' => true,
	'show_if' => 'clan_id'
      ),
      3 => array(
        'link' => 'clan_management.php',
        'title' => 'LANG_CLAN_MANAGEMENT_U',
	'auth' => true
      ),
    )
  ),
);

//$submenu = getSubmenu(scriptName($PHP_SELF));

?>