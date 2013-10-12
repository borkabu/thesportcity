<?php
/*
===============================================================================
events.inc.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - generate event list data

TABLES USED: 
  - BASKET.EVENTS

STATUS:
  - [STAT:INPRGS] in progress

TODO:
  - [TODO:PUBVAR] main functionality
===============================================================================
*/

class LoginBox extends Box{

  function getLoginBox ($auth, $external=false, $facebook=false) {
    global $smarty;
    global $db;
    global $_SESSION;
    global $pm_folders;
    global $langs;
    global $facebook_user;
    global $user_profile;

    if (isset($_SESSION['externally_logged']))
      $smarty->assign("externally_logged", 1);
    
    // content
    if ($auth->userOn()) { 
      $auth->refreshEssensials();
      $user = new User($auth->getUserId());
      $logged = $_SESSION['_user'];
      $logged['COMMENT_TRUST'] = round($logged['COMMENT_TRUST'], 2);
      $logged['CONTENT_TRUST'] = round($logged['CONTENT_TRUST'], 2);
      $logged['INBOX'] = $pm_folders[1];
      $logged['SENT'] = $pm_folders[2];
      $pm = new PM();    
      $unread =  $pm->getUnreadMessagesNumber(1);
      $logged['UNREAD'] = $unread;
      if ($unread > 0)
        $logged['UNREAD_PMS'] = 1;
      $trust = new Trust();
      $hint = new Hint();
      $logged['COMMENT_TRUST_LEVEL'] = $trust->getCommentTrustLevel($_SESSION['_user']['COMMENT_TRUST']);
      $comment_hint = $hint->getHint(1, $logged['COMMENT_TRUST_LEVEL']);
      if ($comment_hint != "")
        $logged['COMMENT_TRUST_HINT']['DESCR'] = $comment_hint;
      $logged['CONTENT_TRUST_LEVEL'] = $trust->getCommentTrustLevel($_SESSION['_user']['CONTENT_TRUST']);
      $content_hint = $hint->getHint(1, $logged['CONTENT_TRUST_LEVEL']);
      if ($content_hint != "")
        $logged['CONTENT_TRUST_HINT']['DESCR'] = $content_hint;

      $logged['CONTENT_TRUST_LEVEL'] = $trust->getContentTrustLevel($_SESSION['_user']['CONTENT_TRUST']);
      if ($logged['COMMENT_TRUST_LEVEL'] >=5 ||
	  $logged['CONTENT_TRUST_LEVEL'] >=5)
	$logged['MODERATOR_PANEL']['CC_MANAGEMENT_PANEL'] = 1;

      if ($auth->isGroupModerator()) {
        $logged['MODERATOR_PANEL']['GROUP_MANAGEMENT_PANEL'] = 1;
      }      
      
      if ($user->getClubs() > 0) {
        $c = 0;
        $clubs = '';
        foreach ($_SESSION['_user']['CLUBS'] as $club) {
          $clubs[] = $club; 
        }
	$logged['CLUBS'] = $clubs;
      }

      if ($user->getClan() > 0) {
	$logged['CLAN'] = $_SESSION['_user']['CLAN'];
      }

      $comment_quote = $trust->getCommentTrustLevelQuote();
      if($comment_quote > 0) {
          $logged['COMMENT_TRUST_QUOTE']['QUOTE_LEFT'] = $trust->getCommentTrustLevelQuoteLeft();
      }
      $content_quote = $trust->getContentTrustLevelQuote();
      if($content_quote > 0) {
          $logged['CONTENT_TRUST_QUOTE']['QUOTE_LEFT'] = $trust->getContentTrustLevelQuoteLeft();
      }
      $logged['COMMENT_TRUST_NEXT_LEVEL'] = round($trust->getCommentTrustNextLevel($_SESSION['_user']['COMMENT_TRUST']), 2);
      $logged['CONTENT_TRUST_NEXT_LEVEL'] = round($trust->getContentTrustNextLevel($_SESSION['_user']['CONTENT_TRUST']), 2);
      $logged['LANGUAGE']=inputLanguages('lang', $this->lang);  

      $permissions = new ForumPermission();
      if ($permissions->canChat()) {
        $chat = new Chat();
        $logged['CHAT']['USERS'] = $chat->getUsersCount();
      }

      $credit_log = new CreditsLog(); 
      $log_entry = $credit_log->getCreditLogLastItem($auth->getUserId());
      if ($log_entry != '')
        $logged['CREDIT_LOG'] = $log_entry;
      $smarty->assign("logged", $logged);
    }
    else {
      $notlogged['LANGUAGE']=inputLanguages('lang', $this->lang);  
      $smarty->assign("notlogged", $notlogged);
    }
 
    if ($external) {
      $smarty->assign("external_user", $_SESSION['external_user']);
      if ($auth->getExternalMessage() != "")
        $smarty->assign("external_message", $auth->getExternalMessage());      
      if ($auth->getExternalError() != "")
        $smarty->assign("external_error", $auth->getExternalError());      
    }
    if (!isset($_SESSION['externally_logged']) && $external) {
//      $auth->reset();
    }

    if (isset($facebook_user) && !empty($facebook_user)) {
       // logged in
      $_SESSION['external_user']['USER_NAME'] = $user_profile['username'];
      $_SESSION['external_user']['SOURCE'] = 'facebook';
      $smarty->assign("external_user", $_SESSION['external_user']);
      $smarty->assign("facebook_logged", 1);
    }
   
    if ($external) 
      $template_file = 'smarty_tpl/bar_login_external.smarty';
    else 
      $template_file = 'smarty_tpl/bar_login.smarty';

    $start = getmicrotime();
    $output = $smarty->fetch($template_file);    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo $template_file.($stop-$start);
    return $output;
  } 
}

?>