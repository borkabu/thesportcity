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

class Notification {
 
  function Notification() {
  }

  function removeTopicEmails($topic_id, $user_id) {
    global $db;
    $db->delete('notification_email', "TOPIC_ID=".$topic_id." AND USER_ID=".$user_id." AND EMAIL_TYPE=1 AND SENT=0");

  }

  function trackTopic($topic_id, $user_id, $forum_id) {
    global $db;

    $sql="REPLACE into topic_track (topic_id, user_id, forum_id, mark_time) 
			VALUES  (".$topic_id.", ".$user_id.", ".$forum_id.", NOW())";
    $db->query($sql);
  } 

  function markAllRead($forum_id = '', $cat_id = '') {
    global $auth;
    global $db;
    global $_SESSION;
 
    if ($auth->userOn()) {
      $filter = '';
      if (!empty($forum_id) && is_numeric($forum_id)) 
        $filter = " AND F.FORUM_ID=".$forum_id;
      if (!empty($cat_id) && is_numeric($cat_id)) 
        $filter .= " AND F.CAT_ID=".$cat_id;

      $sql = "REPLACE into topic_track (topic_id, user_id, forum_id, mark_time) 
		SELECT T.TOPIC_ID, ".$auth->getUserId()." as USER_ID, F.FORUM_ID, NOW()
	            FROM forum_details FD, forum F left 
			join topic T ON T.FORUM_ID=F.FORUM_ID AND T.LANG_ID IN (".$_SESSION['lang_id'].",0) AND T.PUBLISH ='Y' 
                	left join topic_track TT ON T.TOPIC_ID=TT.TOPIC_ID AND TT.USER_ID=".$auth->getUserId()." 
		WHERE F.PUBLISH='Y' 
			AND F.FORUM_ID=FD.FORUM_ID 
			".$filter."
			AND FD.LANG_ID IN (".$_SESSION['lang_id'].",0) 
			AND (TT.MARK_TIME IS NULL OR TT.MARK_TIME < T.LAST_POSTED )
		GROUP BY T.TOPIC_ID ";

      $db->query($sql);
      
    }   
  }

  function populateTopicEmails($topic_id, $exclude) {
    global $db;

    $sql="INSERT into notification_email (topic_id, user_id, email_type) 
			SELECT topic_id, user_id, 1 FROM topic_subscribe 
				WHERE topic_id =".$topic_id." 
					AND user_ID not in (select user_id from notification_email where sent=0 and topic_id=".$topic_id." )
					AND user_ID <> ".$exclude;

    $db->query($sql);
  } 

  function bulkSendTopicEmails() {
    global $db;   
    $sql="SELECT DISTINCT NE.TOPIC_ID, NE.USER_ID, NE.EMAIL_TYPE, U.USER_NAME, U.EMAIL, U.LAST_LANG, T.TOPIC_ID, L.SHORT_CODE, T.TOPIC_NAME, T.FORUM_ID
		FROM notification_email NE, users U , topic_subscribe TS, topic T, languages L
		WHERE NE.TOPIC_ID = TS.TOPIC_ID
			and NE.USER_ID=U.USER_ID
			and T.TOPIC_ID=TS.TOPIC_ID
			and T.LANG_ID=L.ID
			and U.EMAIL_VERIFIED='Y'
			and NE.SENT=0";

    $db->query($sql);
    $emails = '';
    $c = 0;
    while ($row = $db->nextRow()) {
       $emails[$c] = $row;
       $c++;
    }
    for ($i=0; $i<$c; $i++) {
      if ($this->sendTopicEmail($emails[$i]['USER_NAME'], $emails[$i]['EMAIL'], $emails[$i]['TOPIC_NAME'], $emails[$i]['TOPIC_ID'], $emails[$i]['FORUM_ID'], $emails[$i]['LAST_LANG'], $emails[$i]['SHORT_CODE'])) {
        unset($sdata);
	$sdata['SENT'] = 1;
	$sdata['SENT_DATE'] = 'NOW()';
        $db->update('notification_email', $sdata, "TOPIC_ID=".$emails[$i]['TOPIC_ID']." AND USER_ID=".$emails[$i]['USER_ID']." AND EMAIL_TYPE=1 AND SENT=0");
      } 
    }
  }

