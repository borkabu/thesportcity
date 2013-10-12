<?php
/*
===============================================================================
adm_menu.php
-------------------------------------------------------------------------------
Generates admin menu
===============================================================================
*/

function getSecurityItemsList () { 
  global $menu;
  $data = array();
  $data = getSecurityItemLine ($menu, $data, 0);
  $data = array_unique($data);
  sort($data);
  $i = 0;
  foreach ($data as $value) 
  {     
    $data1[$i]['ITEM'] = $value;
    $i++;
  }
  return $data1; 
}

function getSecurityItems () {
  global $menu;
  $data = array();
  $data = getSecurityItemLine ($menu, $data, 0);
  $data = array_unique($data);
  sort($data);
  $i = 0;
  foreach ($data as $value) 
  {     
    $data1[$value]['ITEM'] = $value;
    $data1[$value]['LEGEND'] = getSecCode($value);
    $i++;
  }
  return $data1; 

}

function getSecurityItemLine ($menu, $data, $line = 0) {
  while (list($key, $val) = each($menu)) {
    if (isset($val['item']) && is_array($val['item'])) {
      $data = getSecurityItemLine ($val['item'], $data, $line + 1); 
    }
   if (isset($val['security']))
     array_push($data, $val['security']);
  }
 return $data;
}

// wrapper function for getMenuLine
function getMenu ($page) {
  global $menu;
  $data = array();
  getMenuLine ($page, $menu, $data, 0);
  return $data;
}

function getMenuLine ($page, $menu, &$data, $line = 0) {
  global $_SESSION; 
  global $langs;
  $c = 0;
  $sel = FALSE;
  $child_sel = FALSE;
  $mline = array();
  while (list($key, $val) = each($menu)) {
    if (isset($val['item']) && is_array($val['item'])) {
      if (getMenuLine($page, $val['item'], $data, $line + 1)) {
        $sel = TRUE;
        $child_sel = TRUE;
      }
    }
//     echo $_security[$val['security']]."_";
//   echo $_admin[$val['security']];

   if (!empty($_SESSION["_admin"][$val['security']]) && strcmp($_SESSION["_admin"][$val['security']], 'NA') != 0)
   {
    $mline[$c]['LINK'] = $val['link'];
    if (isset($langs[$val['title']]))
       $mline[$c]['TITLE'] = $langs[$val['title']];
    else $mline[$c]['TITLE'] = $val['title'];

    if ($val['link'] == $page || $child_sel) {
      $mline[$c]['SEL'][0]['X'] = 1;
      $sel = TRUE;
      $child_sel = FALSE;
    }
    $c++;
   }
  }
  if ($sel || $line == 0) {
    $data[0]["LINE_$line"][0]['ITEM'] = $mline;
  }
  return $sel;
}

