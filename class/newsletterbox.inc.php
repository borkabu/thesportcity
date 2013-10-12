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

class NewsletterBox extends Box{

  function NewsletterBox($langs, $lang) {
    parent::Box($langs, $lang);
  }
 
  function getUserNewsletters() {
    global $auth;
    global $_SESSION;
    global $db;
    global $tpl;

    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/bar_newsletters.tpl.html');
    $this->data['NEWSLETTERS'][0] = $this->getUserNewslettersData();
    $this->rows = $this->data['NEWSLETTERS'][0]['_ROWS'];	
    $tpl->addData($this->data);
    return $tpl->parse();

  }

  function getUserNewslettersData() {
    global $auth;
    global $_SESSION;
    global $db;

       $sql="SELECT N.ID, N.NAME, ND.TITLE, ND.DESCR, NS.USER_ID, NS.ACTIVE FROM newsletter N, newsletter_details ND 
		 left join newsletter_subscribers NS ON NS.USER_ID=".$auth->getUserId()."
				and NS.ID=ND.ID 
		 WHERE N.ID=ND.ID AND ND.LANG_ID=".$_SESSION['lang_id']."
			AND N.PUBLISH='Y'
			AND N.END_DATE > NOW()                         
		 ORDER BY N.NAME ASC";
    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
      $data['ITEMS'][$c] = $row; 
      if ($row['ACTIVE'] == 1) 
	$data['ITEMS'][$c]['UNSUBSCRIBE'][0]['ID'] = $row['ID'];
      else $data['ITEMS'][$c]['SUBSCRIBE'][0]['ID'] = $row['ID'];
      $c++;
    }
    $count= $c;
    $db->free();
   
  //  echo $count;
    $data['_ROWS'] = $count;
      // no records?
    if ($c == 0) {
      $data['NORECORDS'][0]['X'] = 1;
    }
    
    $db->free();
    return $data;
  }

  function getHtmlNewsletters() {
    global $auth;
    global $_SESSION;
    global $db;
    global $tpl;

    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/bar_newsletters_list.tpl.html');
    $this->data['NEWSLETTERS'][0] = $this->getHtmlNewslettersData();
    $this->rows = $this->data['NEWSLETTERS'][0]['_ROWS'];	
    $tpl->addData($this->data);
    return $tpl->parse();

  }

  function getHtmlNewslettersData() {
    global $auth;
    global $_SESSION;
    global $db;

       $sql="SELECT NEQ.QUEUE_ID, NEQ.USER_ID, NEQ.SUBJECT, NEQ.HTML, NEQ.SENT_DATe
                  FROM newsletter_email_queue NEQ
		 WHERE NEQ.USER_ID=".$auth->getUserId()."
			AND NEQ.SENT=1
		 ORDER BY NEQ.SENT_DATE DESC";
    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
      $data['ITEMS'][$c] = $row; 
      $c++;
    }
    $count= $c;
    $db->free();
   
  //  echo $count;
    $data['_ROWS'] = $count;
      // no records?
    if ($c == 0) {
      $data['NORECORDS'][0]['X'] = 1;
    }
    
    $db->free();
    return $data;
  }

}
?>