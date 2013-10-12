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

class VideoBox extends Box{

  function VideoBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getVideoNewsBox ($cat='',$page=1,$perpage=PAGE_SIZE) {
    global $tpl;
    global $db;
    
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/bar_videos.tpl.html');
    $this->data['VIDEO'][0] = $this->getVideoNewsData($cat, $page, $perpage, 1, true);
    $this->data['MORE'][0]['GENRE'] = 1;
    $this->rows = $this->data['VIDEO'][0]['_ROWS'];	
//print_r($this->data['VIDEO']);
    $tpl->addData($this->data);
    return $tpl->parse();
  } 

  function getVideoNews ($cat='',$page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    global $_SESSION;
   
    // content
    $video = $this->getVideoNewsData($cat, $page, $perpage, 1, false);

    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_video.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_video_headers.smarty'.($stop-$start)."<br>";
    return $output;
  } 

  function getVideoNewsHeaders ($cat='',$page=1,$perpage=PAGE_SIZE) {
    global $smarty;   
    global $db;
    global $_SESSION;
    
    // content
    $smarty->clearAllAssign();

    $video = $this->getVideoNewsData($cat, $page, $perpage, 1, false);
//    $this->rows = $this->data['VIDEO'][0]['_ROWS'];	
    $smarty->assign("more", 1);   
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_video_headers.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_video_headers.smarty'.($stop-$start)."<br>";
    return $output;

  } 

  function getVideoNewsItem ($video_id) {
    global $tpl;
    global $db;
    global $_SESSION;
    
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/video_item.tpl.html');
    $data = $this->getVideoNewsItemData($video_id);
    if ($data != '') {
      $this->data['VIDEO'][0] = $data;
//print_r($this->data['VIDEO']);
      $tpl->addData($this->data);
      return $tpl->parse();
    }
    else {
      return '';
    }
   
  } 


  function getVideoNewsTopicId() {
    global $db;
    global $_SESSION;

    return $this->data['VIDEO'][0]['TOPIC_ID'];
  }

