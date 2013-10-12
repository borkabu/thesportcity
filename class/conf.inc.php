<?php 

// 
// ============================================================================
// conf.inc.php
// ----------------------------------------------------------------------------
// A universal PHP application configuration file
// ============================================================================
// 
date_default_timezone_set('Europe/London'); 

// Application home directory
$conf_home_dir    = 'c:\\xampp\\htdocs\\thesportcity\\' ; //'/var/www/krepsinis.net/'; 
$conf_chat_data_private_path    = 'c:\\xampp\\htdocs\\chat\\data\\private' ;
//$conf_home_dir    = '/home/linweb01/t/thesportcity.net/user/htdocs/';$conf_site_url    = 'http://www.thesportcity.net/';
$conf_admin_name  = 'the sport city team';
$conf_admin_email = 'admin@thesportcity.net';
$conf_order_email = 'admin@thesportcity.net';
include_once($conf_home_dir.'class/const.inc.php'); // constants
$conf_chat_dir = 'phpfreechat';
$root_prefix = '/thesportcity';

$forums = array ( 'NEWS' => 5,
  	          'MANAGER_LEAGUES' => 7,
		  'MANAGER_TOURNAMENT' => 12,
		  'SURVEYS' => 13,
		  'VIDEO' => 18,
  	          'WAGER_LEAGUES' => 19,
		  'BLOGS' => 21,
		  'CLUBS' => 25,
		  'CLUBS_EVENTS' => 26,
  	          'ARRANGER_LEAGUES' => 29,
  	          'MANAGER_BATTLES' => 31,
  	          'RVS_MANAGER_LEAGUES' => 33,
  	          'SOLO_MANAGER_LEAGUES' => 45
	        );


$delivery_price_credits = array (
                 1 => 70,
                 2 => 400);

$delivery_price_euro = array (
                 1 => 2,
                 2 => 8);
// DEBUG
$debug = TRUE;

// Settings
$page_size = 40;                  // default page size for listings
define('PAGE_SIZE', $page_size);  // constant for same purposes
$search_prox = 15; // the proximity value for search relevancy calculations


// 200 - DB CLASS
$conf_db_type     = 'mysql';      // type of the db server
$conf_db_server   = array('localhost'); //'localhost';  // host of the db server
$conf_db_user     = 'root';           // username to use
$conf_db_password = 'borka';           // password
$conf_db_dbase    = 'sportcity';           // database to connect to

/*$conf_db_server   = array('213.171.200.62'); //'localhost';  // host of the db server
$conf_db_user     = 'sportcityman';           // username to use
$conf_db_password = 'sport23city';           // password
$conf_db_dbase    = 'sportcity';           // database to connect to
  */
$conf_db_oracle_enhance = TRUE; // use enhanced mode for oracle
                                // might slow down script if large amounts
                                // of data is being selected

// 500 - TEMPLATE CLASS
$conf_tpl_cache_ttl             = 30;                 // TTL of cache in minutes
$conf_tpl_cache_level           = TPL_CACHE_NOTHING;      // level of the cache
$conf_tpl_cache_type            = TPL_CACHE_FILE;     // cache to file or db
$conf_tpl_cache_use_instance    = TRUE;               // use page instance
$conf_tpl_cache_use_noncachable = FALSE;              // allow noncachable items
$conf_tpl_cache_path            = $conf_home_dir.'cache/';           // path to directory to 
                                                      //  store cache files to
$conf_tpl_cache_database        = '';                 // name of the database 
                                                      //  for cache
$conf_tpl_cache_table           = '';                 // name of the table for 
                                                      //  cache
$conf_tpl_warn_level            = TPL_WARN_ALL;  // warning level
$conf_tpl_max_include_levels    = 10;                 // maximum number of 
                                                      //  include levels
$conf_tpl_process_inputs        = TRUE;               // process form inputs
$conf_tpl_use_language_class    = FALSE;               // use language class for
                                                      //  unpopulated items
$conf_tpl_allow_global          = TRUE;               // allow usage of global
                                                      //  tags <_TPL:XXX>

// 600 - FORM CLASS
$conf_form_file = $conf_home_dir.'lib/ss.form.php'; // full or ralative path to form definition file

// 700 - LANGUAGE CLASS
$conf_lang      = 'en'; // default language
$conf_lang_file = $conf_home_dir.'lib/krepsinis.lang.php'; // full or ralative path to language file

$phpbb = 'phpbb3_';
$flood_protection = 300;

session_set_cookie_params(1800); 
session_start();
if (isset($_COOKIE[session_name()])) {
  setcookie(session_name(), $_COOKIE[session_name()], time()+1800, '/');
}  

 if (!empty($_POST['lang']))
   $_SESSION["_lang"] = $_POST['lang'];
 else if (!empty($_GET['lang_id']))
   $_SESSION["_lang"] = $_GET['lang_id'];

 if (!empty($_SESSION["_lang"]))
   include($conf_home_dir.'class/ss_lang_'.$_SESSION["_lang"].'.inc.php');
 else {
    if (!empty($_COOKIE['lang']))
      $_SESSION["_lang"] = $_COOKIE['lang'];
    else $_SESSION["_lang"] = $conf_lang;
    include($conf_home_dir.'class/ss_lang_'.$_SESSION["_lang"].'.inc.php');
 }

 setcookie('lang', $_SESSION["_lang"], time()+3600*24*365);

 while (list($key, $val) = each($langs)) {
    $data[$key] = $val;
 }


 $clients = array( 'eurofootball.lt' => array('ip' => '127.0.0.1',
					      'path' => 'http://localhost/ef',
					      'sports' => '2',
					      'encoding' => 'Windows-1257',
					      'logo' => 'http://www.eurofootball.lt/nw_images/eurofootball.png',
					      'small_logo' => 'http://www.thesportcity.net/img/sites/eurofootballlt.gif',
					      'external_source_jquery' => 'eurofootball.lt')
                  );	
?>