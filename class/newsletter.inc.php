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

class Newsletter {
  var $id;
  var $news;
  var $manager_news;
  var $manager_market_news;
  var $manager_tournaments;
  var $manager_tours;
  var $games;
 
  function Newsletter($id='') {
    $this->id = $id;
    $this->news = array();
    $this->games = array();
    $this->manager_news = array();
  }

  function subscribe($newsletter_id, $user_id, $add = false) {
    global $db;

      $sdata['ID'] = $newsletter_id;
      $sdata['USER_ID'] = $user_id;
      $sdata['SUBSCRIPTION_DATE'] = 'NOW()';
      $sdata['ACTIVE'] = 1;
      $actkey = gen_rand_string(0, 10);
      $sdata['UNSUBSCRIBE_KEY'] = "'".$actkey."'";
      if ($add)
        $db->insert('newsletter_subscribers', $sdata);  
      else $db->replace('newsletter_subscribers', $sdata);

  }

  function unsubscribe($newsletter_id, $user_id) {
    global $db;

    $sdata['ACTIVE'] = 0;
    $db->update('newsletter_subscribers', $sdata, 'USER_ID='.$user_id.' AND ID='.$newsletter_id);
  }

  function generateNewsletter() {
  }
  
  function generateGeneralNewsletter($queue_id, $user_id) {
     global $db;
     global $conf_site_url;
     global $conf_home_dir;
     global $tpl;

     $sql= "SELECT N.*, ND.*, NS.*, U.USER_NAME, L.SHORT_CODE, ND.LANG_ID, NQ.QUEUE_ID
		FROM newsletter N, newsletter_details ND, newsletter_queue NQ, 
			 newsletter_subscribers NS, users U, languages L
		WHERE N.ID=ND.ID AND NQ.QUEUE_ID=".$queue_id." 
			AND NS.USER_ID=".$user_id." 
			AND NS.ID=NQ.NEWSLETTER_ID
			AND NQ.NEWSLETTER_ID=ND.ID 
			and NS.USER_ID=U.USER_ID and L.SHORT_CODE=U.LAST_LANG 
			AND LENGTH(U.LAST_LANG) = 2
			AND NS.ACTIVE=1
			and U.EMAIL_VERIFIED='Y'
			AND L.ID=ND.LANG_ID";
     $db->query($sql);
     if ($row = $db->nextRow()) {
       $title = $row['TITLE'];
       $lang = $row['SHORT_CODE'];
       $lang_id = $row['LANG_ID'];

       include($conf_home_dir.'class/ss_lang_'.$lang.'.inc.php');
       while (list($key, $val) = each($langs)) {
         $fdata[$key] = $val;
       }

       $sdata = $this->generateGeneralNewsletterContent($row, $lang_id, $lang);
       $email = new Email();
       $sdata['PLAIN'] = $email->getEmailFromTemplate ('email_newsletter_general_plain', $sdata) ;
       $sdata['HTML'] = $email->getEmailFromTemplate ('email_newsletter_general_html', $sdata) ;

       $sdata['GENERATED'] = 1;
       $sdata['GENERATED_DATE'] = "NOW()";
       $sdata['SUBJECT'] = $title;
       $s_fields_d = array('HTML', 'PLAIN', 'SUBJECT');
       $i_fields_d = array('GENERATED','GENERATED_DATE');
       $d_fields_d = '';
       $c_fields_d  = '';
       $sdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d, $sdata, false, true);
       $db->update('newsletter_email_queue', $sdata, "USER_ID=".$user_id." AND QUEUE_ID=".$queue_id);
     }
  }

  function generateManagerNewsletter($queue_id, $season_id, $user_id) {
     global $db;
     global $tpl;
     global $conf_home_dir;

     $manager = new Manager($season_id);
     $user = $manager->getUser($user_id);
     if ($user != "") {
       $sql= "SELECT N.*, ND.*, NS.*, U.USER_NAME, L.SHORT_CODE, NQ.QUEUE_ID
		FROM newsletter N, newsletter_details ND, newsletter_queue NQ, 
			 newsletter_subscribers NS, users U, languages L
		WHERE N.ID=ND.ID AND NQ.QUEUE_ID=".$queue_id." 
			AND NS.USER_ID=".$user_id." 
			AND  NS.ID=NQ.NEWSLETTER_ID
			AND NQ.NEWSLETTER_ID=ND.ID 
			and NS.USER_ID=U.USER_ID and L.SHORT_CODE=U.LAST_LANG 
			AND LENGTH(U.LAST_LANG) = 2
			AND NS.ACTIVE=1
			and U.EMAIL_VERIFIED='Y'
			AND L.ID=ND.LANG_ID";

      $db->query($sql);
      if ($row = $db->nextRow()) {
        $title = $row['TITLE'];
        $lang = $row['SHORT_CODE'];
        $lang_id = $row['LANG_ID'];
        include($conf_home_dir.'class/ss_lang_'.$lang.'.inc.php');
        while (list($key, $val) = each($langs)) {
          $fdata[$key] = $val;
        }
        $sdata = $this->generateManagerNewsletterContent($row, $user, $season_id, $lang_id, $lang);
        $fdata['PLAIN'][0] = $sdata;
        $fdata['HTML'][0] = $sdata;
        unset($sdata);
        $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
        $tpl->setTemplateFile('tpl/email_newsletter_manager_plain.tpl.html');
        $tpl->addData($fdata);
        $sdata['PLAIN'] = $tpl->parse();
        $tpl->setTemplateFile('tpl/email_newsletter_manager_html.tpl.html');
        $tpl->addData($fdata);
        $sdata['HTML'] = $tpl->parse();
        
        $sdata['GENERATED'] = 1;
        $sdata['GENERATED_DATE'] = "NOW()";
        $sdata['SUBJECT'] = $title;
        $s_fields_d = array('HTML', 'SUBJECT', 'PLAIN');
        $i_fields_d = array('GENERATED','GENERATED_DATE');
        $d_fields_d = '';
        $c_fields_d  = '';
        $sdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d, $sdata, false, true);
        $db->update('newsletter_email_queue', $sdata, "USER_ID=".$user_id." AND QUEUE_ID=".$queue_id);
      }
    }
  }

  function generateWagerNewsletter($queue_id, $season_id, $user_id) {
     global $db;
     global $tpl;
     global $conf_home_dir;

     $wager = new Wager($season_id);
     $user = $wager->getUser($user_id);
     $sql= "SELECT N.*, ND.*, NS.*, U.USER_NAME, L.SHORT_CODE
		FROM newsletter N, newsletter_details ND, newsletter_queue NQ, 
			 newsletter_subscribers NS, users U, languages L
		WHERE N.ID=ND.ID AND NQ.QUEUE_ID=".$queue_id." 
			AND NS.USER_ID=".$user_id." 
			AND  NS.ID=NQ.NEWSLETTER_ID
			AND NQ.NEWSLETTER_ID=ND.ID 
			and NS.USER_ID=U.USER_ID and L.SHORT_CODE=U.LAST_LANG 
			AND LENGTH(U.LAST_LANG) = 2
			AND NS.ACTIVE=1
			and U.EMAIL_VERIFIED='Y'
			AND L.ID=ND.LANG_ID";

     $db->query($sql);
     if ($row = $db->nextRow()) {
       $title = $row['TITLE'];
//echo $title;
       $lang = $row['SHORT_CODE'];
       $lang_id = $row['LANG_ID'];
       include($conf_home_dir.'class/ss_lang_'.$lang.'.inc.php');
       while (list($key, $val) = each($langs)) {
         $fdata[$key] = $val;
       }
       $sdata = $this->generateWagerNewsletterContent($row, $user, $season_id, $lang_id, $lang);
       $fdata['PLAIN'][0] = $sdata;
       $fdata['HTML'][0] = $sdata;
       unset($sdata);
       $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
       $tpl->setTemplateFile('tpl/email_newsletter_wager_plain.tpl.html');
       $tpl->addData($fdata);
       $sdata['PLAIN'] = $tpl->parse();
       $tpl->setTemplateFile('tpl/email_newsletter_wager_html.tpl.html');
       $tpl->addData($fdata);
       $sdata['HTML'] = $tpl->parse();

       $sdata['GENERATED'] = 1;
       $sdata['GENERATED_DATE'] = "NOW()";
       $sdata['SUBJECT'] = $title;
       $s_fields_d = array('HTML', 'SUBJECT', 'PLAIN');
       $i_fields_d = array('GENERATED','GENERATED_DATE');
       $d_fields_d = '';
       $c_fields_d  = '';
       $sdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d, $sdata, false, true);
       $db->update('newsletter_email_queue', $sdata, "USER_ID=".$user_id." AND QUEUE_ID=".$queue_id);
     }
  }

  function generateGeneralNewsletterContent($row, $lang_id) {
       global $db;       
       global $conf_site_url;
       global $langs;       
       global $_SESSION; 
       global $page_size;            

       if (empty($perpage))
         $perpage = $page_size;
       if (empty($page))
         $page = 1;

       $user_id = $row['USER_ID'];
       $user_name = $row['USER_NAME'];
       $lang = $row['SHORT_CODE'];
       $lang_id = $row['LANG_ID'];
       $newsletter_id = $row['ID'];
       $queue_id = $row['QUEUE_ID'];
       $actkey = $row['UNSUBSCRIBE_KEY'];
       $last_release_date = $row['LAST_RELEASE_DATE'];
     // header: your are recieving this newsletter because you participate in...
       $sdata['TITLE'] = $row['TITLE'];
       $sdata['DESCR'] = $row['DESCR'];
       $sdata['HEADER'] = $row['HEADER'];
       $sdata['NEWSLETTER_ID'] = $newsletter_id;
       $sdata['QUEUE_ID'] = $queue_id;

     // newest announecements

       if (!isset($this->news[$lang_id])) {

         $newsbox = new AnnouncementBox($langs, $_SESSION['_lang']);
         $sdata['SITE_NEWS'] = $newsbox->getAnnouncementBox('', $page, $perpage, $last_release_date);
         $this->news[$lang_id] = $sdata['SITE_NEWS'];
       } else {
         $sdata['SITE_NEWS'] = $this->news[$lang_id];
       }
     // current games and prizes
     if (!isset($this->games[$lang_id])) {

       $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MSS.ALLOW_RVS_LEAGUES
          FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$lang_id."
          WHERE MSS.START_DATE < NOW( ) and MSS.END_DATE > NOW()
		AND MSS.PUBLISH='Y'
       ORDER BY MSS.START_DATE ASC";
       $db->query($sql);
       $c=0;
       while ($row = $db->nextRow()) {
         $season_id = $row['SEASON_ID'];
         $sdata['SITE_GAMES']['MANAGER'][$season_id] = $row;
         $sdata['SITE_GAMES']['MANAGER'][$season_id]['UTC']  = 'UTC';
         if ($row['ALLOW_RVS_LEAGUES'] == 'Y') {
           $sdata['SITE_GAMES']['RVS_MANAGER'][$season_id] = $row;
         }
         $c++;
       }

       $sql="SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE as SEASON_TITLE
          FROM wager_seasons MSS
		left JOIN wager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$lang_id."
          WHERE MSS.START_DATE < NOW( ) and MSS.END_DATE > NOW()
		AND MSS.PUBLISH='Y'
       ORDER BY MSS.START_DATE ASC";
       $db->query($sql);
       $c=0;
       while ($row = $db->nextRow()) {
         $season_id = $row['SEASON_ID'];
         $sdata['SITE_GAMES']['WAGER'][$season_id] = $row;
         $sdata['SITE_GAMES']['WAGER'][$season_id]['UTC']  = 'UTC';
         $c++;
       }

       $sql="SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE as SEASON_TITLE
          FROM bracket_seasons MSS
		left JOIN bracket_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$lang_id."
          WHERE MSS.START_DATE < NOW( ) and MSS.END_DATE > NOW()
		AND MSS.PUBLISH='Y'
       ORDER BY MSS.START_DATE ASC";
       $db->query($sql);
       $c=0;
       while ($row = $db->nextRow()) {
         $season_id = $row['SEASON_ID'];
         $sdata['SITE_GAMES']['ARRANGER'][$season_id] = $row;
         $sdata['SITE_GAMES']['ARRANGER'][$season_id]['UTC']  = 'UTC';
         $c++;
       }


       // tournaments RV
       /*$sql="SELECT MSS.MT_ID, MSD.SEASON_TITLE, MSS.REGISTRATION_END_DATE,
	          MSS.REGISTRATION_END_DATE > NOW() AS OPEN 
          FROM manager_tournament MSS
		left JOIN manager_tournament_details MSD ON MSS.MT_ID = MSD.MT_ID AND MSD.LANG_ID=".$lang_id."
          WHERE MSS.START_DATE < NOW( ) AND MSS.END_DATE > NOW()
       ORDER BY MSS.START_DATE ASC";
       $db->query($sql);
       $c=0;
       while ($row = $db->nextRow()) {
         $mt_id = $row['MT_ID'];
         $sdata['SITE_GAMES']['MANAGER_TOURNAMENT'][$mt_id] = $row;
         $sdata['SITE_GAMES']['MANAGER_TOURNAMENT'][$mt_id]['UTC']  = 'UTC';
         if ($row['OPEN'] == 1)
           $sdata['SITE_GAMES']['MANAGER_TOURNAMENT'][$mt_id]['REGISTRATION_OPEN'][0]['REGISTRATION_END_DATE'] = $row['REGISTRATION_END_DATE'];
         else 
           $sdata['SITE_GAMES'][0]['MANAGER_TOURNAMENT'][$mt_id]['REGISTRATION_CLOSED'][0]['X'] = 1;

         $c++;
       }*/
       $this->games[$lang_id] = $sdata['SITE_GAMES'];
     } else {
         $sdata['SITE_GAMES'] = $this->games[$lang_id];
     }
     // apology and unsubscribe info
       $sdata['URL'] = $conf_site_url.'newsletter_subscribe.php?action=unsubscribe&newsletter_id='.$newsletter_id.'&user_id='.$user_id.'&actkey='.$actkey;
       $sdata['USER_NAME'] = $user_name;
       return $sdata;
  }

  function generateManagerNewsletterContent($row, $user, $season_id, $lang_id) {
       global $db;       
       global $conf_site_url;

       $user_name = $row['USER_NAME'];
       $lang = $row['SHORT_CODE'];
       $newsletter_id = $row['ID'];
       $queue_id = $row['QUEUE_ID'];
       $actkey = $row['UNSUBSCRIBE_KEY'];
       $last_release_date = $row['LAST_RELEASE_DATE'];
//echo $last_release_date;
     // header: your are recieving this newsletter because you participate in...
       unset($sdata);
       $sdata['TITLE'] = $row['TITLE'];
       $sdata['DESCR'] = $row['DESCR'];
       $sdata['HEADER'] = $row['HEADER'];
       $sdata['NEWSLETTER_ID'] = $newsletter_id;
       $sdata['QUEUE_ID'] = $queue_id;
       $title = $row['TITLE'];
     // your team position, money spent, transactions left
       $sdata['PLACE'] = $user['PLACE'];
       $sdata['POINTS'] = $user['POINTS'];
       $sdata['MONEY'] = $user['MONEY'];
       $sdata['TRANSACTIONS'] = $user['TRANSACTIONS'];
     // new players on a market
     // players changed teams
     // players left tournament

       if (!isset($this->manager_market_news[$lang_id])) {
         $sql="SELECT COUNT(*) PLAYERS, EVENT_TYPE FROM manager_log ML WHERE event_date > '".$last_release_date."'
			AND EVENT_TYPE IN (4,5,6,7)
			and season_id=".$season_id."
			GROUP BY EVENT_TYPE
			ORDER BY EVENT_TYPE";	
         $db->query($sql);
         $c = 0;
         while ($row = $db->nextRow()) {       
	    $sdata['MARKET_NEWS'][0]['MARKET_NEWS_'.$row['EVENT_TYPE']][0]['PLAYERS'] = $row['PLAYERS'];
            $c++;
         }
         if ($c == 0)
           $sdata['MARKET_NEWS'][0]['NO_NEWS'][0]['X'] = 1;
         $this->manager_market_news[$lang_id] = $sdata['MARKET_NEWS'];
       } else {
         $sdata['MARKET_NEWS'] = $this->manager_market_news[$lang_id];
       }

     // market open and close times
     // next tour scheduled
       if (!isset($this->manager_tours[$lang_id])) {
          $sql = "SELECT MT.NUMBER, MT.START_DATE, MT.END_DATE, MT.START_DATE > NOW() CLOSE_TIME, MT.END_DATE > NOW() OPEN_TIME
             FROM manager_tours MT
		WHERE NOW() <= MT.END_DATE 
                   AND MT.SEASON_ID=".$season_id." 
		ORDER BY MT.START_DATE ASC limit 2";
          $db->query($sql);
          $cl = 0;
          $op = 0;
          $c = 0;
          while ($row = $db->nextRow()) {       
            if ($row['CLOSE_TIME'] == 1 && $cl == 0) {
	      $sdata['MARKET_TIME'][$row['START_DATE']]['MARKET_CLOSE_TIME'][0]['DATE'] = $row['START_DATE'];
              $cl++;
            }
            if ($row['OPEN_TIME'] == 1 && $op == 0) {
  	      $sdata['MARKET_TIME'][$row['END_DATE']]['MARKET_OPEN_TIME'][0]['DATE'] = $row['END_DATE'];
              $op++;
            }
            $c++;
          }
          if ($c == 0)
   	    $sdata['SEASON_OVER'][0]['X'] = 1;  
         $this->manager_tours[$lang_id] = $sdata['MARKET_TIME'];
       } else {
         $sdata['MARKET_TIME'] = $this->manager_tours[$lang_id];
       }

     // injured players on your team

     // related announcements
       if (!isset($this->manager_news[$lang_id])) {
         $sql="SELECT N.NEWS_ID, N.DATE_PUBLISHED, ND.TITLE, ND.LANG_ID, '".$lang."' as SHORT_CODE
			FROM news N, news_details ND 
		 WHERE N.DATE_PUBLISHED > '".$last_release_date."'
			AND N.PUBLISH = 'Y'
			AND N.SEASON_ID=".$season_id."
			AND N.news_id=ND.news_id 
			and ND.lang_id=".$lang_id."
			ORDER BY N.DATE_PUBLISHED DESC";	
         $db->query($sql);
         $c = 0;
         while ($row = $db->nextRow()) {       
	    $sdata['MANAGER_NEWS'][0]['ITEMS'][$c] = $row;
            $c++;
         }
         $this->manager_news[$lang_id] = $sdata['MANAGER_NEWS'];
       } else {
         $sdata['MANAGER_NEWS'] = $this->manager_news[$lang_id];
       }


       if (!isset($this->manager_tournaments[$lang_id])) {
         $sql="SELECT MSS.MT_ID, MSD.SEASON_TITLE, MSS.REGISTRATION_END_DATE,
                MSS.REGISTRATION_END_DATE > NOW() AS OPEN 
	       FROM manager_tournament MSS
			left JOIN manager_tournament_details MSD ON MSS.MT_ID = MSD.MT_ID AND MSD.LANG_ID=".$lang_id."
		       WHERE MSS.START_DATE < NOW( )  AND MSS.END_DATE > NOW()
				AND MSS.SEASON_ID=".$season_id."
		    ORDER BY MSS.START_DATE ASC";
	    $db->query($sql);
	    $c=0;
	    while ($row = $db->nextRow()) {
	      $mt_id = $row['MT_ID'];
	      $sdata['MANAGER_TOURNAMENT'][$mt_id] = $row;
	      $sdata['MANAGER_TOURNAMENT'][$mt_id]['UTC']  = 'UTC';
	      if ($row['OPEN'] == 1)
	        $sdata['MANAGER_TOURNAMENT'][$mt_id]['REGISTRATION_OPEN'][0]['REGISTRATION_END_DATE'] = $row['REGISTRATION_END_DATE'];
	      else 
        	$sdata['MANAGER_TOURNAMENT'][$mt_id]['REGISTRATION_CLOSED'][0]['X'] = 1;

	      $c++;
	    }
         if (isset($sdata['MANAGER_TOURNAMENT'])) 
           $this->manager_tournaments[$lang_id] = $sdata['MANAGER_TOURNAMENT'];
       } else {
         $sdata['MANAGER_TOURNAMENT'] = $this->manager_tournaments[$lang_id];
       }

     // apology and unsubscribe info
     $sdata['URL'] = $conf_site_url.'newsletter_subscribe.php?action=unsubscribe&newsletter_id='.$newsletter_id.'&user_id='.$user['USER_ID'].'&actkey='.$actkey;
     $sdata['USER_NAME'] = $user_name;
     return $sdata;
  }

  function generateWagerNewsletterContent($row, $user, $season_id, $lang_id) {
       global $db;       
       global $conf_site_url;

       $user_name = $row['USER_NAME'];
       $lang = $row['SHORT_CODE'];
       $newsletter_id = $row['ID'];
       $actkey = $row['UNSUBSCRIBE_KEY'];
       $last_release_date = $row['LAST_RELEASE_DATE'];
//echo $last_release_date;
     // header: your are recieving this newsletter because you participate in...
       unset($sdata);
       $sdata['TITLE'] = $row['TITLE'];
       $sdata['DESCR'] = $row['DESCR'];
       $sdata['HEADER'] = $row['HEADER'];
       $title = $row['TITLE'];
     // your team position, money spent, transactions left
       $sdata['PLACE'] = $user['PLACE'];
       $sdata['WEALTH'] = $user['WEALTH'];
     // new players on a market
     // players changed teams
     // players left tournament

       $sql="SELECT SUM(VALUE) GAMES, EVENT_TYPE FROM wager_log ML WHERE event_date > '".$last_release_date."'
			AND EVENT_TYPE IN (1)
			and season_id=".$season_id."
			GROUP BY EVENT_TYPE
			ORDER BY EVENT_TYPE";	
       $db->query($sql);
       $c = 0;
       while ($row = $db->nextRow()) {       
	    $sdata['WAGER_NEWS'][0]['WAGER_NEWS_'.$row['EVENT_TYPE']][0]['GAMES'] = $row['PLAYERS'];
            $c++;
       }
  //     if ($c == 0)
//         $sdata['WAGER_NEWS'][0]['NO_NEWS'][0]['X'] = 1;

     // related announcements
       $sql="SELECT N.NEWS_ID, N.DATE_PUBLISHED, ND.TITLE, ND.LANG_ID, '".$lang."' as SHORT_CODE
			FROM news N, news_details ND 
		 WHERE N.DATE_PUBLISHED > '".$last_release_date."'
			AND N.PUBLISH = 'Y'
			AND N.WSEASON_ID=".$season_id."
			AND N.news_id=ND.news_id 
			and ND.lang_id=".$lang_id."
			ORDER BY N.DATE_PUBLISHED DESC";	
       $db->query($sql);
       $c = 0;
       while ($row = $db->nextRow()) {       
	    $sdata['WAGER_NEWS'][0]['ITEMS'][$c] = $row;
            $c++;
       }
     
     // apology and unsubscribe info
     $sdata['URL'] = $conf_site_url.'newsletter_subscribe.php?action=unsubscribe&newsletter_id='.$newsletter_id.'&user_id='.$user['USER_ID'].'&actkey='.$actkey;
     $sdata['USER_NAME'] = $user_name;
     return $sdata;
  }

  function submitNewsletterToQueue() {
    global $db;
    $sql="SELECT * FROM newsletter N 
			WHERE (DATE_ADD(LAST_RELEASE_DATE, INTERVAL 1 week) < now() 
                               OR DATE_ADD(LAST_RELEASE_DATE, INTERVAL 1 week) is null)
			AND ID=".$this->id;
    $db->query($sql);
    $queues = '';
    $c = 0;
    while ($row = $db->nextRow()) {
      $queues[$c] = $row;
      $c++;
    }

    if (is_array($queues)) {
      foreach ($queues as $queue) {
        unset($sdata);
        $sdata['NEWSLETTER_ID'] = $queue['ID'];
        $sdata['TRIGGER_DATE'] = 'NOW()';
        $db->insert('newsletter_queue', $sdata);
      } 
    }
  }

  function prepareEmailQueue() {
    global $db;

    $sql="SELECT NS.USER_ID, NQ.QUEUE_ID 
		FROM newsletter_queue NQ, newsletter_subscribers NS, users U
		WHERE NQ.NEWSLETTER_ID=NS.ID 
			AND NS.ACTIVE=1 
			and U.EMAIL_VERIFIED='Y'
			and U.USER_ID=NS.USER_ID
			AND NQ.COMPLETED=0";
    $db->query($sql);
    $queues = '';
    $c = 0;
    while ($row = $db->nextRow()) {
        $queues[$c] = $row;
        $c++;
    }

    if (is_array($queues)) {
      foreach ($queues as $queue) {
        unset($sdata);
        $sdata['USER_ID'] = $queue['USER_ID'];
        $sdata['QUEUE_ID'] = $queue['QUEUE_ID'];
        $db->insert('newsletter_email_queue', $sdata);
      } 
    }

  }

  function generateEmailQueue() {
    global $db;
//$db->showquery=true;
    $sql = "SELECT * 
		FROM newsletter_email_queue NEQ, newsletter_queue NQ, newsletter N, users U
		WHERE NEQ.GENERATED=0 
			and NEQ.QUEUE_ID=NQ.QUEUE_ID 
			and NQ.NEWSLETTER_ID=N.ID
                        and NEQ.USER_ID=U.USER_ID
			and U.EMAIL_VERIFIED='Y'";

    $db->query($sql);
    $queues = '';
    $queue_ids = '';
    $c = 0;
    while ($row = $db->nextRow()) {
        $queues[$c] = $row;
	$queue_ids[$row['QUEUE_ID']] = $row;
        $c++;
    }

    if (is_array($queues)) {
      foreach ($queues as $queue) {
        if ($queue['TYPE'] == 0) {
	   $this->generateGeneralNewsletter($queue['QUEUE_ID'], $queue['USER_ID']);
	   echo $queue['USER_ID']."<br>";
        } else if ($queue['TYPE'] == 1) {
	   $this->generateManagerNewsletter($queue['QUEUE_ID'], $queue['SEASON_ID'], $queue['USER_ID']);
        } else if ($queue['TYPE'] == 2) {
	   $this->generateWagerNewsletter($queue['QUEUE_ID'], $queue['SEASON_ID'], $queue['USER_ID']);
        } 

      }
    }   

    if (is_array($queue_ids)) {
      foreach ($queue_ids as $queue_id) {
        unset($sdata);
        $sdata['COMPLETED'] = 1;
        $db->update('newsletter_queue', $sdata, 'QUEUE_ID='.$queue_id['QUEUE_ID']);
        unset($sdata);
        $sdata['LAST_RELEASE_DATE'] = 'NOW()';
        $db->update('newsletter', $sdata, "ID=".$queue_id['ID']);

      }
    }
 
  }

  function submitEmailQueue($quantity) {
     global $db;
     global $langs;

     $sql="SELECT U.EMAIL, NEQ.* FROM newsletter_email_queue NEQ, users U
		WHERE NEQ.USER_ID=U.USER_ID and NEQ.SENT=0 and NEQ.GENERATED=1 
			and U.EMAIL_VERIFIED='Y'
		LIMIT ".$quantity;
     $db->query($sql);
  
    $queues = '';
    $c = 0;
    while ($row = $db->nextRow()) {
        $queues[$c] = $row;
        $c++;
    }

//$db->showquery=true;
    if (is_array($queues)) {
       foreach ($queues as $queue) {
         $email = new Email($langs);
         $email->setRandomHash();
         $to = $queue['EMAIL'];
         $subject = $queue['SUBJECT'];
         $sdata['HTML'] = $queue['HTML'];
         $sdata['PLAIN'] = $queue['PLAIN'];
         $sdata['RANDOM_HASH'] = $email->random_hash;
         $email->getEmailFromTemplate ('email_newsletter', $sdata);
         $email->removeEmptyLines();
         if ($email->sendHTML($to, $subject, $queue['HTML'], $queue['PLAIN'])) {
           unset($sdata);
           $sdata['SENT'] = 1;
           $sdata['SENT_DATE'] = 'NOW()';
           $db->update('newsletter_email_queue', $sdata, "USER_ID=".$queue['USER_ID']." AND QUEUE_ID=".$queue['QUEUE_ID']);
         }
       }
    }

  }

  function getHtmlNewsletter($queue_id) {
    global $db;
    global $auth;

    $db->select('newsletter_email_queue', 'HTML', 'USER_ID='.$auth->getUserId().' AND QUEUE_ID='.$queue_id);
  
    if ($row = $db->nextRow()) { 
      return $row['HTML'];
    }
    return '';

  }

}
?>