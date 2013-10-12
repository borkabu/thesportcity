<?php
/*
===============================================================================
user_session.inc.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - logs user on (login is checked against BASKET.USERS table)

TABLES USED: 
  - BASKET.USERS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
 require('facebook/facebook.php');

 $facebook = new Facebook(array(
   'appId'  => $fbAppId,
   'secret' => $fbSecret,
 )); 

/* $facebook_user = $facebook->getUser();

 if ($facebook_user) {
   try {
     // Proceed knowing you have a logged in user who's authenticated.
     $user_profile = $facebook->api('/me');
     print_r($user_profile);

     $external_authentication['USER_NAME'] = $user_profile['username'];
     $external_authentication['USER_EMAIL'] = $user_profile['email'];
     $external_authentication['VALID'] = 1;
     $external_authentication['SOURCE'] = 'facebook';
   } catch (FacebookApiException $e) {
     echo '<pre>'.htmlspecialchars(print_r($e, true)).'</pre>';
     $facebook_user = null;
   }
 }   
                           */
     $facebook_user = 1;
     $user_profile['username'] = 'bbb3';
     $user_profile['email'] = "borkaaaaaa@tdd.lt";
     $external_authentication['USER_NAME'] = $user_profile['username'];
     $external_authentication['USER_EMAIL'] = $user_profile['email'];
     $external_authentication['VALID'] = 1;
     $external_authentication['SOURCE'] = 'facebook';



     print_r($_SESSION);


// register session
 $page_start_time=getmicrotime(); 
 $auth = new Auth((isset($reset_login) && $reset_login) ? 1 : 0);
 $message = $auth->getMessage();
 $errorbox1 = '';
 if (isset($message) && !empty($message)) {
  $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
  $errorbox1 = $errorbox->getErrorBox($message);
 }

 $_SESSION['_lang'] = mysql_real_escape_string($_SESSION['_lang']);

 $db->query("SELECT ID AS LANG_ID, I18N FROM languages WHERE SHORt_CODE='".$_SESSION['_lang']."'");
 $row = $db->nextRow();
 $_SESSION['lang_id'] = $row['LANG_ID'];
 $_SESSION['I18N'] = $row['I18N'];

 $trust= new Trust();
 $trust->refreshTrusts(); 

 if (isset($_SESSION["_user"]) && isset($_SESSION["_user"]['USER_ID']) &&  $_SESSION["_user"]['USER_ID'] > 0) 
   if (isset($_POST['lang']) || isset($_GET['lang_id']))
     $db->update('users', "LAST_LANG='".$_SESSION["_lang"]."'", 'USER_ID='.$_SESSION["_user"]['USER_ID']); 

?>