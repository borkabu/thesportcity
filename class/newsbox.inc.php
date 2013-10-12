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

class NewsBox extends Box{

  function NewsBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getNewsBox ($cat='',$page=1,$perpage=PAGE_SIZE) {
    global $tpl;
    global $db;
    
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/bar_news.tpl.html');
    $this->data['NEWS'][0] = $this->getNewsData($cat, $page, $perpage, 1, true);
    $this->data['MORE'][0]['GENRE'] = 1;
    $this->rows = $this->data['NEWS'][0]['_ROWS'];	
//print_r($this->data['NEWS']);
    $tpl->addData($this->data);
    return $tpl->parse();
  } 

  function getNews ($cat='',$page=1,$perpage=PAGE_SIZE, $genre=1) {
    global $tpl;
    global $db;
    global $_SESSION;
    global $smarty;
    
    // content
    $news = $this->getNewsData($cat, $page, $perpage, $genre, false);
//    $this->rows = $news['_ROWS'];	

    $template_file = '';
    if ($genre == 1)
      $template_file = "smarty_tpl/bar_news.smarty";
    else if ($genre == 2)
      $template_file = "smarty_tpl/bar_blogs.smarty";

//print_r($this->data['NEWS']);
    $start = getmicrotime();
    $output = $smarty->fetch($template_file);    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo $template_file.($stop-$start);
    return $output;
  } 

  function getNewsHeaders ($cat='',$page=1,$perpage=PAGE_SIZE, $genre=1) {
    global $db;
    global $_SESSION;
    global $smarty;
    
    $smarty->clearAllAssign();
    // content
    $template_file = '';
    if ($genre == 1)
      $template_file = "smarty_tpl/bar_news_headers.smarty";
    else if ($genre == 2)
      $template_file = "smarty_tpl/bar_blogs_headers.smarty";
    $more['GENRE'] = $genre;
    $smarty->assign("more", $more);
    $news = $this->getNewsData($cat, $page, $perpage, $genre, false);
    $start = getmicrotime();
    $output = $smarty->fetch($template_file);    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo $template_file.($stop-$start)."<br>";
    return $output;

  } 

  function getNewsItem ($news_id, $genre='') {
    global $tpl;
    global $db;
    global $_SESSION;
    
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    if ($genre == 2)
      $tpl->setTemplateFile('tpl/blog_item.tpl.html');
    else
      $tpl->setTemplateFile('tpl/news_item.tpl.html');
    $data = $this->getNewsItemData($news_id);
    if ($data != '') {
      $this->data['NEWS'][0] = $data;
//print_r($this->data['NEWS']);
      $tpl->addData($this->data);
      return $tpl->parse();
    }
    else {
      return '';
    }
   
  } 


  function getNewsTopicId() {
    global $db;
    global $_SESSION;

    return $this->data['NEWS'][0]['TOPIC_ID'];
  }