  function sendTopicEmail($user_name, $email, $topic_title, $topic_id, $forum_id, $lang, $lang_id) {
      global $_SESSION;
      global $langs;
      global $conf_site_url;
      global $conf_home_dir;

      include($conf_home_dir.'class/ss_lang_'.$lang.'.inc.php');

      $to = $email;
      $subject = $langs['LANG_EMAIL_TOPIC_NOTIFY_SUBJECT'].$topic_title;   

      $email = new Email($langs, $lang);
      $sdata['USER_NAME'] = $user_name;
      $sdata['TOPIC_NAME'] = $topic_title;
      $sdata['URL'] = $conf_site_url.'forum.php?topic_id='.$topic_id.'&lang_id='.$lang_id;
      $sdata['URL2'] = $conf_site_url.'forum.php?forum_id='.$forum_id.'&lang_id='.$lang_id;
      $email->getEmailFromTemplate ('email_topic_notify', $sdata) ;
      return $email->send($to, $subject);

  }

  function sendPMEmail($user_name, $email, $author, $subject, $message_id, $lang) {
      global $_SESSION;
      global $langs;
      global $conf_site_url;
      global $conf_home_dir;

      include($conf_home_dir.'class/ss_lang_'.$lang.'.inc.php');

      $to = $email;

      $email = new Email($langs, $lang);
      $sdata['USER_NAME'] = $user_name;
      $sdata['TOPIC_NAME'] = $subject;
      $sdata['AUTHOR'] = $author;
      $sdata['URL'] = $conf_site_url.'compose_message.php?folder_id=1&message_id='.$message_id;
      $email->getEmailFromTemplate ('email_pm_notify', $sdata) ;
      return $email->save($to, $langs['LANG_EMAIL_PM_NOTIFY_SUBJECT'].$subject);

  }

  function sendTournamentProgressEmail($user_name, $email, $season_title, $tournament_id, $lang) {
      global $_SESSION;
      global $langs;
      global $conf_site_url;
      global $conf_home_dir;

      include($conf_home_dir.'class/ss_lang_'.$lang.'.inc.php');

      $to = $email;

      $subject = $langs['LANG_EMAIL_TOURNAMENT_PROGRESS_SUBJECT'];
      $email = new Email($langs, $lang);
      $sdata['USER_NAME'] = $user_name;
      $sdata['SEASON_TITLE'] = $season_title;
      $sdata['URL'] = $conf_site_url.'f_manager_tournaments.php?mt_id='.$tournament_id;
      $email->getEmailFromTemplate ('email_tournament_progress', $sdata) ;
      return $email->save($to, $subject);
  }


  function sendTournamentWinnerEmail($user_name, $email, $tournament_title, $tournament_id, $lang) {
      global $_SESSION;
      global $langs;
      global $conf_site_url;
      global $conf_home_dir;

      include($conf_home_dir.'class/ss_lang_'.$lang.'.inc.php');

      $to = $email;

      $subject = $langs['LANG_EMAIL_TOURNAMENT_WINNER_SUBJECT'];
      $email = new Email($langs, $lang);
      $sdata['USER_NAME'] = $user_name;
      $sdata['TOURNAMENT_TITLE'] = $tournament_title;
      $sdata['URL'] = $conf_site_url.'f_manager_tournaments.php?mt_id='.$tournament_id;
      $sdata['URL2'] = $conf_site_url.'f_manager_tournaments.php?mt_id='.$tournament_id;
      $email->getEmailFromTemplate ('email_tournament_winner', $sdata) ;
      return $email->save($to, $subject);

  }

  function sendStockProfitEmail($user_name, $email, $season_title, $player_name, $season_id, $lang, $lang_id) {
      global $_SESSION;
      global $langs;
      global $conf_site_url;
      global $conf_home_dir;

      include($conf_home_dir.'class/ss_lang_'.$lang.'.inc.php');

      $to = $email;
      $subject = $langs['LANG_EMAIL_STOCK_PROFIT_NOTIFY_SUBJECT'].$player_name;   

      $email = new Email($langs, $lang);
      $sdata['USER_NAME'] = $user_name;
      $sdata['SEASON_TITLE'] = $season_title;
      $sdata['PLAYER_NAME'] = $player_name;
      $sdata['URL'] = $conf_site_url.'f_manager_stock_exchange.php?mseason_id='.$season_id.'&lang_id='.$lang_id;
      $email->getEmailFromTemplate ('email_stock_profit_notify', $sdata) ;
      return $email->send($to, $subject);

  }