  function getVideoNewsItemData($video_id) {
    global $db;
    global $_SESSION;
    global $auth;
    global $langs;
    global $html_page;

    $data='';

    $forumpermissions = new ForumPermission();
    $can_vote = $forumpermissions->canVoteContent();
    if (is_numeric($video_id)) {

      $sdata['VIEWED']='VIEWED + 1';
      $db->update('video', $sdata, "VIDEO_ID=".$video_id);
      if ($auth->userOn()) {
        $sql = "SELECT N.VIDEO_ID, ND.TITLE, ND.DESCR, N.LINK, N.THUMBNAIL, 
                SUBSTRING(N.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED,
		N.SOURCE, N.SOURCE_NAME, U.USER_ID, U.USER_NAME, ND.TOPIC_ID,
                N.VOTED, N.CCTL, N.VOTES, PV.USER_ID as VOTER_ID, PV.VOTE
              FROM 
		video_details ND, video N LEFT JOIN users U ON N.USER_ID=U.USER_ID
		     	left join video_votes PV ON PV.VIDEO_ID=N.VIDEO_ID AND PV.USER_ID=".$auth->getUserId()."
              WHERE
                N.VIDEO_ID='".$video_id."' 
                AND N.PUBLISH='Y' AND N.DATE_PUBLISHED < NOW()
		AND N.VIDEO_ID=ND.VIDEO_ID AND ND.LANG_ID=".$_SESSION['lang_id'];
      } else {
        $sql = "SELECT N.VIDEO_ID, ND.TITLE, ND.DESCR, N.LINK, N.THUMBNAIL,
                SUBSTRING(N.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED,
		N.SOURCE, N.SOURCE_NAME, U.USER_ID, U.USER_NAME, ND.TOPIC_ID,
                N.VOTED, N.CCTL, N.VOTES
              FROM 
                video_details ND, video N LEFT JOIN users U ON N.USER_ID=U.USER_ID
              WHERE
                N.VIDEO_ID='".$video_id."' 
                AND N.PUBLISH='Y' AND N.DATE_PUBLISHED < NOW()
		AND N.VIDEO_ID=ND.VIDEO_ID AND ND.LANG_ID=".$_SESSION['lang_id'];
       }
//    echo $sql;
      $db->query($sql);
      $row = $db->nextRow();
      $data = $row;
      $data['LINK'] = stripslashes($row['LINK']);
      $data['LINK'] = str_replace("width=\"640\"", 'width="520"', $data['LINK']);

      $html_page->setPageTitle($row['TITLE']);
      $html_page->setPageDescr(str_replace("\"", "'", $row['DESCR']));
      $data['LANG'] = $_SESSION['_lang'];
       
      if (!empty($row['SOURCE_NAME'])) {
        $data['SRC'][0]['SOURCE'] = $row['SOURCE'];
        $data['SRC'][0]['SOURCE_NAME'] = $row['SOURCE_NAME'];
      }

//echo $forumpermissions->canContentBeVoted($row['CCTL']);
      if ($row['USER_ID'] == $auth->getUserId() && $row['VOTED'] == 'N') {
        $data['EDIT'][0]['VIDEO_ID'] = $row['VIDEO_ID'];
        $data['DELETE'][0]['VIDEO_ID'] = $row['VIDEO_ID'];
      }

      if ($can_vote && $row['VOTER_ID'] != $auth->getUserId()
          && $row['USER_ID'] != $auth->getUserId()
	  && $row['VOTED'] == 'N'
          && $forumpermissions->canContentBeVoted($row['CCTL'])) {
	        $data['VOTING'][0]['VIDEO_ID'] = $row['VIDEO_ID'];
      }
      else if ($row['VOTED'] == 'Y') {
	if ($row['VOTES'] > 0) 
	          $data['VOTED_PLUS'][0]['VIDEO_ID'] = $row['VIDEO_ID'];
        else
	          $data['VOTED_MINUS'][0]['VIDEO_ID'] = $row['VIDEO_ID'];
      }
      else if (isset($row['VOTE']) && $row['VOTE'] > 0) {
	        $data['THUMB_UP'][0]['VIDEO_ID'] = $row['VIDEO_ID'];
      } 
      else if (isset($row['VOTE']) && $row['VOTE'] < 0) {
	        $data['THUMB_DOWN'][0]['VIDEO_ID'] = $row['VIDEO_ID'];
      }

    }      

    return $data;
  }

  function getVideoNewsData ($cat='',$page=1,$perpage=PAGE_SIZE, $box=FALSE, $rss=false){
    global $db;
    global $_SESSION;
    global $smarty;

    $where = "N.PUBLISH='Y' AND N.DATE_PUBLISHED <= NOW()";
  
    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;
  
    // limit by category
    if ($cat > 0 && is_numeric($cat)) {
      $where .= " AND (N.CAT_ID=$cat)";
    }
  
    $sql_count = "SELECT COUNT(N.VIDEO_ID) ROWS
                   FROM video N, video_details ND 
                   WHERE ND.VIDEO_ID=N.VIDEO_ID AND ND.LANG_ID=".$_SESSION['lang_id']." AND ".$where; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }
  
    if (!$box)  {
      $sql = "SELECT 
              N.VIDEO_ID, ND.TITLE, ND.DESCR, N.CAT_ID, N.THUMBNAIL,
              ND.SOURCE, ND.SOURCE_NAME, SUBSTRING(N.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED, T.POSTS
            FROM 
              news N, news_details ND 
		     left join topic T ON T.TOPIC_ID=ND.TOPIC_ID AND T.LANG_ID=".$_SESSION['lang_id']."
            WHERE ND.VIDEO_ID=N.VIDEO_ID AND ND.LANG_ID=".$_SESSION['lang_id']." AND
              ".$where."
            ORDER BY 
              N.DATE_PUBLISHED DESC, N.VIDEO_ID DESC
            ".$limitclause;
    }
    else {
      $sql = "SELECT 
              N.VIDEO_ID, ND.TITLE, ND.DESCR, N.CAT_ID, SUBSTRING(N.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED, T.POSTS, N.THUMBNAIL
            FROM 
              video N, video_details ND 
		     left join topic T ON T.TOPIC_ID=ND.TOPIC_ID AND T.LANG_ID=".$_SESSION['lang_id']."
            WHERE ND.VIDEO_ID=N.VIDEO_ID AND ND.LANG_ID=".$_SESSION['lang_id']." AND
              ".$where."
            ORDER BY 
              N.DATE_PUBLISHED DESC, N.VIDEO_ID DESC
            ".$limitclause;
    }
  
//  $db->showquery = true;
    $db->query($sql);
    $videos = array();
    $c = 0;
    while ($row = $db->nextRow()) {
      $video_item = $row; 
      $video_item['LANG'] = $_SESSION['_lang']; 
      if ($row['THUMBNAIL'] == "")
	$video_item['GENERIC_THUMBNAIL'][0]['X'] = 1;
      if (empty($row['POSTS'])) {
        $video_item['POSTS'] = 0; 
	$video_item['WEIGHT'] = "normal";
      } else 	
	$video_item['WEIGHT'] = "bold";
      $c++;
      $videos[] = $video_item; 
    }
    $db->free();
   
  //  echo $count;
    $this->rows = $count;
    $smarty->assign("videos", $videos);   
    return $videos;
  }

  function addVideoNewsItem($video_id = '') {
    global $db;
    global $_POST;
    global $langs;
    global $auth;
    global $_SESSION;
    global $conf_site_url;

    $trust = new Trust();
    $error=FALSE;
    $r_fields=array('title', 'source', 'source_name', 'link');

    $s_fields=array('source', 'source_name', 'link', 'thumbnail');
    $i_fields='';
    $d_fields='';
    $c_fields='';

    $s_fields_d=array('title', 'descr');
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
        $_POST['link'] = str_replace("ob<x>ject", 'object', $_POST['link']);
        $_POST['link'] = str_replace("allowsc<x>riptaccess", 'allowscriptaccess', $_POST['link']);
        $_POST['link'] = str_replace("em<x>bed", 'embed', $_POST['link']);
        $_POST['link'] = str_replace("allowsc<x>riptAccess", "allowscriptAccess", $_POST['link']);
        $sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$_POST);
	$sdata['USER_ID'] = $auth->getUserId();
	$sdata['DATE_CREATED'] = "NOW()";
	$tdata=buildSaveData($s_fields_d,$i_fields_d,$d_fields_d,$c_fields_d,$_POST);
//print_r($tdata);
	// proceed to database updates
	if(!empty($video_id) && !empty($_POST["lang_id"])){
		// UPDATE
		$db->update('video', $sdata, "VIDEO_ID=".$video_id);
		$tdata['video_id'] = $video_id;
                $db->select('video_details', 'VIDEO_ID', 'VIDEO_ID='.$video_id);
                if ($row=$db->nextRow())
  		  $db->update('video_details', $tdata, 'VIDEO_ID='.$video_id);
                else  
  		  $db->insert('video_details', $tdata);
	}else{
		// INSERT
		$sdata['USER_ID']=$auth->getUserId();
	        $actkey = gen_rand_string(0, 10);
                $cctl = $trust->getContentTrustLevel($_SESSION['_user']['CONTENT_TRUST']);
                if ($cctl == 0 || $cctl == 1) {
		  $sdata['ACTKEY'] = "'".$actkey."'";
                  $sdata['PUBLISH'] = "'N'";
                  $sdata['REVIEWED'] = "'N'";
                  // send email to admin
                } else {
                  $sdata['REVIEWED'] = "'N'";
                  $sdata['PUBLISH'] = "'Y'";
	  	  $sdata['DATE_PUBLISHED'] = "NOW()";
		}
	        $sdata['CCTL'] = $cctl;

		$db->insert('video', $sdata);

		$video_id = $db->id();
		$tdata['video_id']  = $video_id;
		$db->insert('video_details',$tdata);

                if ($trust->getContentTrustLevel($_SESSION['_user']['CONTENT_TRUST']) == 0 ||
		    $trust->getContentTrustLevel($_SESSION['_user']['CONTENT_TRUST']) == 1) {
                  $edata['USER_NAME'] = $_SESSION['_user']['USER_NAME'];
		  $edata['TITLE'] = $_POST['title'];
		  $edata['SOURCE'] = $_POST['source'];
                  $edata['TEXT'] = $_POST['descr'];
		  $edata['URL_APPROVE'] = $conf_site_url."user_activation.php?mode=video_approve&video_id=".$video_id."&actkey=".$actkey;
		  $edata['URL_IGNORE'] = $conf_site_url."user_activation.php?mode=video_ignore&video_id=".$video_id."&actkey=".$actkey;
		  $edata['URL_DISAPPROVE'] = $conf_site_url."user_activation.php?mode=video_disapprove&video_id=".$video_id."&actkey=".$actkey;

		  $email = new Email($langs, $_SESSION['_lang']);
 		  $email->getEmailFromTemplate ('email_video_approve', $edata) ;
	          $subject = $langs['LANG_EMAIL_VIDEO_APPROVE_LINE_1'];
		  $email->sendAdmin($subject);
                } else {
               	  $credits = new Credits();
	    	  $credits->updateCredits ($auth->getUserId(), 0.2);
  		  $credit_log = new CreditsLog();
	  	  $credit_log->logEvent ($auth->getUserId(), 8, 0.2);
		}
	};
	// redirect to news page
    }
    return $video_id;
  }

}   
?>