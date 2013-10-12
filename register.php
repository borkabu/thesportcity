<?php
error_reporting (E_ALL & ~E_NOTICE);
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
// parse template
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('tpl/register.tpl.html');

// --- build content data -----------------------------------------------------
// process updates

$db->showquery=true;

$submitted = false;
if (isset($_POST['save'])) {
  $_POST['mobile_phone'] = getMobile($_POST['mobile_phone']);
  // required fields
  $dupe_fields1 = array('email');
  $dupe_fields2 = array('mobile_phone');
  if ($auth->userOn()) {
    $s_fields = array('first_name', 'last_name',
                      'town', 'address1', 
                      'address2', 'postcode', 'pic_location');
    $r_fields = array('first_name', 'last_name', 'country');
  }
  else {
    $s_fields = array('first_name', 'last_name', 'user_name', 'password',
                      'email', 'town', 'address1', 
                      'address2', 'postcode', 'pic_location');
    $r_fields = array('first_name', 'last_name', 'email', 'password', 'country');
  }
  
  $i_fields = array('mobile_phone', 'country', 'timezone');
  $d_fields = array('birth_date');
  $c_fields = array('gender', 'remember');
  
  $dupe_except = array('user_id' => evalIntSql($_SESSION['_user']['USER_ID']));
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR_MAND'][0]['X'] = 1;
  }

  // check for duplicate email
  if (isset($_POST['email']) && !dupeFieldsOk('users', $dupe_fields1, $_POST, $dupe_except)) {
    $error = TRUE;
    $data['ERROR_DUPE_EMAIL'][0]['X'] = 1;
  }

  if (!$auth->userOn()) {
    if (!dupeFieldsOk('users', array('user_name'), $_POST, $dupe_except)) { 
      $error = TRUE;
      $data['ERROR_DUPE_UNAME'][0]['X'] = 1;
    }
  }

  // check for duplicate mobile phones
  if (!empty($_POST['mobile_phone']) && !dupeFieldsMobileOk('users', $dupe_fields2, $_POST, $dupe_except)) {
    $error = TRUE;
    $data['ERROR_DUPE_MOBILE'][0]['X'] = 1;
  }

  // check for password matching
  if (!$auth->userOn() && $_POST['password'] != $_POST['password2']) {
    $error = TRUE;
    $data['ERROR_PASW_MATCH'][0]['X'] = 1;
  }
  
  // check for correct username lenght
  if ((strlen($_POST['user_name']) < 2 || strlen($_POST['user_name']) > 12) && !$auth->userOn()) {
    $error = TRUE;
    $data['ERROR_NAME_RESTR'][0]['X'] = 1;
  }
  
  // check for validity of usernam
  if (!loginOk($_POST['user_name'])) {
    $error = TRUE;
    $data['ERROR_NAME_RESTR2'][0]['X'] = 1;
  }
  
  // check for correct password lenght
  if (!$auth->userOn() && (strlen($_POST['password']) < 5 || strlen($_POST['password']) > 16)) {
    $error = TRUE;
    $data['ERROR_PASW_RESTR'][0]['X'] = 1;
  }
  
  // validate e-mail address
  if (!empty($_POST['email']) && !emailOk($_POST['email'])) {
    $error = TRUE;
    $data['ERROR_EMAIL_RESTR'][0]['X'] = 1;
  }
   
  // validate mobile phone number
  if (!empty($_POST['mobile_phone']) && !mobileOk($_POST['mobile_phone'])) {
    $error = TRUE;
    $data['ERROR_MOBILE'][0]['X'] = 1;
  }

// check CAPTCHA

  if (!$auth->userOn() ) {
    include_once 'securimage/securimage.php';
    $securimage = new Securimage();
    if ($securimage->check($_POST['captcha_code']) == false) {
      // the code was incorrect
      // handle the error accordingly with your other error checking

      // or you can do something really basic like this
      $error = TRUE;
      $data['ERROR_CAPTCHA'][0]['X'] = 1;
    }
  }
      

  if (!$error) {
    // get save data
//        echo 3;
    $submitted = true;
    if (!$auth->userOn()) {
      $password =  $_POST['password'];
      $_POST['password'] = md5($_POST['password']);
    }
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST, TRUE, TRUE);
    // remember me string generation
    if (!$auth->userOn()) {
      if ($remember == 'Y') {
        if (empty($_user['COOKIESTRING'])) {
          list($usec, $sec) = explode(' ', microtime()); 
          $cookiestring = md5($_POST['user_name'].$_POST['password'].$usec);
          $SESSION['_user']['COOKIESTRING'] = $cookiestring;
          $sdata['COOKIESTRING'] = "'$cookiestring'";
        }
        setcookie('ssuser', $SESSION['_user']['COOKIESTRING'], time()+3600*24*365);
      }
      else {
        setcookie('ssuser');
      }
    }
