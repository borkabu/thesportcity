<?php
// 
// ============================================================================
// const.inc.php
// ----------------------------------------------------------------------------
// Constant definition file
// ============================================================================
// 

// keyword type constants
define('KEYWORD_USER', 10);
define('KEYWORD_TEAM', 15);
define('KEYWORD_TOURNAMENT', 20);
define('KEYWORD_ORGANIZATION', 30);
define('KEYWORD_NEWS', 100);
define('KEYWORD_GAME', 110);
define('KEYWORD_EVENT', 120);
define('KEYWORD_VIDEO', 130);

// news group constants
define('NEWS_DOMESTIC', 'L'); // domestic news
define('NEWS_WORLD', 'W');    // worldwide news

// SENDLOG.TYPE constants
define('SEND_NEWS', 0);     // news
define('SEND_SCHEDULE', 1); // schedule
define('SEND_SMS', 2); // schedule
define('SEND_WAP_SMS', 3); // schedule

// game type constants
// NOTE: values will affect sort results
define('GAME_FINAL16', 10);
define('GAME_FINAL2', 20);
define('GAME_SMALLFINAL2', 25);
define('GAME_FINAL4', 30);
define('GAME_SMALLFINAL4', 35);
define('GAME_11_12', 36);
define('GAME_9_10', 37);
define('GAME_FINAL8', 40);
define('GAME_SMALLFINAL8', 45);
define('GAME_FINAL', 50);
define('GAME_THIRDPLACE', 55);
define('GAME_GROUP', 60);
define('GAME_SUBGROUP', 70);
define('GAME_SUPERFINAL', 80);
define('GAME_RELEGATION', 85);
define('GAME_FIFTHPLACE', 90);
define('GAME_SEVENTHPLACE', 92);
define('GAME_NINTHPLACE', 94);
define('GAME_11PLACE', 96);
define('GAME_13PLACE', 98);
define('GAME_14PLACE', 100);
define('GAME_FRIENDLY', 110);

// team type constants
// NOTE: values will affect sort results
define('TEAM_CLUB', 10);
define('TEAM_NATIONAL', 20);

// user type constants
define('USER_REGULAR', 0); // regular site users
define('USER_INFO', 1); // user is subject to info on the site

// membership user type constants
// NOTE: values will affect sort results
define('USER_PLAYER', 40);
define('USER_TRAINER', 30);
define('USER_ADMINISTRATION', 10);
define('USER_OFFICIAL', 20);

// player position constants
// NOTE: values will affect sort results
define('POSITION_CENTER', 30);
define('POSITION_FORWARD', 20);
define('POSITION_GUARD', 10);

// mtournament type constants
// NOTE: values will affect sort results
define('TOURNAMENT_LEAGUE', 20);
define('TOURNAMENT_TOURNAMENT', 40);
define('TOURNAMENT_CHAMPIONSHIP', 10);
define('TOURNAMENT_OLYPICS', 30);


// --- DB CLASS CONSTANTS -----------------------------------------------------

// db constants for QueryBuilder
define('DB_QUERY_SELECT', 'select');   // SELECT operation
define('DB_QUERY_INSERT', 'insert');   // INSERT operation
define('DB_QUERY_UPDATE', 'update');   // UPDATE operation
define('DB_QUERY_DELETE', 'delete');   // DELETE operation
define('DB_QUERY_REPLACE', 'replace');   // REPLACE operation

// --- TEMPLATE CLASS CONSTANTS -----------------------------------------------

// cache level constants
define('TPL_CACHE_ALL', 101);
define('TPL_CACHE_OBJECTS', 102);
define('TPL_CACHE_NOTHING', 103);

// cache type constants
define('TPL_CACHE_DB', 201);
define('TPL_CACHE_FILE', 202);

// warning level constants
define('TPL_WARN_IGNORE', 301);
define('TPL_WARN_CRITICAL', 302);
define('TPL_WARN_ALL', 303);

// --- FORM CLASS CONSTANTS ---------------------------------------------------

// general form constants
define('FORM_CHECKED', TRUE); // used to indicate that this input is 'checked'

// form input type constants
define('FORM_INPUT_TEXT', 'text');
define('FORM_INPUT_HIDDEN', 'hidden');
define('FORM_INPUT_PASSWORD', 'password');
define('FORM_INPUT_RADIO', 'radio');
define('FORM_INPUT_CHECKBOX', 'checkbox');
define('FORM_INPUT_TEXTAREA', 'textarea');
define('FORM_INPUT_SELECT', 'select');
define('FORM_INPUT_IMAGE', 'image');
define('FORM_INPUT_BUTTON', 'button');
define('FORM_INPUT_SUBMIT', 'submit');
define('FORM_INPUT_RESET', 'reset');

