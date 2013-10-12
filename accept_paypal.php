<?php
error_reporting(E_ALL ^ E_NOTICE);
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
// page requirements
include('class/inputs.inc.php');
include('class/order.inc.php');

// read the post from PayPal system and add 'cmd'

$all_headers = $_POST;
$postdata = file_get_contents("php://input");
$req = 'cmd=_notify-validate';

/*if (function_exists('get_magic_quotes_gpc'))
{
  $get_magic_quotes_exits = true;
}
foreach ($_POST as $key => $value) {
  if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1){
    $value = urlencode(stripslashes($value));
  } else {
    $value = urlencode($value);
  }
  $value = str_replace('%5Cr%5Cn', '%0D%0A', $value);
  $req .= "&$key=$value";
} */

foreach ($_POST as $key => $value) {
  $value = urlencode(stripslashes($value));
  $req .= "&$key=$value";
}

// post back to PayPal system to validate
$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
$fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);

// assign posted variables to local variables
$item_name = $_POST['item_name'];
$item_number = $_POST['item_number'];
$payment_status = $_POST['payment_status'];
$payment_amount = $_POST['mc_gross'];
$payment_currency = $_POST['mc_currency'];
$txn_id = $_POST['txn_id'];
$txn_type = $_POST['txn_type'];
$receiver_email = $_POST['receiver_email'];
$payer_email = $_POST['payer_email'];
$option_name1 = $_POST['option_name1'];
$option_selection1 = $_POST['option_selection1'];

if (isset($_POST['txn_type']) && $_POST['txn_type'] == "cart") {
    $_POST['item_name'] = "TSC shop";
    $_POST['item_number'] = $_POST['custom'];
}
else if (isset($_POST['txn_type']) && $_POST['txn_type'] == "web_accept") {
  if (isset($_POST['option_name1']))
    $_POST['item_name'] = $_POST['option_name1'];
  if (isset($_POST['option_selection1']))
    $_POST['item_number'] = $_POST['option_selection1'];
}

  $s_fields = array('item_name', 'item_number', 'payment_status', 'mc_currency', 'receiver_email', 'payer_email', 'txn_id');
  $i_fields = array('mc_gross');
  $d_fields = '';
  $c_fields = '';
  
  // get save data
  $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);

$message = "item: ".$item_name."\r\n";
$message .= "item_number: ".$item_number."\r\n";
$message .= "payment_status: ".$payment_status."\r\n";
$message .= "payment_amount: ".$payment_amount."\r\n";
$message .= "payment_currency: ".$payment_currency."\r\n";
$message .= "txn_type: ".$txn_type."\r\n";
$message .= "txn_id: ".$txn_id."\r\n";
$message .= "receiver_email: ".$receiver_email."\r\n";
$message .= "payer_email: ".$payer_email."\r\n";
$message .= "option_name1: ".$option_name1."\r\n";
$message .= "option_selection1: ".$option_selection1."\r\n";
$message .= "POST\r\n";
$message .= array_to_string($_POST);
$message .= "GET\r\n";
$message .= array_to_string($_GET);
$message .= "Headers\r\n";
$message .= array_to_string($all_headers);
$message .= "$req\r\n";
$message .= $postdata;

$email = new Email($langs, $_SESSION['_lang']);

if (!$fp) {
// HTTP ERROR
  $message .= "Status: HTTP error";
  $email->setBody($message);
  $email->sendAdmin("TheSportCity.Net paypal transaction error");
} else {
fputs ($fp, $header . $req);
 while (!feof($fp)) {
  $res = fgets ($fp, 1024);
  if (strcmp ($res, "VERIFIED") == 0) {
  // check the payment_status is Completed
    if ($payment_status == "Completed") {
    // check that txn_id has not been previously processed
     $db->select('paypal', '*', "TXN_ID='".$txn_id."'");
     if (!$row=$db->nextRow()) {
       // check that receiver_email is your Primary PayPal email
       // check that payment_amount/payment_currency are correct
       $sdata['status'] = "'VERIFIED'";
       $db->insert("paypal", $sdata);
       if ($txn_type == "web_accept") {
         if ($option_name1 == 'credits') {
           $db->select('users', 'USER_ID, CREDIT', "LOWER(EMAIL)=LOWER('".$payer_email."')");
           if ($row=$db->nextRow()) {
             $credits = new Credits();
             $credits->updateCreditsByEmail ($payer_email, $option_selection1);
   
             // process payment
              $message .= "Status: VERIFIED";
              $email->setBody($message);
              $email->sendAdmin("TheSportCity.Net paypal transaction successful");
             }
             else {
              $message .= "Status: VERIFIED - wrong email";
              $email->setBody($message);
              $email->sendAdmin("TheSportCity.Net paypal transaction unsuccessful");
             }
           } else {
              $message .= "Status: VERIFIED - unknown payment option";
              $email->setBody($message);
              $email->sendAdmin("TheSportCity.Net paypal transaction unsuccessful");
           }
       } 
       else if ($txn_type == "cart") {
         if (isset($_POST['custom']))  {
           $order = new Order($_POST['custom'], 1);
           $order->acceptPaypalPayment();
         }

         // process payment
         $message .= "Status: VERIFIED";
         $email->setBody($message);
         $email->sendAdmin("TheSportCity.Net paypal transaction successful");
       }
     }
     else {
       $message .= "Status: VERIFIED - transaction id already has been processed";
       $email->setBody($message);
       $email->sendAdmin("TheSportCity.Net paypal transaction unsuccessful");
      }
    }
    else {
      $message .= "Status: VERIFIED - payment_status: ".$payment_status;
       $email->setBody($message);
       $email->sendAdmin("TheSportCity.Net paypal transaction unsuccessful");
    }
  }
  else if (strcmp ($res, "INVALID") == 0) {
  // log for manual investigation
     $sdata['status'] = "'INVALID'";
     $db->insert("paypal", $sdata);

     $message .= "Status: INVALID";
     $email->setBody($message);
     $email->sendAdmin("TheSportCity.Net paypal transaction error");
  }
}
fwrite($fp,  "200 OK HTTP/1.1");
fclose ($fp);
header("Status: 200 OK");
}
?>