//$db->showquery=true;   
    // proceed to database updates
    if ($auth->userOn()) {
      // UPDATE
      unset($sdata['user_name']);
      $db->update('users', $sdata, 'USER_ID='.$auth->getUserId());
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getMessageBox('MESSAGE_REGISTRATION_UPDATE_SUCCESS');
      $auth->refresh();
    }
    else {
      // INSERT
      $actkey = gen_rand_string(0, 10);
      $sdata['ACTKEY'] = "'".$actkey."'";
      $sdata['REG_DATE'] = 'NOW()';
      $sdata['LAST_LOGIN'] = 'NOW()';
      $real_ip = $_SERVER["REMOTE_ADDR"];

      $sdata['IP'] = "'".$real_ip."'";
      $sdata['REG_IP'] = "'".$real_ip."'";
      $db->insert('users', $sdata);
      $user_id = $db->id(); 

      unset($sdata);
      $sdata['USER_ID'] = $user_id;
      $db->insert('ss_users', $sdata);


      $to = $_POST['email'];
      $subject = $langs['LANG_EMAIL_REGISTER_LINE_1'];

      $email = new Email($langs, $_SESSION['_lang']);
      $sdata['USER_NAME'] = $_POST['user_name'];
      $sdata['PASSWORD'] = $password;
      $sdata['URL'] = $conf_site_url.'user_activation.php?mode=activate&u='.$user_id.'&k='.$actkey;
      $email->getEmailFromTemplate ('email_register', $sdata) ;
      if ($email->send($to, $subject)) {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getMessageBox('MESSAGE_REGISTRATION_SUCCESS');
      } else {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_EMAIL');
      }
    }
  }

}

// preset user fields
if (!$submitted) {
  $opt['class'] = 'input';
  $opt['options'] = $timezones;
  $opt['style'] = 'width: 350px';
  if ($auth->userOn()) {
    $PRESET_VARS = keysToLower($_SESSION['_user']);
    $PRESET_VARS['password2'] = $PRESET_VARS['password'];
    $data['COUNTRY'] = inputCountries('country', $_SESSION['_user']['country']);
    $data['TIMEZONE'] = $frm->getInput(FORM_INPUT_SELECT, 'timezone', $timezone, $opt, $_SESSION['_user']['TIMEZONE']);
    $data['SEX_M'] = $frm->getInputWithValue(FORM_INPUT_RADIO, 'gender', 
                                      array('value_force' => 'Y', 'class' => ''));
    $data['SEX_F'] = $frm->getInputWithValue(FORM_INPUT_RADIO, 'gender', 
                                    array('value_force' => 'N', 'class' => ''));

  }
  else {

  // add sex radio buttons
  $data['COUNTRY'] = inputCountries('country', $_POST['country']);
  $data['TIMEZONE'] = $frm->getInput(FORM_INPUT_SELECT, 'timezone', $timezone, $opt, "0");
  $data['SEX_M'] = $frm->getInputWithValue(FORM_INPUT_RADIO, 'gender', 
                                      array('value_force' => 'Y', 'class' => ''));
  $data['SEX_F'] = $frm->getInputWithValue(FORM_INPUT_RADIO, 'gender', 
                                    array('value_force' => 'N', 'class' => ''));
  }
  // show username field only to new users
  if (!$auth->userOn()) {
    $data['UNAME'][0]['X'] = 1;
    $data['UNAME1'][0]['X'] = 1;
    $data['EMAIL1'][0]['X'] = 1;
    $data['PASSWORD'][0]['X'] = 1;
    $data['REGISTER'][0]['X'] = 1;
    $data['CAPTCHA'][0]['X'] = 1;
  } else {
    $data['UNAME2'][0]['USER_NAME'] = $_SESSION['_user']['USER_NAME'];
    $data['EMAIL2'][0]['EMAIL'] = $_SESSION['_user']['EMAIL'];
    $data['UPDATE'][0]['X'] = 1;
  }
  $tpl->addData($data);

  $content=$tpl->parse();

}

// add data

// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>