define('FORM_INPUT_DATE', 'date');
define('FORM_INPUT_DATETIME', 'datetime');

// --- ERROR CONSTANTS --------------------------------------------------------

// DB: db error constants
define('ERR_DB', 200);            // general db class error
define('ERR_DB_TYPE', 201);       // invalid or not specified db server type
define('ERR_DB_SERVER', 202);     // invalid or not specified db server host
define('ERR_DB_USER', 203);       // invalid or not specified db username
define('ERR_DB_PASSWORD', 204);   // invalid or not specified db password
define('ERR_DB_DBASE', 205);      // invalid or not specified database
define('ERR_DB_CONNECT', 210);    // cannot connect to a server
define('ERR_DB_PCONNECT', 211);   // persistent connections not supported
define('ERR_DB_CLOSE', 212);      // could not close the connection
define('ERR_DB_QUERY', 213);      // error executing SQL query
define('ERR_DB_FREE', 214);       // error freeing resultset
define('ERR_DB_RS_MISSING', 220); // no resultset
define('ERR_DB_FEATURE', 250);    // feature not supported

// TEMPLATE: cache error constants
define('ERR_TPL_CACHE_DB', 501);
define('ERR_TPL_CACHE_FILE', 502);
define('ERR_TPL_CACHE_TTL', 503);

// TEMPLATE: parsing error constants
define('ERR_TPL_PARSE', 510);
define('ERR_TPL_PARSE_DATA', 511);
define('ERR_TPL_PARSE_OBJECT', 512);
define('ERR_TPL_PARSE_INPUT', 513);

// TEMPLATE: general template error constants
define('ERR_TPL_DATA', 520);
define('ERR_TPL_FILE', 521);

// FORM: form error constants
define('ERR_FORM_FILE', 601);   // unable to open form definition file
define('ERR_FORM_DATA', 602);   // erroneous or missing form data
define('ERR_FORM_INPUT', 603);  // invalid form input type

// LANGUAGE: language error constants
define('ERR_LANG_FILE', 701);   // unable to open language file
define('ERR_LANG_NOFILE', 702); // no language file was loaded
define('ERR_LANG_DATA', 703);   // erroneuos or missing language data
define('ERR_LANG_KEY', 704);    // no such key


// --- MENU CONSTANTS --------------------------------------------------------

define('MENU_USERS', 1000);    
define('MENU_USERS_EDIT', 1020);    
define('MENU_NEWS', 2000);
define('MENU_NEWS_MAIN_EDIT', 2012);
define('MENU_NEWS_MAIN_SEND', 2013);
define('MENU_BLOG', 2015);
define('MENU_BLOG_EDIT', 2016);
define('MENU_NEWS_PORTAL_EVENTS', 2020);
define('MENU_NEWS_RL', 2040);
define('MENU_NEWS_ONLINE', 2050);
define('MENU_NEWS_VIDEO', 2045);
define('MENU_MENU', 2060);
define('MENU_RESEARCH', 2070);
define('MENU_GAMES', 3000);
define('MENU_GAMES_SCHED_EDIT', 3012);
define('MENU_GAMES_SCHED_SEND', 3013);
define('MENU_GAMES_RESULTS', 3020);
define('MENU_GAMES_RESULTS_EDIT', 3022);
define('MENU_GAMES_RESULTS_SEND', 3023);
define('MENU_ACTIONS', 4000);
define('MENU_ACTIONS_TOTO', 4010);
define('MENU_ACTIONS_TOTO_EDIT', 4012);
define('MENU_ACTIONS_CONTEST', 4030);
define('MENU_ACTIONS_CONTEST_EDIT', 4032);
define('MENU_ACTIONS_SMS', 4040);
define('MENU_ACTIONS_MANAGER', 4050);
define('MENU_ACTIONS_WAGER', 4060);
define('MENU_ACTIONS_ARRANGER', 4070);
define('MENU_BASKET', 5000);
define('MENU_BASKET_SEASONS', 5100);
define('MENU_ORGS', 6000);
define('MENU_PARAMETERS', 7000);
define('MENU_PARAMETERS_IMAGE', 7050);
define('MENU_PARAMETERS_PASSWORD', 7070);
define('MENU_DISCUSSIONS', 8000);
define('MENU_STATS', 9000);
define('MENU_CLUBS', 10000);
define('MENU_ADMINS', 11000);
define('MENU_ADMINS_EDIT', 11010);
define('MENU_PARTNERS', 12000);
define('MENU_PARTNERS_TOPSPORT', 12010);
define('MENU_PARTNERS_ORAKULAS', 12020);
define('MENU_PARTNERS_RADIOCENTRAS', 12030);
define('MENU_FORUM', 13000);
define('MENU_SPORT_CITY', 14000);
define('MENU_SHOP', 15000);

?>