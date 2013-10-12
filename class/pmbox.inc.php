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

class PMBox extends Box{
  var $message;

  function PMBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getComposeMessageBar($logged, $message) {
    global $db;
    global $smarty;
    global $_POST;
    global $auth;

    if ($auth->userOn()) {
      $receipients_box = $this->getAddReceipientsBox();
      if (isset($_POST['subject']))
        $logged['SUBJECT'] = $_POST['subject'];
      else if (isset($this->message) && isset($this->message['EDITING'])) {
        $logged['SUBJECT'] = $this->message['EDITING']['SUBJECT'];
      }
      if (isset($_POST['descr']))
        $logged['DESCR'] = $_POST['descr'];
      else if (isset($this->message) && isset($this->message['EDITING']))
        $logged['DESCR'] = $this->message['EDITING']['DESCR'];

    } else return '';

    if (!empty($receipients_box))
      $smarty->assign("receipients_box", $receipients_box);    
    if (isset($logged))
      $smarty->assign("logged", $logged);    

    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/bar_compose_message.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_compose_message.smarty'.($stop-$start);

    return $content;
  }

  function getMessage($message_id, $folder_id) {
    global $db;
    global $smarty;

    // content
    $this->message = $this->getMessageData($message_id, $folder_id);

    if (!empty($this->message))
      $smarty->assign("message", $this->message);
    else return ''; 

    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/bar_pm.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_pm.smarty'.($stop-$start);

    return $content;
  }

  function getMessageData ($message_id, $folder_id){
    global $db;
    global $_SESSION;
    global $_POST;
    global $_GET;
    global $auth;
    global $PRESET_VARS;
  
    $sql =  "SELECT PM.*, U.USER_NAME AS AUTHOR, PFM.RECEIVED,
                    GROUP_CONCAT(DISTINCT U1.USER_NAME ORDER BY U1.USER_NAME SEPARATOR ', ') AS USER_RECEIPIENT, 
                    GROUP_CONCAT(DISTINCT FG.GROUP_NAME ORDER BY FG.GROUP_NAME SEPARATOR ', ') AS GROUP_RECEIPIENT, 
  		    ".$folder_id." as FOLDER_ID
                   FROM pm_folder_messages PFM,
			pm_message PM
                        left join users U ON PM.SENDER_ID=U.USER_ID,
			pm_message_receiver PMR
			left join users U1 ON PMR.RECEIVER_ID=U1.USER_ID AND RECEIVER_TYPE=1
			left join forum_groups_details FG ON PMR.RECEIVER_ID=FG.GROUP_ID AND PMR.RECEIVER_TYPE=2 and LANG_ID=".$_SESSION['lang_id']."

                   WHERE PM.PM_ID=PFM.MESSAGE_ID
			AND PM.PM_ID =PMR.PM_ID
			AND PFM.MESSAGE_ID=".$message_id." 
			AND PFM.USER_ID =".$auth->getUserId()."
			AND PFM.FOLDER_ID=".$folder_id;
    $db->query($sql);
    $message = '';
    if ($row = $db->nextRow()) {
      $message = $row; 
      if (!empty($row['USER_RECEIPIENT']))  
        $message['USER_NAME'] = $row['USER_RECEIPIENT'];
      if (!empty($row['GROUP_RECEIPIENT']))  
        $message['GROUP_NAME'] = $row['GROUP_RECEIPIENT'];

      if ($row['RECEIVED'] == 0 && $folder_id == 1) {
        $pm = new PM();
        $pm->openMessage($row['PM_ID'], $row['SENDER_ID']);   
      }

      if ($row['SENDER_ID'] == -1) {
        $message['SYSTEM'] = true;
        $message['DESCR'] = textEncode($message['DESCR']);
      }


      if ($folder_id == 3) {
	$message['EDIT'] = $row;
      }
      if ($folder_id == 1) {
	$message['REPLY'] = $row;
      }
      if (isset($_POST['edit_pm'])) {
        while (list($key, $val) = each($row)) {
          $PRESET_VARS[strtolower($key)] = $val;
          $message['EDITING'][$key] = $val;
        }
      }
      if (isset($_GET['reply_pm'])) {
        $message['EDITING']['X'] = 1;
	$PRESET_VARS['subject'] = "Re: ".$row['SUBJECT'];
	$PRESET_VARS['receipient'] = $row['AUTHOR'];
	$PRESET_VARS['descr'] = "<div style=\"background-color:#AAA;color:#FFF\" class=\"quotetitle\">".$row['AUTHOR']."</div><div style=\"background-color:#E7FFE7\" class=\"quotecontent\">".$row['DESCR']."</div><br>";
      }
    }
    
    return $message;
  }

