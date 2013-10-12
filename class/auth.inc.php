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

class Auth {
  var $message;
  var $external_message;
  var $external_error;
 
  function Auth($reset=false) {
    $this->message = '';
    $this->external_message = '';
    $this->external_error = '';
    if ($reset)
      $this->reset();
    else
      $this->logout();
    $this->login();
    $this->login_ss();
  }

  function getMessage() {
    return $this->message;
  }

  function getExternalMessage() {
    return $this->external_message;
  }

  function getExternalError() {
    return $this->external_error;
  }

  function reset() {
    global $_SESSION;
    global $_COOKIE;

    $lang = $_SESSION["_lang"];
    $_SESSION = array();

    if (isset($_COOKIE[session_name()])) {
      setcookie(session_name(), '', time()-42000, '/', '.thesportcity.net');
    }  
    $_COOKIE['ssuser'] = '';
    setcookie('ssuser');
    session_unset();
    session_destroy();
    session_set_cookie_params(1800, '/', '.thesportcity.net'); 
    session_start();
    $_SESSION["_lang"] = $lang;
  }

  function logout() {
   global $_GET;
   global $_POST;
   global $_SESSION;
   global $_COOKIE;
   global $fbAppId;

   if (isset($_GET['logoff']) || isset($_POST['logout'])) {
      $this->message = 'ERROR_GOODBYE';
      $lang = $_SESSION["_lang"];
      $_SESSION = array();

      if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-42000, '/', '.thesportcity.net');
      }  
      $_COOKIE['ssuser'] = '';
      setcookie('ssuser', "", time() - 3600, '/', '.thesportcity.net');
      session_unset();
      session_destroy();
      session_set_cookie_params(1800, '/', '.thesportcity.net'); 
//      session_set_cookie_params(1800, '/', ''); 
      session_start();
      $_SESSION["_lang"] = $lang;

      unset($_SESSION['fb_'.$fbAppId.'_code']);   
      unset($_SESSION['fb_'.$fbAppId.'_access_token']);   
      unset($_SESSION['fb_'.$fbAppId.'_user_id']);   
        
   }
   if (isset($_GET['kill_external']))
     unset($_SESSION['external_user']);
  }

  function login() {
    global $_SESSION;
    global $_POST;
    global $_COOKIE;
    global $db;
    global $langs;
    global $external_authentication;

    if (isset($_SESSION['external_authentication'])) {
      $external_authentication = $_SESSION['external_authentication'];
    }

if ($external_authentication['SOURCE'] == 'facebook')
 $db->showquery=true;
    if (isset($external_authentication)) {
      if (isset($external_authentication['USER_NAME']) && !isset($_SESSION["linked_user"])) {
        $_SESSION['externally_logged'] = 1;
        // find linked user
//print_r($external_authentication);
        $sql = "SELECT U.USER_ID, U.USER_NAME from users U, linked_users L 
                    where U.USER_ID=L.USER_ID AND LINKED_USER_NAME='".$external_authentication['USER_NAME']."' 
					and SOURCE='".$external_authentication['SOURCE']."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $external_user = $row['USER_ID'];          
          $_SESSION['external_user'] = $external_authentication;
          $_SESSION['external_user']['TSC_USER_NAME'] = $row['USER_NAME'];
        } else {
          // no linked user found
          // try automatic linking by email
          $db->select("users", "USER_ID, USER_NAME", "EMAIL='".$external_authentication['USER_EMAIL']."'");
          if ($row2 = $db->nextRow()) {
            if (!empty($external_authentication['USER_NAME'])) {
              unset($sdata);
              $this->linkUser($row2['USER_ID'], $external_authentication['USER_NAME'], $external_authentication['SOURCE']);
              $external_user = $row2['USER_ID'];          
              $_SESSION['external_user'] = $external_authentication;
              $_SESSION['external_user']['TSC_USER_NAME'] = $row2['USER_NAME'];
              $this->external_message = $langs['LANG_MESSAGE_EXTERNAL_USER_LINKED_U'];
            }
          } else {
            // cannot link users
            // offer go on as a new user, or login as existing tsc user
 	    unset($_SESSION['external_user']);
            $_SESSION['external_user'] = $external_authentication;
            $_SESSION['external_user']['TSC_USER_NAME'] = $external_authentication['USER_NAME']."@".$external_authentication['SOURCE'];
            if (isset($_POST['propagate'])) {
              // create account, and link it
              unset($sdata);
              $password = gen_rand_string(0, 8);
	      $sdata['USER_NAME'] = "'".$_SESSION['external_user']['TSC_USER_NAME']."'";
	      $sdata['EMAIL'] = "'".$external_authentication['USER_EMAIL']."'";
	      $sdata['FIRST_NAME'] = "'".$external_authentication['USER_NAME']."'";
	      $sdata['LAST_NAME'] = "'".$external_authentication['USER_NAME']."'";
	      $sdata['PASSWORD'] = "'".md5($password)."'";
	      $sdata['REG_DATE'] = 'NOW()';
	      $sdata['LAST_LOGIN'] = 'NOW()';
              $real_ip = $_SERVER["REMOTE_ADDR"];
	      $sdata['IP'] = "'".$real_ip."'";
	      $sdata['REG_IP'] = "'".$real_ip."'";
	      $sdata['COUNTRY'] = 3;
	      $sdata['ACTIVE'] = "'Y'";
	      $sdata['EMAIL_VERIFIED'] = "'Y'";
              $db->insert('users', $sdata);

              $id = $db->id();
              unset($sdata);
              $this->linkUser($id, $external_authentication['USER_NAME'], $external_authentication['SOURCE']);
              $this->external_message = $langs['LANG_MESSAGE_EXTERNAL_REGISTRATION_SUCCESS_U'];

              unset($sdata);
              $to = $external_authentication['USER_EMAIL'];
              $subject = $langs['LANG_EMAIL_EXTERNAL_REGISTER_LINE_1'];

              $email = new Email($langs, $_SESSION['_lang']);
              $sdata['USER_NAME'] = $_SESSION['external_user']['TSC_USER_NAME'];
              $sdata['PASSWORD'] = $password;
              $email->getEmailFromTemplate ('email_external_register', $sdata) ;
              $email->send($to, $subject);
            } else {
  	      unset($_SESSION['_user']);
  	    }
          }
        } 
      } else {
	 unset($_SESSION['_user']);
	 unset($_SESSION['external_user']);
         unset($_SESSION['externally_logged']);
         $_SESSION['external_user'] = $external_authentication;
      }
    }