  function populateStockProfitEmails($season_id, $player_id) {
    global $db;

    $sql="INSERT into manager_stock_exchange_notification (season_id, player_id, user_id, email_type) 
		SELECT MMS.season_id, MMS.player_id, MSE.user_id, 2
		FROM manager_stock_exchange MSE, manager_market_stats MMS, manager_market MM
		WHERE MSE.season_id=MMS.season_id
		and MSE.player_id=MMS.player_id
		and MM.user_id=MMS.player_id
		and MM.season_id=MMS.season_id
		and MSE.size > 0
		and MSE.notify = 1
		and MSE.buying_price < 1+MMS.TEAMS/10+MMS.SHARES/1000-0.8-MMS.PENALTY/10 + MM.CURRENT_VALUE_MONEY/50000 
		AND MSE.SEASON_ID=".$season_id."
		and MSE.player_id=".$player_id."
	  ON DUPLICATE KEY UPDATE sent=0";
    $db->query($sql);
  } 

  function bulkSendStockProfitEmails() {
    global $db;   
    $sql="SELECT DISTINCT MSEN.USER_ID, MSEN.EMAIL_TYPE, U.USER_NAME, U.EMAIL, U.LAST_LANG, L.SHORT_CODE,
		MSD.SEASON_TITLE, B.LAST_NAME, B.FIRST_NAME, MSEN.SEASON_ID, MSEN.PLAYER_ID
		FROM manager_stock_exchange_notification MSEN, manager_stock_exchange MSE,  
			users U, busers B, manager_seasons_details MSD, languages L
		WHERE MSEN.SEASON_ID = MSE.SEASON_ID
			AND MSEN.PLAYER_ID = MSE.PLAYER_ID
			AND MSEN.USER_ID = MSE.USER_ID
			and MSEN.USER_ID=U.USER_ID
			and MSEN.PLAYER_ID=B.USER_ID
			and MSEN.season_ID=MSD.SEASON_ID and MSD.LANG_ID=L.ID
			and L.SHORT_CODE=U.LAST_LANG
			and L.ID=MSD.LANG_ID
			and U.EMAIL_VERIFIED='Y'
			and MSEN.SENT=0";

    $db->query($sql);
    $emails = '';
    $c = 0;
    while ($row = $db->nextRow()) {
       $emails[$c] = $row;
       $c++;
    }
    for ($i=0; $i<$c; $i++) {
      if ($this->sendStockProfitEmail($emails[$i]['USER_NAME'], $emails[$i]['EMAIL'], $emails[$i]['SEASON_TITLE'], $emails[$i]['LAST_NAME']." ".$emails[$i]['FIRST_NAME'], $emails[$i]['SEASON_ID'], $emails[$i]['LAST_LANG'], $emails[$i]['SHORT_CODE'])) {
        unset($sdata);
	$sdata['SENT'] = 1;
	$sdata['SENT_DATE'] = 'NOW()';
        $db->update('manager_stock_exchange_notification', $sdata, "SEASON_ID=".$emails[$i]['SEASON_ID']." AND USER_ID=".$emails[$i]['USER_ID']." AND PLAYER_ID=".$emails[$i]['PLAYER_ID']." AND EMAIL_TYPE=2 AND SENT=0");
	unset($sdata);
	$sdata['NOTIFY'] = 0;
        $db->update('manager_stock_exchange', $sdata, "SEASON_ID=".$emails[$i]['SEASON_ID']." AND USER_ID=".$emails[$i]['USER_ID']." AND PLAYER_ID=".$emails[$i]['PLAYER_ID']);
      } 
    }
  }

