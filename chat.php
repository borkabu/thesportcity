<?php
ini_set('display_errors', 1);
//error_reporting (E_ERROR);
/*
===============================================================================
index.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - first page of a portal

TABLES USED: 
  - BASKET.NEWS
  - BASKET.EVENTS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
// includes
include('class/conf.inc.php');
include('class/func.inc.php');
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

// user session
include('class/ss_const.inc.php');

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');

// --- build content data -----------------------------------------------------
//else 

$content = '';
$permissions = new ForumPermission();
if ($auth->userOn() && $permissions->canChat()) {
  $tsc_chat = new Chat();
  $general_channels = $tsc_chat->getChannelsList();
  $private_leagues = array() ; //$tsc_chat->getAllUsersPrivateLeagues();
  $c = 0;
  $channels = array();
  foreach($general_channels as $channel) {
    $data['GENERAL_CHANNELS'][0]['GENERAL_CHANNEL'][$c]['CHANNEL_TITLE'] = $channel;
    $data['GENERAL_CHANNELS'][0]['GENERAL_CHANNEL'][$c]['CHANNEL_LINK'] = $channel;
    array_push($channels, $channel);
    $c++;
  }
/*  foreach($private_leagues as $channel) {
    $data['PRIVATE_LEAGUES_CHANNELS'][$channel['SEASON_TITLE']]['PRIVATE_LEAGUES_CHANNEL'][$c] = $channel;
    $data['PRIVATE_LEAGUES_CHANNELS'][$channel['SEASON_TITLE']]['SEASON_TITLE'] = $channel['SEASON_TITLE'];
    array_push($channels, $channel['CHANNEL_LINK']);
    $c++;
  }*/
  $data['CURRENT_CHANNEL'] = $tsc_chat->getCurrentChannels();
  require_once "/phpfreechat/src/phpfreechat.class.php";
  $params = array();
  $params["title"] = "TheSportCity.Net ".$langs['LANG_CHAT_U'];
  $params["nick"] = $_SESSION['_user']['USER_NAME'];
//  $params["refresh_delay"] = 60000;
  $params["timeout"] = 600000;
  $params["frozen_nick"] = true;
  $params["channels"] = array($data['CURRENT_CHANNEL'][0]); //$channels
//  $params["frozen_channels"] = $channels;
  $params["isadmin"] = $auth->isAdmin(); // do not use it on production servers ;)
  $params["serverid"] = "TheSportCity.Net"; // calculate a unique id for this chat
  $params["nickmeta"] = $tsc_chat->getNickMetadata();
  $params["language"] = $_SESSION['I18N'];
  $params["time_offset"] = 60*60*6;
  $params["dyn_params"] = array("language", "channels", "frozen_channels", "time_offset");
//  $params["theme"]       = "msn";
  //$params["debug"] = true;

  $params["container_type"] = "Mysql";
  $params["container_cfg_mysql_database"] = $conf_db_dbase;
  $params["container_cfg_mysql_username"] = $conf_db_user;
  $params["container_cfg_mysql_password"] = $conf_db_password;

//print_r($params["nickmeta"]);
  $chat = new phpFreeChat( $params );

//  $data['LANGUAGE']=inputLanguages('lang', $_SESSION['_lang']);  
  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/chat.tpl.html');
  $tpl->addData($data);
 
  $content=$tpl->parse();

//$db->showquery=true;

}
else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_NOT_LOGGED_IN');
}

  // include common header
include('inc/top_chat.inc.php');

// content
echo $content;
if ($auth->userOn() && $permissions->canChat()) {
  $chat->printChat();

/*echo "<script>
Event.observe(window, 'load', function() {
  if (pfc_isready) {
    pfc.sendRequest('/join ".$data['CURRENT_CHANNEL'][0]."');
//alert(1);
  }
//alert(1);
});
</script>";*/

}

// include common footer
include('inc/bot_chat.inc.php');

include('class/db_close.inc.php');
?>