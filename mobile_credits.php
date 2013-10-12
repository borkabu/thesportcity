<?php
/*
===============================================================================
remind.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - sends a password reminder to user's email

TABLES USED: 
  - BASKET.USERS

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

// user session
include('class/ss_const.inc.php');

include('include.php');
include('class/user_session.inc.php');
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');

// check that the request comes from Fortumo server
  if(!in_array($_SERVER['REMOTE_ADDR'],
      array('86.140.40.161', '81.20.151.38', '81.20.148.122', '209.20.83.207', '79.125.125.1'))) {
    $email = new Email($langs, $_SESSION['_lang']);
    $email->setBody("Error: Unknown IP: ".$_SERVER['REMOTE_ADDR']);
    $email->sendAdmin("Fortumo hacking attempt");
    return;
  }


  // check the signature
  $sms_services = array ('e414f1808e925dfaf28372e3400e2897' => '9900d4b9cc487d7dd481ec17a5d37b8d',
			 '4d1df1bc7cb71e4f6b08e8f6622ca223' => 'ff083fcb4cf86cd5f40ea2129c85f3e0',
			 '101d5bf0d91428b0a1ada66c896af1df' => '159cf68d2bc2d3f37fcc75ec5a0fea50');

  $secret = isset($_GET['service_id']) ? $sms_services[$_GET['service_id']] : ''; // insert your secret between ''
  if(!empty($secret) && !check_signature($_GET, $secret)) {
    $email = new Email($langs, $_SESSION['_lang']);
    $email->setBody("Error: Invalid signature");
    $email->sendAdmin("Fortumo hacking attempt");
    return;
  } 

  $sender = $_GET['sender'];
  $message = $_GET['message'];
  $status = $_GET['status'];
  $country = $_GET['country'];
  $credits_bought = $_GET['amount'];
  $cuid = !empty($_GET['cuid']) ? $_GET['cuid'] : -10;

  if ($country)
    $last_lang = $conf_lang;

  unset($sdata);
  $sdata['MOBILE_PHONE'] = "'".$sender."'"; 
  $sdata['KEYWORD'] = "'".$_GET['keyword']."'";
  $sdata['SENT'] = "NOW()";
  $sdata['STATUS'] = "'".$status."'";
  $sdata['MESSAGE'] = "'".$message."'";
  $sdata['CREDITS'] = $credits_bought;
  $db->insert('sms_log', $sdata);
  // get sender from db
     $db->select('users', 'USER_ID, USER_NAME, CREDIT, LAST_LANG', "USER_ID= '".$cuid."' OR MOBILE_PHONE= ".$sender);
     if ($row=$db->nextRow()) {
       $sender_id = $row['USER_ID'];
       $sender_name = $row['USER_NAME'];
       $credits = $row['CREDIT'];
       if (!empty($row['LAST_LANG']))
         $last_lang = $row['LAST_LANG'];
       if ($status == "completed") {
         $credit_class = new Credits();
         $credit_class->updateCredits($sender_id, $credits_bought);
	 $credit_log = new CreditsLog();
	 $credit_log->logEvent ($sender_id, 1, $credits_bought);
         $data['SMS_CREDIT_SUCCESS'][0]['CREDITS_BOUGHT'] = $credits_bought;
         $data['SMS_CREDIT_SUCCESS'][0]['CREDIT'] = $credits + $credits_bought;
       }
     }
     else {
       $data['SMS_CREDIT_FAILURE_BAD_PHONE'][0]['X'] = 1;
     }

  include($conf_home_dir.'class/ss_lang_'.$last_lang.'.inc.php');
  while (list($key, $val) = each($langs)) {
    $data[$key] = $val;
  }

  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/sms_credits.tpl.html');
  $tpl->addData($data);
  $content = $tpl->parse();

  // print out the reply
  echo trim($content);


  function check_signature($params_array, $secret) {
    ksort($params_array);

    $str = '';
    foreach ($params_array as $k=>$v) {
      if($k != 'sig') {
        $str .= "$k=$v";
      }
    }
    $str .= $secret;
    $signature = md5($str);

    return ($params_array['sig'] == $signature);
  }
?>