  function populateReminderEmails($type) {
    global $db;

    // check if time OK
    if ($type == 1) {
	$sql="SELECT MT.*, MT1.END_DATE AS START_POINT,
	(unix_timestamp( MT.START_DATE ) - unix_timestamp(NOW() ) )/ 3600 <= 4 AS FIRST_WARNING,
	(unix_timestamp( MT.START_DATE ) - unix_timestamp(NOW() ) )/ 3600 <= 1 AS SECOND_WARNING
             FROM manager_tours MT
                   left join manager_tours MT1 ON MT.SEASON_ID = MT1.SEASON_ID AND MT1.NUMBER = MT.NUMBER - 1
             WHERE NOW() <=  MT.START_DATE 
                   AND NOW() >= DATE_ADD(MT.START_DATE, INTERVAL -4 HOUR)
		   AND MT1.END_DATE < NOW()";
//echo $sql;
        $db->query($sql);
        $seasons = "";
        $c = 0;
        while ($row = $db->nextRow()) {
          $seasons[$c] = $row;
          $c++;
        }

        for ($i=0; $i<$c; $i++) {        
          $filter = "";
          $reminder_type = 0;
          if ($seasons[$i]['SECOND_WARNING'] == 1) {
           $filter = " and MU.COMPLETENESS < 90 ";
	   $reminder_type = 2;
          } else if ($seasons[$i]['FIRST_WARNING'] == 1) {
           $filter = " and MU.LAST_REVIEWED < '".$seasons[$i]['START_POINT']."'";
	   $reminder_type = 1;
          }
          if ($filter != "") {
            // construct sql depending on that
            $sql="INSERT IGNORE into reminder_notification (season_id, tour_id, type, user_id, email_type, reminder_type) 
		SELECT RS.season_id, ".$seasons[$i]['NUMBER'].",".$type.", RS.user_id, 2, ".$reminder_type."
		FROM  reminder_subscribe RS, manager_users MU, users U
		WHERE RS.season_id=MU.season_id
		and RS.user_id=MU.user_id
		and U.user_id=MU.user_id
		and U.LAST_LANG <> ''
	        ".$filter."
		and U.EMAIL_VERIFIED='Y'
		AND RS.SEASON_ID=".$seasons[$i]['SEASON_ID'];
//echo $sql;
          $db->query($sql);
          }
        } 
    }
  } 

  function bulkSendReminderEmails() {
    global $db;   
    $sql="SELECT DISTINCT RN.USER_ID, RS.UNSUBSCRIBE_KEY, RN.REMINDER_TYPE, U.USER_NAME, U.EMAIL, U.LAST_LANG, L.SHORT_CODE,
		MSD.SEASON_TITLE, RN.SEASON_ID, DATE_ADD(MT.START_DATE, INTERVAL U.TIMEZONE*60 MINUTE) AS START_DATE, U.TIMEZONE, MT.NUMBER
		FROM reminder_notification RN, manager_tours MT, reminder_subscribe RS,
			users U, manager_seasons_details MSD, languages L
		WHERE  RN.USER_ID=U.USER_ID
			and RN.season_ID=MSD.SEASON_ID and MSD.LANG_ID=L.ID
			and MT.season_id=RN.SEASON_ID and MT.NUMBER=RN.TOUR_ID
			and L.SHORT_CODE=U.LAST_LANG
			and L.ID=MSD.LANG_ID
			and U.EMAIL_VERIFIED='Y'
			and RN.SENT=0
                        AND RN.USER_ID=RS.USER_ID
                        AND RS.season_id=RN.SEASON_ID
			AND RS.type=RN.type
                LIMIT 100";

    $db->query($sql);
    $emails = '';
    $c = 0;
    while ($row = $db->nextRow()) {
       $emails[$c] = $row;
       $c++;
    }
    unset($sdata);
    for ($i=0; $i<$c; $i++) {
      if ($this->sendReminderEmail($emails[$i])) {
        unset($sdata);
	$sdata['SENT'] = 1;
	$sdata['SENT_DATE'] = 'NOW()';
        $db->update('reminder_notification', $sdata, "SEASON_ID=".$emails[$i]['SEASON_ID']." AND USER_ID=".$emails[$i]['USER_ID']." AND TOUR_ID=".$emails[$i]['NUMBER']." AND EMAIL_TYPE=2 AND SENT=0 AND REMINDER_TYPE=".$emails[$i]['REMINDER_TYPE']);
      } 
    }
  }