//print_r($_SESSION['external_user']);
    if ((isset($_POST['l_user_name']) || isset($_POST['l_password'])) 
       || (isset($_SESSION['external_user']['TSC_USER_NAME']) && !$this->userOn())
       || (isset($_COOKIE['ssuser']) && strlen($_COOKIE['ssuser'])>1 && !$this->userOn())) {
      $logproc = FALSE;
      $fields = 'USER_ID, FIRST_NAME, LAST_NAME, USER_NAME, PASSWORD, EMAIL, 
             PHONE, COUNTRY, MOBILE_PHONE, TOWN, ADDRESS1, ADDRESS2, POSTCODE, PUBLISH, 
             ROUND(CREDIT, 2) CREDIT, FROZEN_CREDITS, GENDER, SUBSTRING(BIRTH_DATE, 1, 10) BIRTH_DATE,  COOKIESTRING,
             PIC_LOCATION, IP, REG_IP, REG_DATE, (DATE(LAST_LOGIN) = DATE(NOW())) VISITED_ALREADY, ACTIVE, 
	     TRUST, COMMENT_TRUST, CONTENT_TRUST, TIMEZONE, ADMIN, PM_EMAIL, STOCK_PROFIT_EMAIL, TOPIC_SORTING,
	     EDITOR_WINDOW, DAY(NOW()) as CURRENT_DAY, EMAIL_VERIFIED';
  
      if (isset($_POST['l_user_name']) && strlen($_POST['l_user_name']) > 1) {
        $db->select('users', $fields, "UPPER(USER_NAME) LIKE UPPER('".$_POST['l_user_name']."')
                                     AND PASSWORD=MD5('".$_POST['l_password']."')");
        $logproc = TRUE;
      }
      else if (isset($_COOKIE['ssuser']) && $_COOKIE['ssuser'] != '') {
          // "remember me" cookie
           $db->select('users', $fields, "COOKIESTRING='".$_COOKIE['ssuser']."'");
           $logproc = TRUE;
      } else if (isset($_SESSION['external_user']['TSC_USER_NAME'])) {
           $db->select('users', $fields, "UPPER(USER_NAME) LIKE UPPER('".$_SESSION['external_user']['TSC_USER_NAME']."')");
           $logproc = TRUE;
      } else {
	 $logproc = false;
      }

        $sdata = '';
        if ($logproc && ($row = $db->nextRow())) {
          // login successeful
          if ($row['ACTIVE'] == 'Y') {
            if (isset($_SESSION['external_user']) && !isset($_SESSION['external_user']['TSC_USER_NAME'])) {
	      $sql = "SELECT * from linked_users L 
                       where L.USER_ID = ".$row['USER_ID'];
//echo $sql;
              $db->query($sql);
              if ($row3 = $db->nextRow()) {
                // user is linked to a different one already
                $this->external_error = $langs['LANG_ERROR_USER_ALREADY_LINKED_U'];
                return;
              }
            }
  	    $sdata['IP'] = "'".$_SERVER['REMOTE_ADDR']."'";
            if ($row['REG_IP'] == "")
    	      $sdata['REG_IP'] = "'".$_SERVER['REMOTE_ADDR']."'";
            $sdata['LAST_LOGIN']='NOW()';
            $_SESSION["_user"] = $row;
            if (isset($_POST['l_remember']) && $_POST['l_remember'] == 'on') {
              if (empty($_SESSION["_user"]['COOKIESTRING'])) {
                  list($usec, $sec) = explode(' ', microtime()); 
                  $cookiestring = md5($_POST['l_user_name'].$_POST['l_password'].$usec);
                  $_SESSION["_user"]['COOKIESTRING'] = $cookiestring;
                  $sdata['COOKIESTRING'] = "'$cookiestring'";
              }
              setcookie('ssuser', $_SESSION["_user"]['COOKIESTRING'], time()+3600*24*365, '/', '.thesportcity.net');
            }
            $db->update('users', $sdata, 'USER_ID='.$_SESSION["_user"]['USER_ID']);  
  
            if ($row['VISITED_ALREADY'] == 0 && $row['CREDIT'] < 1) {
      	      $credits = new Credits();
       	      $credits->updateCredits ($_SESSION["_user"]['USER_ID'], 0.01, false);
                $credit_log = new CreditsLog();
    	      $credit_log->logEvent ($_SESSION["_user"]['USER_ID'], 11, 0.01);
            }
            $this->getSupporterInfo();

            if (isset($_SESSION['external_user'])) {
                // link
              if (!empty($_SESSION['external_user']['USER_NAME'])) {
                unset($sdata);
                $this->linkUser($_SESSION["_user"]['USER_ID'], $_SESSION['external_user']['USER_NAME'], $_SESSION['external_user']['SOURCE']);
                $external_user = $_SESSION["_user"]['USER_ID'];          
                if (!empty($external_authentication))
                  $_SESSION['external_user'] = $external_authentication;
                $_SESSION['external_user']['TSC_USER_NAME'] = $_SESSION["_user"]['USER_NAME'];
              }
            }
          }   
          else {
           unset($_SESSION["_user"]);
           unset($_POST['l_user_name']);
           unset($_POST['l_password']);
           $this->message = 'ERROR_ACCOUNT_NOT_ACTIVE';
          }
          $db->free();
          // update user last login date
        }
        else {
        // login incorrect. go back to login page with error
          unset($_SESSION["_user"]);
          if (!(empty($_POST['l_user_name']) || empty($_POST['l_password']))) {
            unset($_POST['l_user_name']);
            unset($_POST['l_password']);  
            $this->message = 'ERROR_LOGIN';
            $this->external_error = $langs['LANG_ERROR_LOGIN_U'];
          }
        }
      } else if ($this->userOn() && isset($_SESSION["_user"]['CURRENT_DAY']) && $_SESSION["_user"]['CURRENT_DAY'] != date("d")) {
	  $credits = new Credits();
       	  $credits->updateCredits ($_SESSION["_user"]['USER_ID'], 0.01, false);
            $credit_log = new CreditsLog();
    	  $credit_log->logEvent ($_SESSION["_user"]['USER_ID'], 11, 0.01);
	  $_SESSION["_user"]['CURRENT_DAY'] = date("d");
            unset($sdata);
	  $sdata['LAST_LOGIN'] = 'NOW()';
	  $sdata['IP'] = "'".$_SERVER['REMOTE_ADDR']."'";
            $db->update('users', $sdata, 'USER_ID='.$_SESSION["_user"]['USER_ID']);  
      }
  }

  function login_ss() {
    global $_SESSION;
    global $db;

    if ($this->userOn()) {
      $db->select("ss_users", "*, UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(LAST_VISIT) AS TIMECHECK", "USER_ID=".$_SESSION["_user"]['USER_ID']);
      if ($row = $db->nextRow()) {
        $_SESSION["_user"]['SS'][0] = $row;
      }  
    } 
  }

  function refresh() {
    global $_SESSION;
    global $db;

      $fields = 'USER_ID, FIRST_NAME, LAST_NAME, USER_NAME, PASSWORD, EMAIL, 
             PHONE, COUNTRY, MOBILE_PHONE, TOWN, ADDRESS1, ADDRESS2, POSTCODE, PUBLISH, 
             CREDIT, FROZEN_CREDITS, GENDER, SUBSTRING(BIRTH_DATE, 1, 10) BIRTH_DATE, 
             PIC_LOCATION, IP, REG_DATE, TO_DAYS(LAST_LOGIN) - TO_DAYS(NOW()) VISITED_ALREADY, 
	     ACTIVE, TRUST, TIMEZONE, PM_EMAIL, STOCK_PROFIT_EMAIL, DAY(NOW()) as CURRENT_DAY,
	     EMAIL_VERIFIED';

      $db->select('users', $fields, "USER_ID=".$this->getUserId());
      if ($row = $db->nextRow()) {
        $_SESSION["_user"] = $row;
        $this->getSupporterInfo();
      }   
  }

  function refreshEssensials() {
    global $_SESSION;
    global $db;

    if ($this->userOn()) {
      $fields = 'CREDIT, FROZEN_CREDITS';
      $db->select('users', $fields, "USER_ID=".$this->getUserId());
      if ($row = $db->nextRow()) {
        $_SESSION["_user"]['CREDIT'] = round($row['CREDIT'], 2);   
        $_SESSION["_user"]['FROZEN_CREDITS'] = round($row['FROZEN_CREDITS'], 2);   
      }
    }
  }

  function isGroupModerator() {
    global $db;

    $sql = "SELECT COUNT(level) LEVELS FROM forum_groups_members FGM where level in (1,3) and USER_ID=".$this->getUserId();
    $db->query($sql);
    $row = $db->nextRow();
    return $row['LEVELS'];
  }

  function isGroupModerator2($group_id) {
    global $db;

    $sql = "SELECT level FROM forum_groups_members FGM where level in (1,3) and group_id = ".$group_id." and USER_ID=".$this->getUserId();
    $db->query($sql);
    if ($row = $db->nextRow()) {
      return true;
    } 
    return false;
  }

  function isForumModerator($forum_id) {
    global $db;

    $sql = "SELECT FGM.level FROM forum F, forum_groups_members FGM 
		where F.GROUP_ID=FGM.GROUP_ID
		      and FGM.level in (1,3) 
		      and F.forum_id =".$forum_id."
	              AND FGM.USER_ID=".$this->getUserId();
    $db->query($sql);
    if ($row = $db->nextRow()) {
      return true;
    } 
    return false;
  }

  function userOn() {
    global $_SESSION;

    if (isset($_SESSION["_user"]) && isset($_SESSION["_user"]['USER_ID']) &&  $_SESSION["_user"]['USER_ID'] > 0) {
      return TRUE;
    }
    else
      return FALSE;
  }

  function getUserId() {
    global $_SESSION;
    if (isset($_SESSION["_user"]) && isset($_SESSION["_user"]['USER_ID']) && $_SESSION["_user"]['USER_ID'] > 0)
      return $_SESSION["_user"]['USER_ID'];      
  }


  function getUserName() {
    global $_SESSION;
    if (isset($_SESSION["_user"]) && isset($_SESSION["_user"]['USER_ID']) && $_SESSION["_user"]['USER_ID'] > 0)
      return $_SESSION["_user"]['USER_NAME']; 
  }

  function getUserTimezone() {
    global $_SESSION;
    if (!empty($_SESSION["_user"]['TIMEZONE']))
      return $_SESSION["_user"]['TIMEZONE']; 
    else return 0;
  }

  function getUserTimezoneName() {
    global $_SESSION;
    global $timezones;

    if (!empty($_SESSION["_user"]['TIMEZONE']))
      return substr($timezones[$_SESSION["_user"]['TIMEZONE']], 1, strpos($timezones[$_SESSION["_user"]['TIMEZONE']], "]") -1); 
    else return "+00:00";
  }

  function userActive() {
    global $_SESSION;
    if (isset($_SESSION["_user"]) && $_SESSION["_user"]['ACTIVE'] == 'Y')
      return TRUE;
    else
      return FALSE;
  }

  function userOnComment() {
    global $_SESSION;
    if ($_SESSION["_user"]['USER_ID'] > 0 && $_SESSION["_user"]['USER_ACTIVE'] == 1)
      return TRUE;
    else
      return FALSE;
  }
  
  function isAdmin () {
    global $_SESSION;
    if (isset($_SESSION["_user"]['ADMIN']) && $_SESSION["_user"]['ADMIN'] == 'Y')
      return TRUE;
    else
      return FALSE;
  }

  function getSupporterInfo() {
    global $_SESSION;
    global $db;

    $sql = "SELECT DATE_ADD(END_DATE, INTERVAL " .($this->getUserTimezone()*60). " MINUTE) AS END_DATE, 
		  UNIX_TIMESTAMP(END_DATE) END_DATE_SECONDS FROM user_supporter 
		WHERE USER_ID=".$_SESSION["_user"]['USER_ID']."
			AND NOW() <= END_DATE
			AND NOW() >= START_DATE";
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $_SESSION["_user"]['SUPPORTER'] = 1;
      $_SESSION["_user"]['END_DATE_SECONDS'] = $row['END_DATE_SECONDS'];
      $_SESSION["_user"]['END_DATE'] = $row['END_DATE'];
    } else if ($this->isAdmin()) {
      $_SESSION["_user"]['SUPPORTER'] = 1;
      $_SESSION["_user"]['END_DATE_SECONDS'] = "&#8734;";
      $_SESSION["_user"]['END_DATE'] = "&#8734;";
    }    
  }

  function hasSupporter() {
    if ($this->isAdmin () || (isset($_SESSION["_user"]['SUPPORTER']) 
	&& $_SESSION["_user"]['SUPPORTER'] == 1
	&& $_SESSION["_user"]['END_DATE_SECONDS'] <= getdate(0)))
       return true;
    else return false;   
  }

  function getCountry() {
    global $_SESSION;
    global $db;

    $sql = "SELECT LATIN_NAME FROM countries where id=".$_SESSION['_user']['COUNTRY'];
    $db->query($sql);
    $row = $db->nextRow();
    return $row['LATIN_NAME'];

  }

  function getZone() {
    global $_SESSION;
    global $db;

    $sql = "SELECT ZONE FROM countries where id=".$_SESSION['_user']['COUNTRY'];
    $db->query($sql);
    $row = $db->nextRow();
    return $row['ZONE'];

  }

  function getLastIp() {
    global $_SESSION;
    return $_SESSION['_user']['IP'];
  }

  function getRegIp() {
    global $_SESSION;
    return $_SESSION['_user']['REG_IP'];
  }

  function getCredits() {
    global $_SESSION;
    return $_SESSION['_user']['CREDIT'];
  }

  function isClanLeader() {
    global $db;

    $sql = "SELECT USER_ID, CLAN_ID FROM clans C where USER_ID=".$this->getUserId();
    $db->query($sql);
    if ($row = $db->nextRow())
      return $row['CLAN_ID'];
    return false;
  }


  function isClanMember() {
    global $db;

    $sql = "SELECT USER_ID, CLAN_ID FROM clan_members C where STATUS in (1, 2) AND USER_ID=".$this->getUserId();
    $db->query($sql);
    if ($row = $db->nextRow())
      return $row['CLAN_ID'];
    return false;
  }

  function getClanInvites($clan_id = '') {
    global $db;

    $where_clan = '';
    if ($clan_id != '')
      $where_clan = " AND C.CLAN_ID=".$clan_id;

    $sql = "SELECT C.CLAN_ID, C.CLAN_NAME
		FROM clan_members CM, clans C
		WHERE C.CLAN_ID=CM.CLAN_ID
			AND CM.status=3 
			".$where_clan."
			AND CM.user_id=".$this->getUserId();

    $db->query($sql);
    $clan_invites = array();
    while ($row = $db->nextRow()) {
      $clan_invite = $row;
      $clan_invite['ENTRY'] = $row;
      if ($_SESSION['_user']['CREDIT'] >= 10) {
         $clan_invite['ENOUGH_CREDITS'] = $row;
         $clan_invite['BUTTONS'] = $row;
      } else {
         $clan_invite['NOT_ENOUGH_CREDITS'] = $row;
      }  
      $clan_invites[] = $clan_invite;
    }
    $db->free(); 

    return $clan_invites;

  }

  function linkUser($user_id, $user_name, $source) {
    global $db;
   
    unset($sdata);
    $sdata['USER_ID'] = $user_id;
    $sdata['LINKED_USER_NAME'] = "'".$user_name."'";
    $sdata['SOURCE'] = "'".$user_name."'";
    $sdata['LINKED_DATE'] = "NOW()";              
    $db->insert("linked_users", $sdata);

  }
}
?>