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

class PM{

  function PM() {
  }

  function openMessage($pm_id, $sender_id) {
    global $db;
    global $auth;

    unset($sdata);
    $sdata['OPENED'] = 1;
    $sdata['OPENED_DATE'] = 'NOW()';
    $db->update('pm_message', $sdata, "PM_ID=".$pm_id);
    unset($sdata);
    $sdata['FOLDER_ID'] = 2;
    $db->update('pm_folder_messages', $sdata, "MESSAGE_ID=".$pm_id." AND FOLDER_ID=3 AND USER_ID=".$sender_id);
    unset($sdata);
    $sdata['RECEIVED'] = 1;
    $db->update('pm_folder_messages', $sdata, "MESSAGE_ID=".$pm_id." AND USER_ID=".$auth->getUserId());

  }

  function deleteMessage($pm_id, $folder_id, $user_id) {
    global $db;

    if ($folder_id == 3)
      $db->delete('pm_folder_messages', "MESSAGE_ID=".$pm_id);
    else 
      $db->delete('pm_folder_messages', "MESSAGE_ID=".$pm_id." AND FOLDER_ID=".$folder_id." AND USER_ID=".$user_id);

  }

  function deleteSystemMessage($pm_id) {
    global $db;

    $db->delete('pm_folder_messages', "MESSAGE_ID=".$pm_id);
    $db->delete('pm_message', "SENDER_ID=-1 AND PM_ID=".$pm_id);
    $db->delete('pm_message_reciever', "PM_ID=".$pm_id);

  }


  function deleteAllMessages($pm_ids, $folder_id, $user_id) {
    global $db;

    $msgs = explode(",", $pm_ids);
    foreach( $msgs as $msg)
     if (!empty($msg))
       $this->deleteMessage($msg, $folder_id, $user_id);

  }


  function getMessagesNumber($folder_id) {
    global $db;
    global $auth;

    $sql_count = "SELECT COUNT(PM.PM_ID) ROWS
                   FROM pm_message PM, 
			pm_folder_messages PFM
                   WHERE PM.PM_ID=PFM.MESSAGE_ID
			AND PFM.USER_ID =".$auth->getUserId()."
			AND PFM.FOLDER_ID=".$folder_id; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
    }

    return $count;
  }

  function getUnreadMessagesNumber($folder_id) {
    global $db;
    global $auth;

    $sql_count = "SELECT COUNT(PFM.MESSAGE_ID) ROWS
                   FROM pm_folder_messages PFM
                   WHERE PFM.RECEIVED = 0
			AND PFM.USER_ID =".$auth->getUserId()."
			AND PFM.FOLDER_ID=".$folder_id; 
//echo $sql_count;
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
    }

    return $count;
  }

  function createSystemPM($user_id, $subject, $descr) {
     global $db;

     $s_fields_d=array('subject', 'descr');
     $i_fields_d='';
     $d_fields_d='';
     $c_fields_d='';

     $_POST['subject'] = $subject;
     $_POST['descr'] = $descr;
     $sdata=buildSaveData($s_fields_d,$i_fields_d,$d_fields_d,$c_fields_d,$_POST);
     $sdata['SENT_DATE'] = "NOW()";
     $sdata['SENDER_ID'] = -1;
     // proceed to database updates
     // INSERT
     $db->insert('pm_message', $sdata);
     $pm_id = $db->id();
     // redirect to news page
     unset($sdata);
     $sdata['PM_ID'] = $pm_id;
     $sdata['RECEIVER_TYPE'] = 1;
     $sdata['RECEIVER_ID'] = $user_id;
     $db->insert('pm_message_receiver', $sdata);
     unset($sdata);
     $sdata['FOLDER_ID'] = 1;
     $sdata['MESSAGE_ID'] = $pm_id;
     $sdata['USER_ID'] = $user_id;
     $db->insert('pm_folder_messages', $sdata);

     $receiver = new User();
     $receiver_id = $receiver->getUserIdFromId($user_id);
     if ($receiver->getPM_Email()) {
       $notification = new notification();
       $notification->sendPMEmail($receiver->user_name, $receiver->email, "System", $_POST['subject'], $pm_id, $receiver->last_lang);
     }
  }


}   
?>