  function sendReminderEmail($info) {
    global $_SESSION;
    global $langs;
    global $conf_site_url;
    global $conf_home_dir;

    $user = new User(); 
    include($conf_home_dir.'class/ss_lang_'.$info['LAST_LANG'].'.inc.php');

    $to = $info['EMAIL'];
    $subject = $langs['LANG_EMAIL_REMINDER_NOTIFY_SUBJECT'.$info['REMINDER_TYPE']].": ".$info['SEASON_TITLE'];

    $email = new Email($langs, $info['LAST_LANG']);
    $sdata['USER_NAME'] = $info['USER_NAME'];
    $sdata['SEASON_TITLE'] = $info['SEASON_TITLE'];
    $sdata['START_DATE'] = $info['START_DATE']." ".$user->getUserTimezoneName($info['TIMEZONE']);
    $sdata['URL'] = $conf_site_url.'f_manager_control.php?mseason_id='.$info['SEASON_ID'].'&lang_id='.$info['LAST_LANG'];
    $sdata['URL_UNSUBSCRIBE'] = $conf_site_url.'f_manager_team_reminder.php?season_id='.$info['SEASON_ID'].'&user_id='.$info['USER_ID'].'&unsubscribe_key='.$info['UNSUBSCRIBE_KEY'];
    echo $email->getEmailFromTemplate ('email_reminder_notify', $sdata) ;
    return $email->send($to, $subject);

  }

  function bulkSendFLPexEmails() {
    global $db;   

    $sql = "SELECT U.USER_NAME, U.EMAIL, U.LAST_LANG, L.SHORT_CODE,
		   U1.USER_NAME as USER_NAME2, U1.USER_ID as USER2_ID2, RML.TITLE, 
		  B.LAST_NAME, B.FIRST_NAME, MSD.SEASON_TITLE, RMPEN.TYPE, MM.CURRENT_VALUE_MONEY,
		RMPE.*, RMPEC.USER_ID as OWNER, RMPEC.PLAYER_ID AS CPLAYER_ID, RMPEC.STATUS as CSTATUS
	    FROM manager_seasons_details MSD, languages L, 
		 rvs_manager_leagues RML, manager_market MM, 
                 rvs_manager_players_exchange_notification RMPEN, 
                 rvs_manager_players_exchange RMPE
			LEFT JOIN users U1 ON U1.USER_ID=RMPE.USER_ID
		        LEFT JOIN users U ON U.USER_ID=RMPE.USER_ID2,
		 rvs_manager_players_exchange_contract RMPEC
			LEFT JOIN busers B ON B.USER_ID=RMPEC.PLAYER_ID
	    WHERE  RMPEC.ENTRY_ID=RMPE.ENTRY_ID
		   AND RMPEC.LEAGUE_ID = RMPE.LEAGUE_ID
                   AND RMPEN.ENTRY_ID=RMPE.ENTRY_ID            
		   and RMPEN.TYPE=1
		   and RMPEN.SENT=0
		   and L.SHORT_CODE=U.LAST_LANG
		   and L.ID=MSD.LANG_ID
		   and MSD.SEASON_ID=RML.SEASON_ID 
           	   and RMPEN.LEAGUE_ID=RML.LEAGUE_ID 
		   and MM.SEASON_ID= RML.SEASON_ID 
		   AND MM.USER_ID= RMPEC.PLAYER_ID
      
            union

            SELECT U.USER_NAME, U.EMAIL, U.LAST_LANG, L.SHORT_CODE,
		   U1.USER_NAME as USER_NAME2, U1.USER_ID as USER2_ID2, RML.TITLE,
		  B.LAST_NAME, B.FIRST_NAME, MSD.SEASON_TITLE, RMPEN.TYPE, MM.CURRENT_VALUE_MONEY,
		RMPE.*, RMPEC.USER_ID as OWNER, RMPEC.PLAYER_ID AS CPLAYER_ID, RMPEC.STATUS as CSTATUS
	    FROM manager_seasons_details MSD, languages L, 
		 rvs_manager_leagues RML, manager_market MM, 
                 rvs_manager_players_exchange_notification RMPEN, 
                 rvs_manager_players_exchange RMPE
			LEFT JOIN users U ON U.USER_ID=RMPE.USER_ID
		        LEFT JOIN users U1 ON U1.USER_ID=RMPE.USER_ID2,
		 rvs_manager_players_exchange_contract RMPEC
			LEFT JOIN busers B ON B.USER_ID=RMPEC.PLAYER_ID
	    WHERE  RMPEC.ENTRY_ID=RMPE.ENTRY_ID
		   AND RMPEC.LEAGUE_ID = RMPE.LEAGUE_ID
                   AND RMPEN.ENTRY_ID=RMPE.ENTRY_ID            
		   and RMPEN.TYPE=2
		   and RMPEN.SENT=0
		   and L.SHORT_CODE=U.LAST_LANG
		   and L.ID=MSD.LANG_ID
		   and MSD.SEASON_ID=RML.SEASON_ID 
           	   and RMPEN.LEAGUE_ID=RML.LEAGUE_ID
		   and MM.SEASON_ID= RML.SEASON_ID 
		   AND MM.USER_ID= RMPEC.PLAYER_ID"; 

//echo $sql;
    $db->query($sql);
    $emails = array();
    while ($row = $db->nextRow()) {
       if ($row['TYPE'] == 1) {
         $emails[$row['USER_ID']]['USER'] = $row;
         $emails[$row['USER_ID']]['OFFERED_TRANSFERS'][$row['ENTRY_ID']]['ROW'] = $row;
         $emails[$row['USER_ID']]['OFFERED_TRANSFERS'][$row['ENTRY_ID']]['PLAYERS'][] = $row;
       } else {
         $emails[$row['USER_ID2']]['USER'] = $row;
         $emails[$row['USER_ID2']]['ACCEPTED_TRANSFERS'][$row['ENTRY_ID']]['ROW'] = $row;
         $emails[$row['USER_ID2']]['ACCEPTED_TRANSFERS'][$row['ENTRY_ID']]['PLAYERS'][] = $row;
       }
    }

//print_r($emails);
    foreach ($emails as $info) {
//print_r($info);
      if ($this->SendFLPexEmail($info)) {
        unset($sdata);
	$sdata['SENT'] = 1;
	$sdata['SENT_DATE'] = 'NOW()';
        $db->update('rvs_manager_players_exchange_notification', $sdata, "SENT=0");
      } 
    }
  }