  function getFolder($folder_id,$page=1,$perpage=PAGE_SIZE, $filter_user='') {
    global $smarty;
    global $db;

    // content
    $folder = $this->getFolderData($folder_id, $page, $perpage, $filter_user);

    $smarty->assign("folder", $folder);
    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/bar_folder.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_folder.smarty'.($stop-$start);

    $this->rows = $folder['_ROWS'];	

    return $content;     
  }

  function getFolderData ($folder_id, $page=1,$perpage=PAGE_SIZE, $filter_user=''){
    global $db;
    global $_SESSION;
    global $_SERVER;
    global $auth;
    global $frm;
    global $pm_folders;

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $sql_user = '';
    if (!empty($filter_user)) {
      if ($folder_id == 2)
        $sql_user = " AND ((U1.USER_NAME like '%". $filter_user."%') OR (FG.GROUP_NAME like '%". $filter_user."%'))";
      else if ($folder_id == 1)
        $sql_user = " AND U.USER_NAME like '%". $filter_user."%'";
    }
  
    $count = 0;
    $sql_count = "SELECT COUNT(DISTINCT PM.PM_ID) ROWS
                   FROM pm_folder_messages PFM,	pm_message PM
                        left join users U ON PM.SENDER_ID=U.USER_ID,
			pm_message_receiver PMR
			left join users U1 ON PMR.RECEIVER_ID=U1.USER_ID AND RECEIVER_TYPE=1
			left join forum_groups_details FG ON PMR.RECEIVER_ID=FG.GROUP_ID AND PMR.RECEIVER_TYPE=2 and LANG_ID=".$_SESSION['lang_id']."
                   WHERE PM.PM_ID=PFM.MESSAGE_ID
			AND PM.PM_ID =PMR.PM_ID
			AND PFM.USER_ID =".$auth->getUserId()."
			AND PFM.FOLDER_ID=".$folder_id.$sql_user; 
    $db->query($sql_count);
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    // get ids
    $sql =  "SELECT PM.PM_ID
                   FROM pm_folder_messages PFM,	pm_message PM
			left join users U ON PM.SENDER_ID=U.USER_ID,
		        pm_message_receiver PMR
                   WHERE PM.PM_ID=PFM.MESSAGE_ID
			AND PFM.USER_ID =".$auth->getUserId()."
			AND PM.PM_ID =PMR.PM_ID
			AND PFM.FOLDER_ID=".$folder_id.$sql_user." 
		   ORDER BY PM.SENT_DATE DESC "	
			.$limitclause;
//echo $sql;
    $db->query($sql);
    $ids = "";
    $pre = "";
    while ($row = $db->nextRow()) {
      $ids .= $pre.$row['PM_ID']; 
      $pre = ",";
    }
  
    $sql =  "SELECT PM.*, U.USER_NAME AS AUTHOR, PFM.RECEIVED,
                    GROUP_CONCAT(DISTINCT U1.USER_NAME ORDER BY U1.USER_NAME SEPARATOR ', ') AS USER_RECEIPIENT, 
                    GROUP_CONCAT(DISTINCT FG.GROUP_NAME ORDER BY FG.GROUP_NAME SEPARATOR ', ') AS GROUP_RECEIPIENT, 
			".$folder_id." as FOLDER_ID
                   FROM pm_folder_messages PFM,	pm_message PM
			left join users U ON PM.SENDER_ID=U.USER_ID,
		        pm_message_receiver PMR
			left join users U1 ON PMR.RECEIVER_ID=U1.USER_ID AND RECEIVER_TYPE=1
			left join forum_groups_details FG ON PMR.RECEIVER_ID=FG.GROUP_ID AND PMR.RECEIVER_TYPE=2 and LANG_ID=".$_SESSION['lang_id']."

                   WHERE PM.PM_ID=PFM.MESSAGE_ID
                        AND PM.PM_ID IN (".$ids.")
			AND PFM.USER_ID =".$auth->getUserId()."
			AND PM.PM_ID =PMR.PM_ID
			AND PFM.FOLDER_ID=".$folder_id.$sql_user." 
                   GROUP BY PM.PM_ID
		   ORDER BY PM.SENT_DATE DESC ";

    $db->query($sql);
//echo $sql;
    $c = 0;
    $folder['FOLDER'] = $pm_folders[$folder_id];
    while ($row = $db->nextRow()) {
      $item = $row; 
      if ($row['RECEIVED'] == 0 && $folder_id==1) {
        $item['CLOSED'] = 1;       
      }
      if ($folder_id == 3) {
	$item['EDIT'][0] = $row;
      }

      if ($row['SENDER_ID'] == -1)
        $item['SYSTEM'] = true;
      if ($folder_id == 1) {
	$item['USER_NAME'] = $row['AUTHOR'];
      } 
      else {
        if (!empty($row['USER_RECEIPIENT']))  
          $item['USER_NAME'] = $row['USER_RECEIPIENT'];
        if (!empty($row['GROUP_RECEIPIENT']))  
          $item['GROUP_NAME'] = $row['GROUP_RECEIPIENT'];
      }

      $folder['ITEMS'][] = $item;
    }
   
   if ($folder_id == 1 || $folder_id == 2) {
     $folder['SEARCH']['WHERE'] = $frm->getInput(FORM_INPUT_TEXT, 'filter_user', isset($filter_user) ? $filter_user : '', array('class' => 'input'));
     $folder['SEARCH']['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '');
     $folder['SEARCH']['FOLDER_ID'] = $folder_id;

     if (!empty($_GET['filter_user'])) {
       $folder['SEARCH']['FILTERED']['FOLDER_ID'] = $folder_id;
     }
   }

  //  echo $count;
    $folder['_ROWS'] = $count;
      // no records?
    return $folder;
  }

  function addPM($pm_id = '') {
    global $db;
    global $_POST;
    global $langs;
    global $auth;
    global $_SESSION;
    global $conf_site_url;

    $trust = new Trust();
    $error=FALSE;
    $_POST['descr'] .= $_POST['simple_text'];

    $r_fields=array('subject', 'descr');
    $s_fields_d=array('subject', 'descr');
    $i_fields_d='';
    $d_fields_d='';
    $c_fields_d='';

    if(!requiredFieldsOk($r_fields, $_POST)){
	$error=TRUE;
	//$data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];        
    };
    if(!$error){

	$sdata=buildSaveData($s_fields_d,$i_fields_d,$d_fields_d,$c_fields_d,$_POST);
	$sdata['SENT_DATE'] = "NOW()";
	$sdata['SENDER_ID'] = $auth->getUserId();
	// proceed to database updates
	if($pm_id != ''){
		// UPDATE
                if (!$this->containsReceipients($pm_id, $pm_id))
                  return 1;
		$db->update('pm_message', $sdata, 'PM_ID='.$pm_id);
		$this->updateReceipients($pm_id, $pm_id);
	}else{
		// INSERT
                if (!$this->containsReceipients(-1, $pm_id))
                  return 1;

		$db->insert('pm_message', $sdata);
		$pm_id = $db->id();
		$this->updateReceipients(-1, $pm_id);
	};
	// redirect to news page
    }
    return 0;
  }

  function getAddReceipientsBox() {
    global $auth;
    global $db;
    global $_GET;
    global $_SESSION;
    global $smarty;

    if (!$auth->userOn()) {
       $content = $langs['LANG_ERROR_PM_LOGIN_U'];
    }
    else {
      $content = '';
      $pm = new PM();
      $pm_id = -1;
      if (isset($_POST['pm_id'])) {
        $pm_id = $_POST['pm_id'];
        $sql =  "SELECT U1.USER_NAME, U1.USER_ID, FG.GROUP_NAME, FG.GROUP_ID
                   FROM pm_message PM
                        left join users U ON PM.SENDER_ID=U.USER_ID,
			pm_message_receiver PMR
			left join users U1 ON PMR.RECEIVER_ID=U1.USER_ID AND RECEIVER_TYPE=1
			left join forum_groups_details FG ON PMR.RECEIVER_ID=FG.GROUP_ID AND PMR.RECEIVER_TYPE=2 and LANG_ID=".$_SESSION['lang_id']."

                   WHERE PM.PM_ID=".$pm_id." 
			AND PM.PM_ID =PMR.PM_ID";
        $db->query($sql);
        while ($row = $db->nextRow()) {
          if ($row['USER_NAME'] != '') {
            $_SESSION['_user']['PM'][$pm_id]['USER_RECEIPIENTS'][$row['USER_ID']]['USER_NAME'] = $row['USER_NAME'];
            $_SESSION['_user']['PM'][$pm_id]['USER_RECEIPIENTS'][$row['USER_ID']]['USER_ID'] = $row['USER_ID'];
          }
          if ($row['GROUP_NAME'] != '') {
            $_SESSION['_user']['PM'][$pm_id]['GROUP_RECEIPIENTS'][$row['GROUP_ID']]['GROUP_NAME'] = $row['GROUP_NAME'];
            $_SESSION['_user']['PM'][$pm_id]['GROUP_RECEIPIENTS'][$row['GROUP_ID']]['GROUP_ID'] = $row['GROUP_ID'];
          }
        }
      }
      $smarty->assign("pm_id", $pm_id);    
      if (isset($_GET['mode']) && $_GET['mode'] == 'users' && isset($_GET['text']) ) {
        $user_names = explode(";", $_GET['text']);
        $bad_user_names = "";  
        foreach($user_names as $user_name)
         if (!empty($user_name)) {
           $user = new User();
           $user_id = $user->getUserIdFromUsername($user_name);
           if ($user_id > 0) {
             $_SESSION['_user']['PM'][$pm_id]['USER_RECEIPIENTS'][$user_id]['USER_NAME'] = $user_name;
             $_SESSION['_user']['PM'][$pm_id]['USER_RECEIPIENTS'][$user_id]['USER_ID'] = $user_id;
           } else $bad_user_names .= $user_name.";";
         }
        if ($bad_user_names != "") {
           $smarty->assign("user_name_error", $bad_user_names);    
           $smarty->assign("pm_users", str_replace(";", "\n", $bad_user_names));    
        }
      }  
      $user_receipients = array();
      if (!empty($_SESSION['_user']['PM'][$pm_id]['USER_RECEIPIENTS'])) {
        foreach($_SESSION['_user']['PM'][$pm_id]['USER_RECEIPIENTS'] as $user_name)
          if (!empty($user_name))
            $user_receipients[] = $user_name;
      }
      if (count($user_receipients) > 0) 
        $smarty->assign("user_receipients", $user_receipients);    

      if (isset($_GET['mode']) && $_GET['mode'] == 'groups' && isset($_GET['pm_groups']) && $auth->isGroupModerator()) {
        $groups = explode("|", $_GET['pm_groups']);
        foreach($groups as $group) {
          $group_name = explode(";", $group);
          if (!empty($group_name[1])) {
            $_SESSION['_user']['PM'][$pm_id]['GROUP_RECEIPIENTS'][$group_name[0]]['GROUP_NAME'] = $group_name[1];
            $_SESSION['_user']['PM'][$pm_id]['GROUP_RECEIPIENTS'][$group_name[0]]['GROUP_ID'] = $group_name[0];
          }
        }
      }

      $group_receipients = array();
      if (!empty($_SESSION['_user']['PM'][$pm_id]['GROUP_RECEIPIENTS'])) {
        foreach($_SESSION['_user']['PM'][$pm_id]['GROUP_RECEIPIENTS'] as $group)
          if (!empty($group)) {        
            $group_receipients[] = $group;
          }
      }
      if (count($group_receipients) > 0) 
        $smarty->assign("group_receipients", $group_receipients);    

      if ($auth->isGroupModerator() || $auth->isClanLeader()) {
        $groups = inputUsersModeratedGroups ("pm_group");
        $smarty->assign("pm_group", $groups);    
      }

      $start = getmicrotime();
      $content = $smarty->fetch('smarty_tpl/add_receipients.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/add_receipients.smarty'.($stop-$start);
    }
    return $content;
  }

  function containsReceipients($mode, $pm_id) {
    $receipients = 0;
    if (isset($_SESSION['_user']['PM'][$mode]['USER_RECEIPIENTS'])) 
      $receipients += count($_SESSION['_user']['PM'][$mode]['USER_RECEIPIENTS']);
    if (isset($_SESSION['_user']['PM'][$mode]['GROUP_RECEIPIENTS'])) 
      $receipients += count($_SESSION['_user']['PM'][$mode]['GROUP_RECEIPIENTS']);
    if ($receipients != 0)
      return true;
    else return false;
  }

  function updateReceipients($mode, $pm_id) {
    global $db;
    global $_SESSION;
    global $_POST;
    global $auth;

    $db->query("start transaction");
    if ($mode > 0) {
      $db->delete('pm_message_receiver', 'PM_ID='.$pm_id);
      $db->delete('pm_folder_messages', 'PM_ID='.$pm_id);
    }

    if (isset($_SESSION['_user']['PM'][$mode]['USER_RECEIPIENTS'])) {
      foreach($_SESSION['_user']['PM'][$mode]['USER_RECEIPIENTS'] as $user) {
        unset($sdata);
        $sdata['PM_ID'] = $pm_id;
        $sdata['RECEIVER_TYPE'] = 1;
        $sdata['RECEIVER_ID'] = $user['USER_ID'];
        $db->insert('pm_message_receiver', $sdata);
        unset($sdata);
        $sdata['FOLDER_ID'] = 1;
        $sdata['MESSAGE_ID'] = $pm_id;
        $sdata['USER_ID'] = $user['USER_ID'];
        $db->insert('pm_folder_messages', $sdata);
  
        if ($mode==-1) {
          $receiver = new User();
          $receiver_id = $receiver->getUserIdFromId($user['USER_ID']);
          if ($receiver->getPM_Email()) {
            $notification = new notification();
  	    $notification->sendPMEmail($receiver->user_name, $receiver->email, $_SESSION['_user']['USER_NAME'], $_POST['subject'], $pm_id, $receiver->last_lang);
          } 
        }  
      }
      unset($_SESSION['_user']['PM'][$mode]['USER_RECEIPIENTS']);
    }

    unset($sdata);
    $sdata['FOLDER_ID'] = 3;
    $sdata['USER_ID'] = $auth->getUserId();
    $sdata['MESSAGE_ID'] = $pm_id;
    $db->insert('pm_folder_messages', $sdata);

    if (isset($_SESSION['_user']['PM'][$mode]['GROUP_RECEIPIENTS'])) {
      foreach($_SESSION['_user']['PM'][$mode]['GROUP_RECEIPIENTS'] as $group) {
        unset($sdata);
        $sdata['PM_ID'] = $pm_id;
        $sdata['RECEIVER_TYPE'] = 2;
        $sdata['RECEIVER_ID'] = $group['GROUP_ID'];
        $db->insert('pm_message_receiver', $sdata);
        $db->select('forum_groups_members', '*', 'GROUP_ID='.$group['GROUP_ID'].' AND USER_ID <> '.$auth->getUserId());
        $members = array();
        while ($row = $db->nextRow()) {
          $members[] = $row;
        }
        foreach ($members as $member) {
          if (!isset($_SESSION['_user']['PM'][$mode]['USER_RECEIPIENTS'][$member['USER_ID']])) {
            unset($sdata);
            $sdata['FOLDER_ID'] = 1;
            $sdata['MESSAGE_ID'] = $pm_id;
            $sdata['USER_ID'] = $member['USER_ID'];
            $db->insert('pm_folder_messages', $sdata);
            if ($mode==-1) {
              $receiver = new User();
              $receiver_id = $receiver->getUserIdFromId($member['USER_ID']);
              if ($receiver->getPM_Email()) {
                $notification = new notification();
	        $notification->sendPMEmail($receiver->user_name, $receiver->email, $_SESSION['_user']['USER_NAME'], $_POST['subject'], $pm_id, $receiver->last_lang);
              } 
            }  
          }
        }     
      }
      unset($_SESSION['_user']['PM'][$mode]['GROUP_RECEIPIENTS']);
    }
    $db->query("commit");
  }

}   
?>