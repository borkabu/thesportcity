<?php
require_once('class.phpmailer.php');

class Email extends Box{
  var $body;
  var $random_hash;

  function Email($langs = '') {
    if ($langs != '') {
      $this->langs = $langs  ;
      while (list($key, $val) = each($this->langs)) {
        $this->data[$key] = $val;
      }
    }
  }

  function getEmailFromTemplate ($template, $data) {
    global $smarty;  
    global $conf_home_dir;

    $smarty->assign("data", $data);
//print_r($data);
    $start = getmicrotime();
    $output = $smarty->fetch($conf_home_dir.'smarty_tpl/'.$template.'.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/'.$template.'.smarty'.($stop-$start);
    // content

    $this->body = $output;
    return $this->body;
  } 
  
  function removeEmptyLines() {
    //$this->body = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $this->body);
  }

  function setBody($body) {
    $this->body = $body;    
  }

  function setRandomHash() {
    $this->random_hash = md5(date('r', time())); 
  }

  function subjectToUtf8($subject) {
    return "=?UTF-8?B?".base64_encode($subject )."?=";
  }

  function send($to, $subject) {     
     global $conf_admin_email;
     $mail = new PHPMailer(); 

     $mail->CharSet = "UTF-8";
     $mail->AddReplyTo($conf_admin_email, 'TheSportCity.Net admin');  
     $mail->AddAddress($to);
     $mail->SetFrom($conf_admin_email, 'TheSportCity.Net admin');  
     $mail->Subject = $this->subjectToUtf8($subject);
     $mail->Body = stripslashes($this->body);

     return $mail->Send();       
  }

  function sendHTML($to, $subject, $html, $plain) {
     global $conf_admin_email;
     $mail = new PHPMailer(); 

     $mail->CharSet = "UTF-8";
     $mail->AddReplyTo($conf_admin_email, 'TheSportCity.Net admin');  
     $mail->AddAddress($to);
     $mail->SetFrom($conf_admin_email, 'TheSportCity.Net admin');  
     $mail->Subject = $this->subjectToUtf8($subject);
     $mail->MsgHTML($html);
     $mail->AltBody = $plain;
//echo $this->body;
     return $mail->Send();       
  }

  function sendHTMLonly($to, $subject) {     
     global $conf_admin_email;
     $mail = new PHPMailer(); 

     $mail->CharSet = "UTF-8";
     $mail->AddReplyTo($conf_admin_email, 'TheSportCity.Net admin');  
     $mail->AddAddress($to);
     $mail->SetFrom($conf_admin_email, 'TheSportCity.Net admin');  
     $mail->Subject = $this->subjectToUtf8($subject);
     $mail->MsgHTML($this->body);

     return $mail->Send();       
  }
  function sendAdmin($subject) {  
     global $conf_admin_email;

     $mail = new PHPMailer(); 
     
     $mail->CharSet = "UTF-8";
     $mail->AddReplyTo($conf_admin_email, 'TheSportCity.Net admin');  
     $mail->AddAddress($conf_admin_email);
     $mail->SetFrom($conf_admin_email, 'TheSportCity.Net admin');  
     $mail->Subject = $this->subjectToUtf8($subject);
     $mail->Body = stripslashes($this->body);

//echo $this->body;
     return $mail->Send();       
  }

  function sendAdminHTML($subject) {  
     global $conf_admin_email;

     $mail = new PHPMailer(); 
     
     $mail->CharSet = "UTF-8";
     $mail->AddReplyTo($conf_admin_email, 'TheSportCity.Net admin');  
     $mail->AddAddress($conf_admin_email);
     $mail->SetFrom($conf_admin_email, 'TheSportCity.Net admin');  
     $mail->Subject = $this->subjectToUtf8($subject);
     $mail->MsgHTML($this->body);

//echo $this->body;
     return $mail->Send();       
  }

  function save($to, $subject) {     
     global $db;

     $s_fields = array('EMAIL', 'SUBJECT', 'BODY');
     $i_fields = '';
     $d_fields = '';
     $c_fields = '';

// need id
     $data['EMAIL'] = $to;
     $data['SUBJECT'] = $subject;
     $data['BODY'] = $this->body;
     $sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$data);
     $db->insert('notification_email_queue', $sdata);
  }

  function sendSystemPM($to, $user_id, $subject) {     
     global $db;

     $s_fields = array('EMAIL', 'SUBJECT', 'BODY');
     $i_fields = '';
     $d_fields = '';
     $c_fields = '';

// need id
     $data['EMAIL'] = $to;
     $data['SUBJECT'] = $subject;
     $data['BODY'] = $this->body;
     $sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$data);
     $db->insert('notification_email_queue', $sdata);
  }

}

?>