  function SendFLPexEmail($info) {
      global $_SESSION;
      global $langs;
      global $conf_site_url;
      global $conf_home_dir;

      $user = new User(); 
      include($conf_home_dir.'class/ss_lang_'.$info['USER']['LAST_LANG'].'.inc.php');
      $to = $info['USER']['EMAIL'];
      $subject = $langs['LANG_EMAIL_RVS_LEAGUE_PEX_NOTIFY_SUBJECT'];

      $email = new Email($langs, $info['USER']['LAST_LANG']);
      $sdata['USER_NAME'] = $info['USER']['USER_NAME'];
      if (isset($info['OFFERED_TRANSFERS']))
        $sdata['OFFERED_TRANSFERS'] = $info['OFFERED_TRANSFERS'];
      if (isset($info['ACCEPTED_TRANSFERS']))
        $sdata['ACCEPTED_TRANSFERS'] = $info['ACCEPTED_TRANSFERS'];
      echo $email->getEmailFromTemplate ('email_rvs_league_player_exchange_notification', $sdata) ;
      return $email->send($to, $subject);
  }


  function bulkSendQueueEmails($users) {
    global $db;   

    $sql="SELECT DISTINCT *
		FROM notification_email_queue RN
		WHERE  RN.SENT=0
                LIMIT ".$users;
    $db->query($sql);
    $emails = '';
    $c = 0;
    while ($row = $db->nextRow()) {
       $emails[$c] = $row;
       $c++;
    }
    unset($sdata);
    for ($i=0; $i<$c; $i++) {
      $email = new Email();
      $email->setBody($emails[$i]['BODY']);
      if ($email->send($emails[$i]['EMAIL'], $emails[$i]['SUBJECT'])) {
        unset($sdata);
	$sdata['SENT'] = 1;
	$sdata['SENT_DATE'] = 'NOW()';
        $db->update('notification_email_queue', $sdata, "EMAIL_ID=".$emails[$i]['EMAIL_ID']);
      } 
    }
  }

}
?>