$menu = array(
  0 => array(
    'link' => 'user.php',
    'title' => 'LANG_USERS_U',
    'security' => MENU_USERS,
    'item' => array(
      0 => array(
        'link' => 'user.php',
        'title' => 'LANG_LIST_U',
        'security' => MENU_USERS
      ),
      1 => array(
        'link' => 'user_edit.php',
        'title' => 'LANG_ADD_EDIT_U',
        'security' => MENU_USERS_EDIT
      ),
      2 => array(
        'link' => 'user_wap.php',
        'title' => 'WAP LANG_LIST_U',
        'security' => MENU_USERS
      ),
      3 => array(
        'link' => 'award.php',
        'title' => 'APDAVANOJIMAI',
        'security' => MENU_USERS,
        'item' => array(
          0 => array(
            'link' => 'award.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_USERS
          ),
          
          1 => array(
            'link' => 'award_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_USERS_EDIT
          )
        )

      ),
      4 => array(
        'link' => 'clans.php',
        'title' => 'LANG_CLANS_U',
        'security' => MENU_USERS
      ),

    )
  ),
  
  1 => array(
    'link' => 'news.php',
    'title' => 'LANG_NEWS_U',
    'security' => MENU_NEWS,
    'item' => array(
      0 => array(
        'link' => 'news.php',
        'title' => 'LANG_NEWS_U',
        'security' => MENU_NEWS,
        'item' => array(
          0 => array(
            'link' => 'news.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_NEWS
          ),
          
          1 => array(
            'link' => 'news_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_NEWS_MAIN_EDIT
          )
        )
      ),

      1 => array(
        'link' => 'tasks.php',
        'title' => 'UZHDUOTIS',
        'security' => MENU_NEWS,
        'item' => array(
          0 => array(
            'link' => 'tasks.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_NEWS
          ),
          
          1 => array(
            'link' => 'task_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_NEWS_MAIN_EDIT
          )          
        )
      ),
      
      2 => array(
        'link' => 'source.php',
        'title' => 'ŠALTINIAI',
        'security' => MENU_NEWS,
        'item' => array(
          0 => array(
            'link' => 'source.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_NEWS
          ),
          
          1 => array(
            'link' => 'source_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_NEWS
          )
        )
      ),
      
      3 => array(
        'link' => 'event.php',
        'title' => 'DATOS',
        'security' => MENU_NEWS,
        'item' => array(
          0 => array(
            'link' => 'event.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_NEWS
          ),
          
          1 => array(
            'link' => 'event_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_NEWS
          )
        )
      ),

      4 => array(
        'link' => 'run_line.php',
        'title' => 'EILUTE',
         'security' => MENU_NEWS_RL
      ),

      7 => array(
        'link' => 'pages.php',
        'title' => 'LANG_MENU_PAGES_U',
        'security' => MENU_MENU,
        'item' => array(
          0 => array(
            'link' => 'pages.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_MENU
          ),
          
          1 => array(
            'link' => 'pages_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_MENU
          )
        )
      ),
      9 => array(
        'link' => 'transfer.php',
        'title' => 'TRANSFERAI/GANDAI',
        'security' => MENU_NEWS,
        'item' => array(
          0 => array(
            'link' => 'transfer.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_NEWS
          ),
          
          1 => array(
            'link' => 'transfer_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_NEWS
          )
        )
      ),
      10 => array(
        'link' => 'portal_events.php',
        'title' => 'PORTALO IVYKIAI',
        'security' => MENU_NEWS,
        'item' => array(
          0 => array(
            'link' => 'portal_events.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_NEWS_PORTAL_EVENTS
          ),
          
          1 => array(
            'link' => 'portal_events_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_NEWS_PORTAL_EVENTS
          )
        )
      ),
      12 => array(
        'link' => 'blogs.php',
        'title' => 'BLOGAI',
        'security' => MENU_BLOG,
        'item' => array(
          0 => array(
            'link' => 'blogs.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_BLOG
          ),
          
          1 => array(
            'link' => 'blogs_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_BLOG_EDIT
          ),

          2 => array(
            'link' => 'problogs.php',
            'title' => 'PRO BLOG LANG_LIST_U',
            'security' => MENU_BLOG
          ),
          
          3 => array(
            'link' => 'problogs_edit.php',
            'title' => 'PRO BLOG LANG_ADD_EDIT_U',
            'security' => MENU_BLOG_EDIT
          ),

          4 => array(
            'link' => 'blogs_pro_edit.php',
            'title' => 'PRO LANG_ADD_EDIT_U',
            'security' => MENU_BLOG_EDIT,
            'ignore' => true
          )          

        )
      ),
      13 => array(
        'link' => 'video.php',
        'title' => 'Video',
        'security' => MENU_NEWS,
        'item' => array(
          0 => array(
            'link' => 'video.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_NEWS_VIDEO
          ),
          
          1 => array(
            'link' => 'video_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_NEWS_VIDEO
          )
        )
      ),

    )
  ),
  
  2 => array(
    'link' => 'sched.php',
    'title' => 'LANG_MATCHES_U',
    'security' => MENU_GAMES,
    'item' => array(
      0 => array(
        'link' => 'sched.php',
        'title' => 'LANG_MATCHES_U',
        'security' => MENU_GAMES,
        'item' => array(
          0 => array(
            'link' => 'sched.php',
            'security' => MENU_GAMES,
            'title' => 'LANG_LIST_U'
          ),
          
          1 => array(
            'link' => 'sched_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_GAMES_SCHED_EDIT
          ),

          2 => array(
            'link' => 'sched_load.php',
            'title' => 'PAKRAUTI',
            'security' => MENU_GAMES_SCHED_EDIT
          ),
        )
      ),
      1 => array(
        'link' => 'sched_races.php',
        'title' => 'LANG_RACES_U',
        'security' => MENU_GAMES,
        'item' => array(
          0 => array(
            'link' => 'sched_races.php',
            'security' => MENU_GAMES,
            'title' => 'LANG_LIST_U'
          ),
          
          1 => array(
            'link' => 'sched_races_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_GAMES_SCHED_EDIT
          ),
          2 => array(
            'link' => 'sched_races_load.php',
            'title' => 'PAKRAUTI',
            'security' => MENU_GAMES_SCHED_EDIT
          ),
          
        )
      ),
      
      2 => array(
        'link' => 'res.php',
        'title' => 'LANG_RESULTS_U',
        'security' => MENU_GAMES_RESULTS,
        'item' => array(
          0 => array(
            'link' => 'res.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_GAMES_RESULTS
          ),
          
          1 => array(
            'link' => 'res_edit.php',
            'title' => 'LANG_ADD_U',
            'security' => MENU_GAMES_RESULTS_EDIT
          ),
        )
      ),
    )
  ),
  
  3 => array(
    'link' => 'manager_season.php',
    'title' => 'LANG_GAMES_U',
    'security' => MENU_ACTIONS,
    'item' => array(     
      0 => array(
        'link' => 'manager_season.php',
        'title' => 'LANG_MANAGER_U',
        'security' => MENU_ACTIONS_MANAGER,
        'item' => array(
          0 => array(
            'link' => 'manager_season.php',
            'title' => 'LANG_SEASONS_U',
            'security' => MENU_ACTIONS_MANAGER
          ),
          
          1 => array(
            'link' => 'manager_season_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_ACTIONS_MANAGER
          ),

          2 => array(
            'link' => 'manager_price.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_ACTIONS_MANAGER
          ),
          
          3 => array(
            'link' => 'manager_price_edit.php',
            'title' => 'LANG_ADD_U',
            'security' => MENU_ACTIONS_MANAGER
          ),

          4 => array(
            'link' => 'manager_subseasons_edit.php',
            'title' => 'LANG_ADD_U',
            'security' => MENU_ACTIONS_MANAGER
          ),

          5 => array(
            'link' => 'manager_season_tours_edit.php',
            'title' => 'LANG_ADD_U',
            'security' => MENU_ACTIONS_MANAGER
          )

        )
      ),   
      1 => array(
        'link' => 'manager_tournament_season.php',
        'title' => 'LANG_TOURNAMENTS_U',
        'security' => MENU_ACTIONS_MANAGER,
        'item' => array(
          0 => array(
            'link' => 'manager_tournament_season.php',
            'title' => 'LANG_SEASONS_U',
            'security' => MENU_ACTIONS_MANAGER
          ),
          
          1 => array(
            'link' => 'manager_tournament_season_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_ACTIONS_MANAGER
          )
        )
      ),
      2 => array(
        'link' => 'wager_season.php',
        'title' => 'LANG_WAGER_U',
        'security' => MENU_ACTIONS_WAGER,
        'item' => array(
          0 => array(
            'link' => 'wager_season.php',
            'title' => 'LANG_SEASONS_U',
            'security' => MENU_ACTIONS_WAGER
          ),
          
          1 => array(
            'link' => 'wager_season_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_ACTIONS_WAGER
          ),
          2 => array(
            'link' => 'wager_load.php',
            'title' => 'LANG_LOAD_U',
            'security' => MENU_ACTIONS_WAGER
          ),
          3 => array(
            'link' => 'wager_games.php',
            'title' => 'LANG_EDIT_U',
            'security' => MENU_ACTIONS_WAGER
          )
        )
      ),
      3 => array(
        'link' => 'bracket_season.php',
        'title' => 'LANG_BRACKETS_U',
        'security' => MENU_ACTIONS_ARRANGER,
        'item' => array(
          0 => array(
            'link' => 'bracket_season.php',
            'title' => 'LANG_SEASONS_U',
            'security' => MENU_ACTIONS_ARRANGER
          ),
          
          1 => array(
            'link' => 'bracket_season_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_ACTIONS_ARRANGER
          ),
        )
      ),
    )
  ),
  
  4 => array(
    'link' => 'ppl.php',
    'title' => 'LANG_SPORT_U',
    'security' => MENU_BASKET,
    'item' => array(
      0 => array(
        'link' => 'ppl.php',
        'title' => 'LANG_PLAYERS_U',
        'security' => MENU_BASKET,
        'item' => array(
          0 => array(
            'link' => 'ppl.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_BASKET
          ),
          
          1 => array(
            'link' => 'ppl_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_BASKET
          ),

          2 => array(
            'link' => 'ppl_edit_members.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_BASKET
          ),

          3 => array(
            'link' => 'ppl_nt_load.php',
            'title' => 'PAKRAUTI NT',
            'security' => MENU_BASKET
          ),
          4 => array(
            'link' => 'ppl_club_load.php',
            'title' => 'PAKRAUTI KLUBA',
            'security' => MENU_BASKET
          ),

        )
      ),
      
      1 => array(
        'link' => 'team.php',
        'title' => 'LANG_TEAMS_U',
        'security' => MENU_BASKET,
        'item' => array(
          0 => array(
            'link' => 'team.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_BASKET
          ),
          
          1 => array(
            'link' => 'team_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_BASKET
          )
        )
      ),
      
      2 => array(
        'link' => 'league.php',
        'title' => 'LANG_LEAGUES_U',
        'security' => MENU_BASKET,
        'item' => array(
          0 => array(
            'link' => 'league.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_BASKET
          ),
          
          1 => array(
            'link' => 'league_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_BASKET
          )
        )
      ),
      
      3 => array(
        'link' => 'season.php',
        'title' => 'LANG_SEASONS_U',
        'security' => MENU_BASKET_SEASONS,
        'item' => array(
          0 => array(
            'link' => 'season.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_BASKET_SEASONS
          ),
          
          1 => array(
            'link' => 'season_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_BASKET_SEASONS
          ),

          2 => array(
            'link' => 'season_teams_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_BASKET_SEASONS
          )

        )
      ),
    )
  ),
  
  6 => array(
    'link' => 'country.php',
    'title' => 'LANG_PARAMETERS_U',
    'security' => MENU_PARAMETERS,
    'item' => array(
      0 => array(
        'link' => 'country.php',
        'title' => 'LANG_COUNTRY_U',
        'security' => MENU_PARAMETERS,
        'item' => array(
          0 => array(
            'link' => 'country.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_PARAMETERS
          ),
          
          1 => array(
            'link' => 'country_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_PARAMETERS
          )
        )
      ),
      
      2 => array(
        'link' => 'cat.php',
        'title' => 'LANG_CATEGORIES_U',
        'security' => MENU_PARAMETERS,
        'item' => array(
          0 => array(
            'link' => 'cat.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_PARAMETERS
          ),
          
          1 => array(
            'link' => 'cat_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_PARAMETERS
          )
        )
      ),
      
      3 => array(
        'link' => 'hint.php',
        'title' => 'LANG_HINTS_U',
        'security' => MENU_PARAMETERS,
        'item' => array(
          0 => array(
            'link' => 'hint.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_PARAMETERS
          ),
          
          1 => array(
            'link' => 'hint_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_PARAMETERS
          )
        )
      ),

      4 => array(
        'link' => 'help.php',
        'title' => 'PAGALBA',
        'security' => MENU_PARAMETERS,
        'item' => array(
          0 => array(
            'link' => 'help.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_PARAMETERS
          ),
          
          1 => array(
            'link' => 'help_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_PARAMETERS
          )
        )
      ),
           
      5 => array(
        'link' => 'image.php',
        'title' => 'LANG_IMAGES_U',
        'security' => MENU_PARAMETERS_IMAGE,
        'item' => array(
          0 => array(
            'link' => 'image.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_PARAMETERS_IMAGE
          ),
          
          1 => array(
            'link' => '#upload',
            'title' => 'LANG_ADD_U',
            'security' => MENU_PARAMETERS_IMAGE
          )
        )
      ),
      6 => array(
        'link' => 'banners.php',
        'title' => 'LANG_BANNERS_U',
        'security' => MENU_PARAMETERS,
        'item' => array(
          0 => array(         
            'link' => 'banners.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_PARAMETERS
          ),
          
          1 => array(
            'link' => 'banner_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_PARAMETERS
          )
        )
      ),
      7 => array(
        'link' => 'newsletter.php',
        'title' => 'LANG_NEWSLETTERS_U',
        'security' => MENU_PARAMETERS,
        'item' => array(
          0 => array(
            'link' => 'newsletter.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_PARAMETERS
          ),
          
          1 => array(
            'link' => 'newsletter_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_PARAMETERS
          )
        )
      ),
      10 => array(
        'link' => 'password.php',
        'title' => 'SLAPTAŽODIS',
        'security' => MENU_PARAMETERS_PASSWORD
      ),
    )
  ),
 
  8 => array(
    'link' => 'admin.php',
    'title' => 'LANG_ADMINISTRATION_U',
    'security' => MENU_ADMINS,
    'item' => array(
      0 => array(
        'link' => 'admin.php',
        'security' => MENU_ADMINS,
        'title' => 'LANG_LIST_U'
      ),
      1 => array(
        'link' => 'admin_edit.php',
        'security' => MENU_ADMINS,
        'title' => 'LANG_ADD_U'
      ),
      2 => array(
        'link' => 'admin_group.php',
        'security' => MENU_ADMINS,
        'title' => 'GRUPES',
        'item' => array(
           0 => array(
             'link' => 'admin_group.php',
             'security' => MENU_ADMINS,
             'title' => 'LANG_LIST_U'
            ),
           1 => array(
             'link' => 'admin_group_edit.php',
             'security' => MENU_ADMINS,
             'title' => 'LANG_ADD_EDIT_U'
            ),
           2 => array(
             'link' => 'admin_group_members_edit.php',
             'security' => MENU_ADMINS,
             'title' => 'PRIDĖTI NARIUS'
            )
         )  
      )
    )
  ),

  9 => array(
    'link' => 'survey.php',
    'title' => 'LANG_SURVEYS_U',
    'security' => MENU_ACTIONS,
    'item' => array(
      0 => array(
        'link' => 'survey.php',
        'title' => 'LANG_SURVEYS_U',
        'security' => MENU_ACTIONS,
        'item' => array(
          0 => array(
            'link' => 'survey.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_ACTIONS
          ),
          
          1 => array(
            'link' => 'survey_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_ACTIONS
          ),
          
          2 => array(
            'link' => 'survey_answers_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_ACTIONS
          )
          
        )
      )
    )
  ),    

  11 => array(
    'link' => 'ss_battles.php',
    'title' => 'SC',
    'security' => MENU_SPORT_CITY,
    'item' => array(
      0 => array(
        'link' => 'ss_battles.php',
        'title' => 'LANG_BATTLES_U',
        'security' => MENU_SPORT_CITY,
        'item' => array(
          0 => array(
            'link' => 'ss_battles.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_SPORT_CITY
          )         
        )
      ),
      1 => array(
        'link' => 'ss_item.php',
        'title' => 'LANG_ITEMS_U',
        'security' => MENU_SPORT_CITY,
        'item' => array(
          0 => array(
            'link' => 'ss_item.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_SPORT_CITY
          ),
          1 => array(
            'link' => 'ss_item_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_SPORT_CITY
          )
        )
      ),
      2 => array(
        'link' => 'ss_item_type.php',
        'title' => 'LANG_ITEM_TYPES_U',
        'security' => MENU_SPORT_CITY,
        'item' => array(
          0 => array(
            'link' => 'ss_item_type.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_SPORT_CITY
          ),         
          1 => array(
            'link' => 'ss_item_type_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_SPORT_CITY
          )         

        )
      ),
      3 => array(
        'link' => 'ss_skill.php',
        'title' => 'LANG_SKILLS_U',
        'security' => MENU_SPORT_CITY,
        'item' => array(
          0 => array(
            'link' => 'ss_skill.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_SPORT_CITY
          ),
          1 => array(
            'link' => 'ss_skill_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_SPORT_CITY
          )         
        )
      )
    )
  ),
  
  12 => array(
    'link' => 'forum_cat.php',
    'title' => 'LANG_MENU_FORUM',
    'security' => MENU_FORUM,
    'item' => array(
      0 => array(
        'link' => 'forum_cat.php',
        'title' => 'LANG_CATEGORIES_U',
        'security' => MENU_FORUM,
        'item' => array(
          0 => array(
            'link' => 'forum_cat.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_FORUM,
          ),
          1 => array(
            'link' => 'forum_cat_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_FORUM,
          )

        )
      ),
      1 => array(
        'link' => 'forum.php',
        'title' => 'LANG_MENU_FORUM',
        'security' => MENU_FORUM,
        'item' => array(
          0 => array(
            'link' => 'forum.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_FORUM,
          ),
          1 => array(
            'link' => 'forum_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_FORUM,
          )

        )
      ),
      2 => array(
        'link' => 'pm_folder.php',
        'title' => 'LANG_FOLDERS_U',
        'security' => MENU_FORUM,
        'item' => array(
          0 => array(
            'link' => 'pm_folder.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_FORUM,
          ),
          1 => array(
            'link' => 'pm_folder_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_FORUM,
          )
        )
      ),
      3 => array(
        'link' => 'forum_group.php',
        'title' => 'LANG_GROUPS_U',
        'security' => MENU_FORUM,
        'item' => array(
          0 => array(
            'link' => 'forum_group.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_FORUM,
          ),
          1 => array(
            'link' => 'forum_group_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_FORUM,
          )
        )
      )

    )
  ),
  
  13 => array(
    'link' => 'shop_stock.php',
    'title' => 'LANG_MENU_SHOP',
    'security' => MENU_SHOP,
    'item' => array(
      0 => array(
        'link' => 'shop_stock.php',
        'title' => 'LANG_STOCK_U',
        'security' => MENU_SHOP,
        'item' => array(
          0 => array(
            'link' => 'shop_stock.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_SHOP,
          ),
          1 => array(
            'link' => 'shop_stock_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_SHOP,
          )
        )
      ),
      1 => array(
        'link' => 'shop_attributes.php',
        'title' => 'LANG_ATTRIBUTES_U',
        'security' => MENU_SHOP,
        'item' => array(
          0 => array(
            'link' => 'shop_attributes.php',
            'title' => 'LANG_LIST_U',
            'security' => MENU_SHOP,
          ),
          1 => array(
            'link' => 'shop_attributes_edit.php',
            'title' => 'LANG_ADD_EDIT_U',
            'security' => MENU_SHOP,
          )
        )
      )  
    )
  )
);
?>