  function getNewsItemData($news_id) {
    global $db;
    global $_SESSION;
    global $auth;
    global $reality_types;
    global $langs;
    global $html_page;

    $data='';

    $forumpermissions = new ForumPermission();
    if (is_numeric($news_id)) {

      $sdata['VIEWED']='VIEWED + 1';
      $db->update('news', $sdata, "NEWS_ID=".$news_id);
      if ($auth->userOn()) {
        $sql = "SELECT N.NEWS_ID, N.REALITY, ND.TITLE, ND.DESCR, 
                SUBSTRING(N.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED,
		ND.SOURCE, ND.SOURCE_NAME, U.USER_ID, U.USER_NAME, ND.TOPIC_ID,
                N.VOTED, N.CCTL, N.VOTES, PV.USER_ID as VOTER_ID, PV.VOTE, N.GENRE
              FROM 
                news_details ND, news N LEFT JOIN users U ON N.USER_ID=U.USER_ID
		     	left join news_votes PV ON PV.NEWS_ID=N.NEWS_ID AND PV.USER_ID=".$auth->getUserId()."
              WHERE
                N.NEWS_ID='".$news_id."' 
                AND N.PUBLISH='Y' AND N.DATE_PUBLISHED < NOW()
		AND N.NEWS_ID=ND.NEWS_ID AND ND.LANG_ID=".$_SESSION['lang_id'];
      } else {
        $sql = "SELECT N.NEWS_ID, N.REALITY, ND.TITLE, ND.DESCR, 
                SUBSTRING(N.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED,
		ND.SOURCE, ND.SOURCE_NAME, U.USER_ID, U.USER_NAME, ND.TOPIC_ID,
                N.VOTED, N.CCTL, N.VOTES, N.GENRE
              FROM 
                news_details ND, news N LEFT JOIN users U ON N.USER_ID=U.USER_ID
              WHERE
                N.NEWS_ID='".$news_id."' 
                AND N.PUBLISH='Y' AND N.DATE_PUBLISHED < NOW()
		AND N.NEWS_ID=ND.NEWS_ID AND ND.LANG_ID=".$_SESSION['lang_id'];
       }
      $db->query($sql);
      $row = $db->nextRow();
      $data = $row;

      $html_page->setPageTitle($row['TITLE']);
      $html_page->setPageDescr(str_replace("\"", "'", strip_tags($row['DESCR'])));
      $data['LANG'] = $_SESSION['_lang'];
      if (!empty($row['REALITY']))
        $data['REALITY'] = $langs[$reality_types[$row['REALITY']]];
      // encode news title to be passed through http query
       
      if (!empty($row['SOURCE_NAME'])) {
        if (substr($row['SOURCE'], 0, 4) != 'http')
          $data['SRC'][0]['SOURCE'] = "http://".$row['SOURCE'];
        else $data['SRC'][0]['SOURCE'] = $row['SOURCE'];
        $data['SRC'][0]['SOURCE_NAME'] = $row['SOURCE_NAME'];
      }

//echo $forumpermissions->canContentBeVoted($row['CCTL']);
      if ($row['USER_ID'] == $auth->getUserId() && $row['VOTED'] == 'N') {
        $data['EDIT'][0]['NEWS_ID'] = $row['NEWS_ID'];
        $data['DELETE'][0]['NEWS_ID'] = $row['NEWS_ID'];
      }      
      if ($auth->userOn() && $row['GENRE'] == 2)
	$can_vote = true;
      else $can_vote = $forumpermissions->canVoteContent();       
      if ($can_vote && $row['VOTER_ID'] != $auth->getUserId()
          && $row['USER_ID'] != $auth->getUserId()
	  && $row['VOTED'] == 'N'
          && ($forumpermissions->canContentBeVoted($row['CCTL']) || $row['GENRE'] == 2)) {
	        $data['VOTING'][0]['NEWS_ID'] = $row['NEWS_ID'];
      }
      else if ($row['VOTED'] == 'Y') {
	if ($row['VOTES'] > 0) 
	          $data['VOTED_PLUS'][0]['NEWS_ID'] = $row['NEWS_ID'];
        else
	          $data['VOTED_MINUS'][0]['NEWS_ID'] = $row['NEWS_ID'];
      }
      else if (isset($row['VOTE']) && $row['VOTE'] >= 0 && $row['VOTE'] != '') {
	        $data['THUMB_UP'][0]['NEWS_ID'] = $row['NEWS_ID'];
      } 
      else if (isset($row['VOTE']) && $row['VOTE'] < 0 && $row['VOTE'] != '') {
	        $data['THUMB_DOWN'][0]['NEWS_ID'] = $row['NEWS_ID'];
      }

    }      

    return $data;
  }

  function getNewsData ($cat_id='',$page=1,$perpage=PAGE_SIZE,$genre=3, $box=FALSE, $rss=false){
    global $db;
    global $smarty;
    global $_SESSION;

    $where = "N.PUBLISH='Y' AND N.DATE_PUBLISHED <= NOW()";
  
    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;
  
    $filter = array();
    // limit by category
    if ($cat_id > 0 && is_numeric($cat_id)) {
      $where .= " AND (N.CAT_ID=$cat_id)";
      $filter['FILTERED']['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('genre'));
    }
  
    if ($genre>0 && is_numeric($genre)) {
       $where .= " AND N.GENRE='$genre'";
    }

    if ($genre == 1) {
      $filter['CAT_ID'] = inputCats('cat_id', $cat_id, 80, true);
      $filter['GENRE'] = $genre;
      $filter['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('genre'));
    }
    
    $sql_count = "SELECT COUNT(N.NEWS_ID) ROWS
                   FROM news N, news_details ND 
                   WHERE ND.NEWS_ID=N.NEWS_ID AND ND.LANG_ID=".$_SESSION['lang_id']." AND ".$where; 
    $db->query($sql_count);
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }
  
    if (!$box)  {
      $sql = "SELECT 
              N.NEWS_ID, ND.TITLE, ND.DESCR, N.PRIORITY, N.CAT_ID,
              ND.SOURCE, ND.SOURCE_NAME, SUBSTRING(N.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED, N.GENRE, T.POSTS, 
		N.SEASON_ID, N.WSEASON_ID, 
		MSD.SEASON_TITLE, WSD.TSEASON_TITLE, U.USER_NAME, CD.CAT_NAME
            FROM 
              news N
		left join manager_seasons_details MSD ON MSD.SEASON_ID=N.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']." 
		left join wager_seasons_details WSD ON WSD.SEASON_ID=N.WSEASON_ID AND WSD.LANG_ID=".$_SESSION['lang_id']." 
		LEFT JOIN users U ON N.USER_ID=U.USER_ID
		LEFT JOIN cats_details CD ON N.CAT_ID=CD.CAT_ID AND CD.LANG_ID=".$_SESSION['lang_id']."
		, news_details ND 
		     left join topic T ON T.TOPIC_ID=ND.TOPIC_ID AND T.LANG_ID=".$_SESSION['lang_id']."
            WHERE ND.NEWS_ID=N.NEWS_ID AND ND.LANG_ID=".$_SESSION['lang_id']." AND
              ".$where."
            ORDER BY 
              N.PRIORITY DESC, N.DATE_PUBLISHED DESC, N.NEWS_ID DESC
            ".$limitclause;
    }
    else {
      $sql = "SELECT 
              N.NEWS_ID, ND.TITLE, ND.DESCR, N.CAT_ID, SUBSTRING(N.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED, T.POSTS, 
		N.SEASON_ID, N.WSEASON_ID, MSD.SEASON_TITLE, WSD.TSEASON_TITLE, CD.CAT_NAME
            FROM 
              news N 
		left join manager_seasons_details MSD ON MSD.SEASON_ID=N.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']." 
		left join wager_seasons_details WSD ON WSD.SEASON_ID=N.WSEASON_ID AND WSD.LANG_ID=".$_SESSION['lang_id']." 
		LEFT JOIN cats_details CD ON N.CAT_ID=CD.CAT_ID AND CD.LANG_ID=".$_SESSION['lang_id']."
		, news_details ND 
		     left join topic T ON T.TOPIC_ID=ND.TOPIC_ID AND T.LANG_ID=".$_SESSION['lang_id']."
            WHERE ND.NEWS_ID=N.NEWS_ID AND ND.LANG_ID=".$_SESSION['lang_id']." AND
              ".$where."
            ORDER BY 
              N.PRIORITY DESC, N.DATE_PUBLISHED DESC, N.NEWS_ID DESC
            ".$limitclause;
    }
//echo $sql;  
//  $db->showquery = true;
    $db->query($sql);

    $c = 0;
    $news = array();
    while ($row = $db->nextRow()) {
      $news_item = $row; 
      if (!empty($row['CAT_NAME']))
        $news_item['CAT_NAME'] = $row['CAT_NAME'];
      $news_item['LANG'] = $_SESSION['_lang']; 
      if (empty($row['POSTS'])) {
        $news_item['POSTS'] = 0; 
	$news_item['WEIGHT'] = "normal";
      } else 	
	$news_item['WEIGHT'] = "bold";
      if (!empty($row['SEASON_ID']))
        $news_item['MANAGER_SEASON']['SEASON_TITLE'] = $row['SEASON_TITLE'];
      if (!empty($row['WSEASON_ID']))
        $news_item['WAGER_SEASON']['SEASON_TITLE'] = $row['TSEASON_TITLE'];

      $c++;
      $news[] = $news_item; 
    }
    $db->free();
   
    $this->rows = $count;
    $smarty->assign("filter", $filter);
    $smarty->assign("news", $news);   
    return $news;
  }


  function getNewsShortData ($cat_id='',$page=1,$perpage=PAGE_SIZE,$genre=3, $last_date =''){
    global $db;
    global $smarty;
    global $_SESSION;

    $where = "N.PUBLISH='Y' AND N.DATE_PUBLISHED <= NOW()";
  
    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;
  
    $filter = array();
    // limit by category
    if ($cat_id > 0 && is_numeric($cat_id)) {
      $where .= " AND (N.CAT_ID=$cat_id)";
      $filter['FILTERED']['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('genre'));
    }
  
    if ($genre>0 && is_numeric($genre)) {
       $where .= " AND N.GENRE='$genre'";
    }

    if (!empty($last_date)) {
      $where .= " AND N.date_published >'".$last_date."'";
    }
    
    if ($genre == 1) {
      $filter['CAT_ID'] = inputCats('cat_id', $cat_id, 80, true);
      $filter['GENRE'] = $genre;
      $filter['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('genre'));
    }
    
    $sql_count = "SELECT COUNT(N.NEWS_ID) ROWS
                   FROM news N, news_details ND 
                   WHERE ND.NEWS_ID=N.NEWS_ID AND ND.LANG_ID=".$_SESSION['lang_id']." AND ".$where; 
    $db->query($sql_count);
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }
  
    $sql = "SELECT 
              N.NEWS_ID, ND.TITLE, ND.DESCR, N.PRIORITY, N.CAT_ID,
              ND.SOURCE, ND.SOURCE_NAME, SUBSTRING(N.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED, N.GENRE, T.POSTS, 
		N.SEASON_ID, N.WSEASON_ID, 
		MSD.SEASON_TITLE, WSD.TSEASON_TITLE, U.USER_NAME, CD.CAT_NAME
            FROM 
              news N
		left join manager_seasons_details MSD ON MSD.SEASON_ID=N.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']." 
		left join wager_seasons_details WSD ON WSD.SEASON_ID=N.WSEASON_ID AND WSD.LANG_ID=".$_SESSION['lang_id']." 
		LEFT JOIN users U ON N.USER_ID=U.USER_ID
		LEFT JOIN cats_details CD ON N.CAT_ID=CD.CAT_ID AND CD.LANG_ID=".$_SESSION['lang_id']."
		, news_details ND 
		     left join topic T ON T.TOPIC_ID=ND.TOPIC_ID AND T.LANG_ID=".$_SESSION['lang_id']."
            WHERE ND.NEWS_ID=N.NEWS_ID AND ND.LANG_ID=".$_SESSION['lang_id']." AND
              ".$where."
            ORDER BY 
              N.PRIORITY DESC, N.DATE_PUBLISHED DESC, N.NEWS_ID DESC
            ".$limitclause;
//echo $sql;  
//  $db->showquery = true;
    $db->query($sql);

    $news = array();
    while ($row = $db->nextRow()) {
      $news_item = $row; 
      if (!empty($row['CAT_NAME']))
        $news_item['CAT_NAME'] = $row['CAT_NAME'];
      $news_item['LANG'] = $_SESSION['_lang']; 
      if (empty($row['POSTS'])) {
        $news_item['POSTS'] = 0; 
	$news_item['WEIGHT'] = "normal";
      } else 	
	$news_item['WEIGHT'] = "bold";
      if (!empty($row['SEASON_ID']))
        $news_item['MANAGER_SEASON']['SEASON_TITLE'] = $row['SEASON_TITLE'];
      if (!empty($row['WSEASON_ID']))
        $news_item['WAGER_SEASON']['SEASON_TITLE'] = $row['TSEASON_TITLE'];

      if (strlen($news_item['DESCR']) > 600) {
        $descr1 = mb_substr($news_item['DESCR'], 0, 400);
        $descr2 = mb_substr($news_item['DESCR'], 400, 200);
        $news_item['DESCR1'] = strip_tags($descr1, '<p><a><img><b><br><i>');
        $news_item['DESCR2'] = strip_tags($descr2, '<p><br>');
        $news_item['DESCR2'] = mb_substr($news_item['DESCR2'], 0, mb_strrpos($news_item['DESCR2'], '.') + 1);
        $news_item['DESCR'] = $news_item['DESCR1'].$news_item['DESCR2'];
      }
      $news[] = $news_item; 
    }
    $db->free();
   
    $this->rows = $count;
    $smarty->assign("filter", $filter);
    $smarty->assign("news", $news);   
    return $news;
  }

  function addNewsItem($news_id = '', $news_type='') {
    global $db;
    global $_POST;
    global $langs;
    global $auth;
    global $_SESSION;
    global $conf_site_url;
    global $reality_types;

    $trust = new Trust();
    $error=FALSE;
//    if ($_POST['descr'] == "" && isset($_POST['simple_text']))
    $_POST['descr'] .= $_POST['simple_text'];

    if ($news_type == 2) {
      $r_fields=array('title', 'descr');
      $s_fields_d=array('title', 'descr');
    } else {
      $r_fields=array('title', 'source', 'source_name', 'descr');
      $s_fields_d=array('title', 'source', 'source_name', 'descr');
    }
    $i_fields_d=array('lang_id');
    $d_fields_d='';
    $c_fields_d='';
//$db->showquery=true;
//print_r($_POST);
    if(!requiredFieldsOk($r_fields, $_POST)){
	$error=TRUE;
	$data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];        
    };

    if(!$error){
	$sdata['GENRE'] = $news_type;
	$sdata['DATE_CREATED'] = "NOW()";
	$sdata['USER_ID'] = $auth->getUserId();
	$sdata['PRIORITY'] = 1;
        if ($news_type != 2) {
  	  $sdata['REALITY'] = $_POST['reality'];
          if (!empty($_POST['cat_id']))
  	    $sdata['CAT_ID'] = $_POST['cat_id'];
        }

	$tdata=buildSaveData($s_fields_d,$i_fields_d,$d_fields_d,$c_fields_d,$_POST);
//print_r($tdata);
	// proceed to database updates
	if(!empty($news_id) && !empty($_POST["lang_id"])){
		// UPDATE
		$db->update('news', $sdata, "NEWS_ID=".$news_id);
		$tdata['news_id'] = $news_id;
                $db->select('news_details', 'NEWS_ID', 'NEWS_ID='.$news_id);
                if ($row=$db->nextRow())
  		  $db->update('news_details', $tdata, 'NEWS_ID='.$news_id);
                else  
  		  $db->insert('news_details', $tdata);
	}else{
		// INSERT
		$sdata['DATE_CREATED']='SYSDATE()';
		$sdata['USER_ID']=$auth->getUserId();
	        $actkey = gen_rand_string(0, 10);
                $cctl = $trust->getContentTrustLevel($_SESSION['_user']['CONTENT_TRUST']);
		$moderated = false; 
                if (($news_type == 1 && ($cctl == 0 || $cctl == 1)) ||
		    ($news_type == 2 && ($cctl < 3))) {
		  $sdata['ACTKEY'] = "'".$actkey."'";
                  $sdata['PUBLISH'] = "'N'";
                  $sdata['REVIEWED'] = "'N'";
		  $moderated = true; 
                  // send email to admin
                } else {
                  $sdata['REVIEWED'] = "'N'";
                  $sdata['PUBLISH'] = "'Y'";
	  	  $sdata['DATE_PUBLISHED'] = "NOW()";
		}
		if ($news_type == 2 && $cctl < 2)
                  $cctl = 2;
	        $sdata['CCTL'] = $cctl;

		$db->insert('news', $sdata);

		$news_id = $db->id();
		$tdata['news_id']  = $news_id;
		$db->insert('news_details',$tdata);
                if ($moderated) {
                  $edata['USER_NAME'] = $_SESSION['_user']['USER_NAME'];
		  $edata['TITLE'] = $_POST['title'];
		  $edata['SOURCE'] = $_POST['source'];
		  $edata['REALITY'] = $langs[$reality_types[$_POST['reality']]];
                  $edata['TEXT'] = $_POST['descr'];
		  $edata['URL_APPROVE'] = $conf_site_url."user_activation.php?mode=news_approve&news_id=".$news_id."&actkey=".$actkey;
		  $edata['URL_IGNORE'] = $conf_site_url."user_activation.php?mode=news_ignore&news_id=".$news_id."&actkey=".$actkey;
		  $edata['URL_DISAPPROVE'] = $conf_site_url."user_activation.php?mode=news_disapprove&news_id=".$news_id."&actkey=".$actkey;

		  $email = new Email($langs, $_SESSION['_lang']);
 		  $email->getEmailFromTemplate ('email_news_approve', $edata) ;
                  if ($news_type == 1)
  	            $subject = $langs['LANG_EMAIL_NEWS_APPROVE_LINE_1'];
                  else if ($news_type == 2)
  	            $subject = $langs['LANG_EMAIL_BLOG_APPROVE_LINE_1'];		
		  $email->sendAdmin($subject);
                } else {
                  if ($news_type == 1) {
                    $credits = new Credits();
	    	    $credits->updateCredits ($auth->getUserId(), 0.1);
  		    $credit_log = new CreditsLog();
	  	    $credit_log->logEvent ($auth->getUserId(), 8, 0.1);
                  }
		}
	};
	// redirect to news page
    }
    return $news_id;
  }

  function getExternalNewsBox () {
    global $smarty;
    
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_external_news.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_external_news.smarty'.($stop-$start);
    return $output;
  } 

}   
?>