<?php
// check if login does not contain any illegal characters
function loginOk ($login) {
  $ereg = '/^[a-zA-Z0-9_\-]*$/';
  if (preg_match($ereg, $login))
    return TRUE;
  else
    return FALSE;
}

// check if it's a valid email address
function emailOk ($email) {
  if (preg_match('/^.*@.*\..*$/', $email))
    return TRUE;
  else
    return FALSE;
}

// check if it's a valid phone number
function phoneOk ($phone) {
  $phone = preg_replace('/[^0123456789]{1}/', '', $phone);
  if (strlen($phone) > 5)
    return TRUE;
  else
    return FALSE;
}

// check if it's a valid mobile number
function mobileOk ($phone) {
  if (is_numeric($phone)) //&& in_array($code, $valid_codes))
    return TRUE;
  else
    return FALSE;
}

function getMobile ($phone) {
  $phone = "'".$phone."'";
  return preg_replace('/[^0123456789]{1}/', '', $phone);
}

function getMobileCode($phone) {
      $phone_code = ereg_replace('[^0123456789]{1}', '', $phone);
      $phone_code1 = str_replace(' ', '', $phone_code);
      // remove country code
      $phone_code2 = ereg_replace('^370', '', $phone_code1);
      $phone_code3 = substr($phone_code2, 1, 2);
 return $phone_code3;
}

function lookup($target){
  if( preg_match("/[a-zA-Z]/i", $target) )
    $ntarget = gethostbyname($target);
  else
    $ntarget = gethostbyaddr($target);
  return $ntarget;
}

// is referer is in posted vars, use it
// otherwise use $HTTP_REFERER
function getReferer (&$postvars) {
  global $_SERVER;
  $ref = '';
  if (isset($postvars['referer']))
    $ref = $postvars['referer'];
  else if (isset($_SERVER['HTTP_REFERER']))
    $ref = $_SERVER['HTTP_REFERER'];
  
  $ref = preg_replace('/[?&]del=[0-9]*/', '', $ref);
  return $ref;
}

// searches for value of $name in posted form data, querystring and preset data
function getVal ($name) {
  global $_POST;
  global $HTTP_GET_VARS;
  global $PRESET_VARS;
  if (isset($_POST[$name]))
    return $_POST[$name];
  elseif (isset($HTTP_GET_VARS[$name]))
    return $HTTP_GET_VARS[$name];
  else if (isset($PRESET_VARS[$name]))
    return $PRESET_VARS[$name];
  else return "";
}

// truncate string to $limit characters and append with $appendix if longer 
// then $limit
function truncateString ($str, $limit, $appendix = '...') {
  //mb_internal_encoding("UTF-8");
  if (is_array($str)) {
    while (list($key, $val) = each($str)) {
      if (strlen($val) > $limit) {
        $str[$key] = substr($val, 0, $limit).$appendix;
      }
    }
  }
  elseif (strlen($str) > $limit) {
    $str = substr($str, 0, $limit).$appendix;
  }
  return $str;
}

function truncateStringML ($str, $langs, $limit, $appendix = '...') {
  if (is_array($str)) {
    while (list($key, $val) = each($str)) {
      $val = $langs[$val];
      if (strlen($val) > $limit) {
        $str[$key] = substr($val, 0, $limit).$appendix;
      }
    }
  }
  elseif (strlen($langs[$str]) > $limit) {
    $str = substr($str, 0, $limit).$appendix;
  }
  return $str;
}


function trimString ($str) {
  if (is_array($str)) {
    while (list($key, $val) = each($str)) {
      if (strlen($val) > $limit) {
        $str[$key] = trim($val);
      }
    }
  }
  else {
    $str = trim($str);
  }
  return $str;
}

// lowercase all array keys
function keysToLower ($arr) {
  if (!is_array($arr)) {
    return $arr;
  }
  
  $newarr = array();
  while (list($key, $val) = each($arr)) {
    $newarr[strtolower($key)] = $val;
  }
  return $newarr;
}

// uppercase all array keys
function keysToUpper ($arr) {
  if (!is_array($arr)) {
    return $arr;
  }
  
  $newarr = array();
  while (list($key, $val) = each($arr)) {
    $newarr[strtoupper($key)] = $val;
  }
  return $newarr;
}

// make lithuanian text uppercase
function toUpper ($str) {
  $str = strtoupper($str);
  $str = str_replace('ą', 'Ą', $str);
  $str = str_replace('č', 'Č', $str);
  $str = str_replace('ę', 'Ę', $str);
  $str = str_replace('ė', 'Ė', $str);
  $str = str_replace('į', 'Į', $str);
  $str = str_replace('š', 'Š', $str);
  $str = str_replace('ų', 'Ų', $str);
  $str = str_replace('ū', 'Ū', $str);
  $str = str_replace('ž', 'Ž', $str);
  return $str;
}

// make lithuanian text lowercase
function toLower ($str) {
  $str = strtolower($str);
  $str = str_replace('Ą', 'ą', $str);
  $str = str_replace('Č', 'č', $str);
  $str = str_replace('Ę', 'ę', $str);
  $str = str_replace('Ė', 'ė', $str);
  $str = str_replace('Į', 'į', $str);
  $str = str_replace('Š', 'š', $str);
  $str = str_replace('Ų', 'ų', $str);
  $str = str_replace('Ū', 'ū', $str);
  $str = str_replace('Ž', 'ž', $str);
  return $str;
}


// make lithuanian text lowercase
function toWindowsFromUTF ($str) {
  return $str;
//  return iconv("UTF-8", "Windows-1257", $str);
}

function toUTFFromWindows ($str) {
  return $str;
   //iconv("Windows-1257", "UTF-8", $str);
}

function toUTFFromWindows2 ($str) {
   return iconv("Windows-1257", "UTF-8", $str);
}

// validates integer value
function evalInt ($int) {
  if (empty($int))
    return 0;
  else
    return $int;
}

// validates integer value to be inserted into sql.
// returns string null if it's empty
function evalIntSql ($int) {
  if ($int > 0)
    return $int;
  else
    return 'null';
}

function addZero ($num, $digits = 2) {
  $d = strlen($num);
  if ($d < $digits)
    return str_repeat('0', $digits-$d).$num;
  else
    return $num;
}

function scriptName ($script) {
  $script = '/'.$script;
  $script = str_replace('\\', '/', $script);
  preg_match('~.*/([^/]*)$~', $script, $arr);
  return $arr[1];
}

// generates universal url
function url ($key='', $val='', $vars='', $key2='', $val2='', $key3='', $val3='') {
  global $_GET;
  global $_POST;
  
  if (!is_array($vars)) {
    $vars = array(
      'year',
      'order',
      'where',
      'query',
      'perpage',
      'personal',
      'page',
      'let',
      'cat',
      'all',
      'group_id',
      'param1',
      'param2',
      'param3',
      'research_id',
      'table_id',
      'item_id',
      'page_id',
      'user_id',
      'team_id',
      'season_id',
      'tseason_id',
      'contest_id',
      'tcats_id',
      'tcats',
      'conference_id',
      'type',
      // forum
      'forum_id',
      'topic_id',
      'msg_id',
      'topic_name',
      // new comments
      'news_id',
      'genre',
      'blog_id',
      'problog_id',
      'gallery_id',
      'slide',
      'comment',
      'title',
      'mt_id', 
      'mseason_id', 
      'tour_id', 
      'question_id', 
      'survey_id', 
      'id',	
      // communities
      'club_id',
      'event_id',
      'folder_id',
      'filter_user',
      'cat_id',
      'domain',
      'iform',
      'ifield',
      'manager_type',
      'injury_list',
      'res_menu' ,
      'league_id',
      'clan_id',
      'full',
      'sched',
      'rating_id',
      'rating_date',
	//
       'past',
       'country'		
      );
  }
  
  $url = '';
  $arr = array();
  $first = '?';
  for ($i=0; $i < sizeof($vars); $i++) {
    $var = $vars[$i];
    $val_post = '';
    if (isset($_POST[$var]))
      $val_post = $_POST[$var];
    $val_get = '';
    if (isset($_GET[$var])) {
      $val_get = $_GET[$var];
    }
    if (isset($val_post) && ($var != $key)) {
      if (!empty($val_post)) {
        $url .= $first.$var.'='.urlencode($val_post);
        $first = '&';
      }
    }
    if (isset($val_get) && ($var != $key)) {
      if (!empty($val_get)) {
        $url .= $first.$var.'='.urlencode($val_get);
        $first = '&';
      }
    }
  }
  
  if (!empty($key) && !empty($val)) {
    $url .= $first.$key.'='.urlencode($val);
  }

  if (!empty($key2) && !empty($val2)) {
    $url .= '&'.$key2.'='.urlencode($val2);
  }

  if (!empty($key3) && !empty($val3)) {
    $url .= '&'.$key3.'='.urlencode($val3);
  }
  
  return $url;
}

// generates universal url
function wapurl ($key='', $val='', $vars='', $key2='', $val2='', $key3='', $val3='') {
  global $HTTP_GET_VARS;
  global $_POST;
  
  if (!is_array($vars)) {
    $vars = array(
      'order',
      'where',
      'query',
      'where_int',
      'query_less',
      'query_more',
      'perpage',
      'personal',
      'page',
      'let',
      'cat',
      'all',
      'group_id',
      'param1',
      'param2',
      'param3',
      'research_id',
      'table_id',
      'page_id',
      'user_id',
      'team_id',
      'season_id',
      'problog_id',
      'type',
      'year',
      'tour_id',
      'mseason_id',  	
      'mt_id',  	
      // forum
      'topic_id',
      'msg_id',
      'topic_name',
      // new comments
      'news_id',
      'comment',
      'title',
      // communities
      'cat_id'
      );
  }
  
  $url = '';
  $arr = array();
  $first = '?';
  
  for ($i=0; $i < sizeof($vars); $i++) {
    $var = $vars[$i];
    $val_post = $_POST[$var];
    $val_get = $HTTP_GET_VARS[$var];
    if (isset($val_post) && ($var != $key)) {
      if (!empty($val_post)) {
        $url .= $first.$var.'='.urlencode($val_post);
        $first = '&amp;';
      }
    }
    elseif (isset($val_get) && ($var != $key)) {
      if (!empty($val_get)) {
        $url .= $first.$var.'='.urlencode($val_get);
        $first = '&amp;';
      }
    }
  }
  
  if (!empty($key) && !empty($val)) {
    $url .= $first.$key.'='.urlencode($val);
  }

  if (!empty($key2) && !empty($val2)) {
    $url .= '&amp;'.$key2.'='.urlencode($val2);
  }

  if (!empty($key3) && !empty($val3)) {
    $url .= '&amp;'.$key3.'='.urlencode($val3);
  }
  
  return $url;
}


// make plural from a lithuanian word
function makePlural ($name) {
  if (ereg('is$', $name)) $name = ereg_replace('is$', 'ai', $name);
  elseif (ereg('IS$', $name)) $name = ereg_replace('IS$', 'AI', $name);
  elseif (ereg('as$', $name)) $name = ereg_replace('as$', 'ai', $name);
  elseif (ereg('AS$', $name)) $name = ereg_replace('AS$', 'AI', $name);
  elseif (ereg('us$', $name)) $name = ereg_replace('us$', 'ai', $name);
  elseif (ereg('US$', $name)) $name = ereg_replace('US$', 'AI', $name);
  elseif (ereg('ys$', $name)) $name = ereg_replace('ys$', 'iai', $name);
  elseif (ereg('YS$', $name)) $name = ereg_replace('YS$', 'IAI', $name);
  elseif (ereg('ė$', $name)) $name = ereg_replace('ė$', 'ės', $name);
  elseif (ereg('Ė$', $name)) $name = ereg_replace('Ė$', 'ĖS', $name);
  elseif (ereg('a$', $name)) $name = ereg_replace('a$', 'os', $name);
  elseif (ereg('A$', $name)) $name = ereg_replace('A$', 'OS', $name);
  return $name;
}

// change the ending of the lithuanian name
function litend ($name) {
  if (ereg('is$', $name)) $name = ereg_replace('is$', 'i', $name);
  elseif (ereg('IS$', $name)) $name = ereg_replace('IS$', 'I', $name);
  elseif (ereg('as$', $name)) $name = ereg_replace('as$', 'ai', $name);
  elseif (ereg('AS$', $name)) $name = ereg_replace('AS$', 'AI', $name);
  elseif (ereg('us$', $name)) $name = ereg_replace('us$', 'au', $name);
  elseif (ereg('US$', $name)) $name = ereg_replace('US$', 'AU', $name);
  elseif (ereg('ys$', $name)) $name = ereg_replace('ys$', 'y', $name);
  elseif (ereg('YS$', $name)) $name = ereg_replace('YS$', 'Y', $name);
  elseif (ereg('ė$', $name)) $name = ereg_replace('ė$', 'e', $name);
  elseif (ereg('Ė$', $name)) $name = ereg_replace('Ė$', 'E', $name);
  return $name;
}

// change the ending of the name
function litend2 ($name) {
  if (ereg('us$', $name)) $name = ereg_replace('us$', 'uje', $name);
  elseif (ereg('US$', $name)) $name = ereg_replace('US$', 'UJE', $name);
  elseif (ereg('as$', $name)) $name = ereg_replace('as$', 'e', $name);
  elseif (ereg('AS$', $name)) $name = ereg_replace('AS$', 'E', $name);
  elseif (ereg('ai$', $name)) $name = ereg_replace('ai$', 'uose', $name);
  elseif (ereg('AI$', $name)) $name = ereg_replace('AI$', 'UOSE', $name);
  elseif (ereg('is$', $name)) $name = ereg_replace('is$', 'yje', $name);
  elseif (ereg('IS$', $name)) $name = ereg_replace('IS$', 'YJE', $name);
  elseif (ereg('ys$', $name)) $name = ereg_replace('ys$', 'yje', $name);
  elseif (ereg('YS$', $name)) $name = ereg_replace('YS$', 'YJE', $name);
  elseif (ereg('os$', $name)) $name = ereg_replace('os$', 'ose', $name);
  elseif (ereg('OS$', $name)) $name = ereg_replace('OS$', 'OSE', $name);
  elseif (ereg('a$', $name)) $name = ereg_replace('a$', 'oje', $name);
  elseif (ereg('A$', $name)) $name = ereg_replace('A$', 'OJE', $name);
  elseif (ereg('ė$', $name)) $name = ereg_replace('ė$', 'ėje', $name);
  elseif (ereg('Ė$', $name)) $name = ereg_replace('Ė$', 'ĖJE', $name);
  return $name;
}

// change the ending of the name
function litend3 ($name) {
  if (ereg('us$', $name)) $name = ereg_replace('us$', 'aus', $name);
  elseif (ereg('US$', $name)) $name = ereg_replace('US$', 'AUS', $name);
  elseif (ereg('as$', $name)) $name = ereg_replace('as$', 'o', $name);
  elseif (ereg('AS$', $name)) $name = ereg_replace('AS$', 'O', $name);
  elseif (ereg('ai$', $name)) $name = ereg_replace('ai$', 'ų', $name);
  elseif (ereg('AI$', $name)) $name = ereg_replace('AI$', 'Ų', $name);
  elseif (ereg('is$', $name)) $name = ereg_replace('is$', 'io', $name);
  elseif (ereg('IS$', $name)) $name = ereg_replace('IS$', 'IO', $name);
  elseif (ereg('ys$', $name)) $name = ereg_replace('ys$', 'io', $name);
  elseif (ereg('YS$', $name)) $name = ereg_replace('YS$', 'IO', $name);
  elseif (ereg('os$', $name)) $name = ereg_replace('os$', 'ų', $name);
  elseif (ereg('OS$', $name)) $name = ereg_replace('OS$', 'Ų', $name);
  elseif (ereg('a$', $name)) $name = ereg_replace('a$', 'os', $name);
  elseif (ereg('A$', $name)) $name = ereg_replace('A$', 'OS', $name);
  elseif (ereg('ė$', $name)) $name = ereg_replace('ė$', 'ės', $name);
  elseif (ereg('Ė$', $name)) $name = ereg_replace('Ė$', 'ĖS', $name);
//  return iconv("Windows-1257", "UTF-8", $name);
  return $name;
}

// change the ending of the name
function litend4 ($name) {
  if (ereg('ius$', $name)) $name = ereg_replace('ius$', 'ė', $name);
  elseif (ereg('as$', $name)) $name = ereg_replace('as$', 'ė', $name);
  elseif (ereg('is$', $name)) $name = ereg_replace('is$', 'ė', $name);
  return $name;
}
// get unix timestamp from a formated date
function getTimestamp ($date) {
  if (preg_match('/([0-9]{2,4})[- ]{1}([0-9]{1,2})[- ]{1}([0-9]{1,2})[^0-9]*([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/', $date, $arr)) {
    // datetime with seconds
    return mktime($arr[4], $arr[5], $arr[6], $arr[2], $arr[3], $arr[1]);
  }
  elseif (preg_match('/([0-9]{2,4})[- ]{1}([0-9]{1,2})[- ]{1}([0-9]{1,2})[^0-9]*([0-9]{1,2}):([0-9]{1,2})/', $date, $arr)) {
    // datetime w/o seconds
    return mktime($arr[4], $arr[5], 0, $arr[2], $arr[3], $arr[1]);
  }
  elseif (preg_match('/([0-9]{2,4})[- ]{1}([0-9]{1,2})[- ]{1}([0-9]{1,2})/', $date, $arr)) {
    // date
    return mktime(0, 0, 0, $arr[2], $arr[3], $arr[1]);
  }
}

// compare two dates if the day matches
function today ($date1, $date2='') {
  if (empty($date2))
    $date2 = time();
  // convert to UNIX timestamp
  if (!is_int($date1))
    $date1 = getTimestamp($date1);
  if (!is_int($date2))
    $date2 = getTimestamp($date2);
  
  if (date('Y-m-d', $date1) == date('Y-m-d', $date2))
    return TRUE;
  else
    return FALSE;
}

// get long date
function getDateRSS ($date = '') {
  global $lng;
  if (empty($date))
    $date = time();
  elseif(!is_int($date))
    $date = getTimestamp($date);
  $return = date('D, d M Y H:i:s', $date);
//  $return = str_replace('!', $lng->getText('months2', date('n', $date)), $return);
  return $return;
}

// get long date
function getLongDate ($date = '') {
  global $lng;
  if (empty($date))
    $date = time();
  elseif(!is_int($date))
    $date = getTimestamp($date);
  $return = date('Y ! j \d.', $date);
  $return = str_replace('!', $lng->getText('months2', date('n', $date)), $return);
  return $return;
}

// get long datetime
function getLongDateTime ($date = '') {
  global $lng;
  if (empty($date))
    $date = time();
  elseif(!is_int($date))
    $date = getTimestamp($date);
  $return = date('Y ! j \d./G:i', $date);
  $return = str_replace('!', $lng->getText('months2', date('n', $date)), $return);
  return $return;
}

// get short datetime
function getShortDateTime ($date = '') {
  global $lng;
  if (empty($date))
    $date = time();
  elseif(!is_int($date))
    $date = getTimestamp($date);
  $return = date('Y-m-d H:i', $date);
  return $return;
}

function arrSlice ($array, $offset='') {
  $return = '';
  if (is_array($array)) {
    while (list($key, $val) = each($array)) {
      if (is_array($val)) {
        $return .= $offset."<b>$key:</b><br>\n";
        $return .= $offset.arrSlice($val, $offset.'&nbsp;&nbsp;');
      }
      else {
        $return .= $offset."<b>$key:</b> ".htmlEncode($val)."<br>\n";
      }
      $return .= "<br>\n";
    }
  }
  else {
    $return = htmlEncode($array);
  }
  return $return;
}

function wapTextEncode ($str) {
// from text to html
  $str = str_replace("\r\n", '<br/>', $str);
  $str = str_replace("\n", '<br/>', $str);
  $str = str_replace(". <br/>", '.<br/>', $str);
  $str = str_replace("<B>", '<b>', $str);
  $str = str_replace("</B>", '</b>', $str);
  $str = str_replace("border=0", 'border="0"', $str);
  
  $patterns = "#<table[^>]*>(.*)</table>#sUi";
  $str = preg_replace($patterns, "", $str);

  $patterns = "#<object[^>]*>(.*)</object>#sUi";
  $str = preg_replace($patterns, "", $str);

  return $str;
}

function ascii_encode($string)  {
   for ($i=0; $i < strlen($string); $i++)  {
       $encoded .= '%'.ord(substr($string,$i));    
   }
   return $encoded;
} 

function textEncode ($str) {
// from text to html
  $str = str_replace("\r\n", '<br/>', $str);
  $str = str_replace("\n", '<br/>', $str);
  return $str;
}

function textDecode ($str) {
// from text to html
  $str = str_replace("<br>", '\r\n', $str);
  return $str;
}

function formEncode ($str) {
  $str = str_replace('<', '&lt;', $str);
  $str = str_replace('>', '&gt;', $str);
  $str = str_replace('"', '&quot;', $str);
  return $str;
}

function htmlEncode ($str) {
  $str = str_replace('<', '&lt;', $str);
  $str = str_replace('>', '&gt;', $str);
  $str = str_replace("\n", '<br>', $str);
  $str = str_replace("\t", '&nbsp;&nbsp;', $str);
  $str = str_replace(' ', '&nbsp;&nbsp;', $str);
  return $str;
}

function xmlentities($string, $quote_style=ENT_COMPAT)
{
  $trans = get_html_translation_table(HTML_ENTITIES, $quote_style);

  foreach ($trans as $key => $value)
      $trans[$key] = '&#'.ord($key).';';

  return strtr($string, $trans);
}

function msgEncode2 ($msg, $cnt = 34) {
  $len = strlen($msg);
  for ($c = 0; $c < $len; $c++) {
    if ($msg[$c] != " ")
      $un++;
    else $un=0;
    if ($un == $cnt) {
      $msgout .= "<wbr></wbr>".$msg[$c];
      $un = 0;
    }
    else 
      $msgout .= $msg[$c];
  }
  $msgout = str_replace("www.von.lt", "www.krepsinis.net", $msgout);
  $msgout = str_replace("cyberdunk", "krepsinis", $msgout);
  $msgout = str_replace("c y b e r d u n k", "krepsinis", $msgout);
  $msgout = str_replace("c.y.b.e.r.d.u.n.k", "krepsinis", $msgout);
  $msgout = str_replace("ref=15944", "krepsinis", $msgout);

  return $msgout;
}

function msgEncode ($msg, $cnt = 39) {
  $sml = array(
    ':)' => 'forum_smile.gif',
    ':-)' => 'forum_smile.gif',
    ':-D' => 'forum_smile_big.gif',
    ':grin:' => 'forum_smile_big.gif',
    ':D' => 'forum_smile_big.gif',
    ':roll:' => 'icon_rolleyes.gif',
    ':lol:' => 'icon_lol.gif',
    ':twisted:' => 'icon_twisted.gif',
    '8D' => 'forum_smile_cool.gif',
    ':I' => 'icon_neutral.gif',
    ':-I' => 'icon_neutral.gif',
    ':neutral:' => 'icon_neutral.gif',
    ':mrgreen:' => 'icon_mrgreen.gif',
    ':P' => 'forum_smile_tongue.gif',
    ':-P' => 'forum_smile_tongue.gif',
    ':razz:' => 'forum_smile_tongue.gif',
    '}:)' => 'forum_smile_evil.gif',
    ':evil:' => 'forum_smile_evil.gif',
    ';)' => 'forum_smile_wink.gif',
    ';-)' => 'forum_smile_wink.gif',
    ':wink:' => 'forum_smile_wink.gif',
    ':oops:' => 'icon_redface.gif',
    ':o)' => 'forum_smile_clown.gif',
    ':o' => 'icon_surprised.gif',
    ':-o' => 'icon_surprised.gif',
    ':eek:' => 'icon_surprised.gif',
    'B)' => 'forum_smile_blackeye.gif',
    ':(' => 'forum_smile_sad.gif',
    ':-(' => 'forum_smile_sad.gif',
    ':sad:' => 'forum_smile_sad.gif',
    '8)' => 'icon_cool.gif',
    '8-)' => 'icon_cool.gif',
    ':cool:' => 'icon_cool.gif',
    ':O' => 'forum_smile_shock.gif',
    ':shock:' => 'forum_smile_shock.gif',
    ':(!' => 'forum_smile_angry.gif',
    'X(' => 'forum_smile_dead.gif',
    '|)' => 'forum_smile_sleepy.gif',
    ':*' => 'forum_smile_kisses.gif',
    ':?:' => 'forum_smile_question.gif',
    ':!:' => 'icon_exclaim.gif',
    ':?' => 'icon_confused.gif',
    ':-?' => 'icon_confused.gif',
    ':???:' => 'icon_confused.gif',
    ':x' => 'icon_mad.gif',
    ':-x' => 'icon_mad.gif',
    ':mad:' => 'icon_mad.gif',
    ':idea:' => 'icon_idea.gif',
    ':arrod:' => 'icon_arrow.gif',
    ':cry:' => 'icon_cry.gif'
  );
  $msg = textEncode($msg);
  
  $len = strlen($msg);
  $msgout = '';
  $un = 0;
  for ($c = 0; $c < $len; $c++) {
    if ($msg[$c] != " " && $msg[$c] != "<")
      $un++;
    else $un=0;
    if ($un == $cnt) {
      $msgout .= "<wbr></wbr>".$msg[$c];
      $un = 0;
    }
    else 
      $msgout .= $msg[$c];
  }

  while (list($key, $val) = each($sml)) {
    $pic = "<img border=\"0\" hspace=\"1\" align=\"absmiddle\" src=\"/img/$val\">";
    $msgout = str_replace($key, $pic, $msgout);
  }

  $msgout = str_replace("www.von.lt", "www.krepsinis.net", $msgout);
  $msgout = str_replace("cyberdunk.com", "krepsinis.net", $msgout);
  $msgout = str_replace("tadukas.tob", "microsoft", $msgout);
  
  return $msgout;
}

function getBrowser ($agent) {
  $binfo = array(
    '.*MSIE',
  );
  // NOT IMPLEMENTED YET !!!
  // NOT IMPLEMENTED YET !!!
  // NOT IMPLEMENTED YET !!!
  // NOT IMPLEMENTED YET !!!
  return $agent;
}

function decho ($str) {
  global $debug;
  if ($debug) {
    echo $str;
  }
}

// changes lithuanian letter to appropriate latin letters
function lt2lat ($str) {
  $str = str_replace('ą','a',$str);
  $str = str_replace('č','c',$str);
  $str = str_replace('ę','e',$str);
  $str = str_replace('ė','e',$str);
  $str = str_replace('į','i',$str);
  $str = str_replace('š','s',$str);
  $str = str_replace('ų','u',$str);
  $str = str_replace('ū','u',$str);
  $str = str_replace('ž','z',$str);
  $str = str_replace('Ą','A',$str);
  $str = str_replace('Č','C',$str);
  $str = str_replace('Ę','E',$str);
  $str = str_replace('Ė','E',$str);
  $str = str_replace('Į','I',$str);
  $str = str_replace('Š','S',$str);
  $str = str_replace('Ų','U',$str);
  $str = str_replace('Ū','U',$str);
  $str = str_replace('Ž','Z',$str);
  $str = str_replace('ö','o',$str);
  return($str);
}

function getSecCode ($msg) {
  $sml = array(
    1000 => 'Users menu',
    1020 => 'Users menu edit',
    2000 => 'News menu',
    2012 => 'News menu main edit',
    2013 => '---',
    2015 => 'Blogs menu',
    2016 => 'Blogs menu edit',
    2020 => 'Portalo ivykiai',
    2040 => 'Running line',
    2045 => 'Video',
    2050 => 'Online',
    2060 => 'Menu puslapiai',
    2070 => 'Menu tyrimai',
    3000 => 'Menu games',
    3012 => 'Scheduler edit',
    3013 => '---',
    3020 => 'Results',
    3022 => 'Results edit',
    3023 => 'Results sending',
    4000 => 'Menu actions',
    4010 => 'Toto',
    4012 => 'Toto edit',
    4030 => 'Contest',
    4032 => 'Contest edit',
    4040 => 'SMS sending',
    4050 => 'Menegeris',
    4060 => 'Wager',
    4070 => 'Bracket',
    5000 => 'Menu basket',
    5100 => 'Menu basket seasons',
    6000 => 'Menu orgs',
    7000 => 'Menu parameters',
    7050 => 'Menu paveiksliukai',
    7070 => 'Password',
    8000 => 'Menu Discussion',
    9000 => 'Menu stats',
    10000 => 'Menu clubs',
    11000 => 'Menu admins',
    11010 => 'Menu admins edit',
    12000 => 'Menu partners',
    12010 => 'TOP SPORT', 
    12020 => 'ORAKULAS',
    12030 => 'RADIOCENTRAS',
    13000 => 'Forum',
    14000 => 'Sport city',
    15000 => 'Shop'
  );
  ///$msg = textEncode($msg);
  return $sml[$msg];
}


function dir_is_empty( $path_to_dir )
{
   $handle = opendir( $path_to_dir );
   if( false === $handle )
       return true; // If PHP4 had exceptions,
                    // we could raise one here.

   while( ( $file = readdir($handle) ) !== false ) {
      // If a file with a non "." or ".." name exists,
      // this dir is not empty
      if( $file !== "." && $file !== ".." ) {
          closedir($dh);
         return false;
      }
   }
   closedir($dh);
   return true;
}

function makeImage($p)
{
  global $conf_home_dir;
  if (!file_exists($conf_home_dir.'imgs/thumbnails')) {
    mkdir($conf_home_dir.'imgs/thumbnails',0777);
  }
  if (!file_exists($conf_home_dir.'imgs/thumbnails/'.$p)) 
  {
//echo "<".$p.">";
   if (strstr($p, '.jpg') || strstr($p, '.jpeg')  || strstr($p, '.JPG') || strstr($p, '.JPEG')) 
    {  
//echo $p;
     $im = imagecreatefromjpeg($conf_home_dir.'imgs/'.$p);
    }
    else if (strstr($p, '.gif')  || strstr($p, '.GIF'))      
           {
             //$im = imagecreatefromgif($conf_home_dir.'imgs/'.$p);
             return $p;    
           }
         else return $p;    
    $x = imagesx($im);
    $y = imagesy($im);
    if ($y > 120)    
      $om = imagecreatetruecolor(96, 120);
    else $om = imagecreatetruecolor(96, $y);
    if ($om) {
    $background = imagecolorallocate($om, 255, 255, 255);
    imagefill($om, 0, 0, $background);
    if ($x > 96 && $x < 160) {
      if ($y > 120)
        imagecopy($om, $im, 0, 0, $x/2 - 48, 0, 96, 120);
      else imagecopy($om, $im, 0, 0, $x/2 - 48, 0, 96, $y);
    } 
    else if ($x < 96) {
      if ($y > 120)
       imagecopy($om, $im, 48 - $x/2 , 0, 0, 0, $x, 120);
      else imagecopy($om, $im, 48 - $x/2 , 0, 0, 0, $x, $y);
     }
    else 
     {
      imagedestroy($om);
      return $p; 
     }

    if (strstr($p, '.jpg') || strstr($p, '.jpeg') || strstr($p, '.JPG') || strstr($p, '.JPEG')) 
    {  
     imagejpeg($om,$conf_home_dir.'imgs/thumbnails/'.$p, 85);
    }
    else if (strstr($p, '.gif') || strstr($p, '.GIF')) 
           {
            $p = str_replace('.gif', '.jpg', $p); 
            imagejpeg($om,$conf_home_dir.'imgs/thumbnails/'.$p, 85);
           }      
  //  imagejpeg($om);
    imagedestroy($om);    
   }
  }
  return 'thumbnails/'.$p;
}

function makeWAPImage($p)
{
  if (!file_exists('./imgs/wapthumbnails')) {
    mkdir('./imgs/wapthumbnails',0777);
  }
  if (!file_exists("./imgs/wapthumbnails/".$p)) 
  {
   if (strstr($p, '.jpg') || strstr($p, '.jpeg')  || strstr($p, '.JPG') || strstr($p, '.JPEG')) 
    {  
     $im = imagecreatefromjpeg("./imgs/".$p);
    }
    else if (strstr($p, '.gif')  || strstr($p, '.GIF'))      
           {
             $im = imagecreatefromgif("./imgs/".$p);
           }
         else return $p;    
    $x = imagesx($im);
    $y = imagesy($im);
    if ($y > 120)    
      $om = imagecreatetruecolor(20, 30);
    else $om = imagecreatetruecolor(20, $y);
    if ($om) {
      $background = imagecolorallocate($om, 255, 255, 255);
      imagefill($om, 0, 0, $background);
      imagecopyresampled($om, $im, 0, 0, 0, 0, 20, 30, $x, $y);
     }
    else 
     {
      imagedestroy($om);
      return $p; 
     }

    if (strstr($p, '.jpg') || strstr($p, '.jpeg') || strstr($p, '.JPG') || strstr($p, '.JPEG')) 
    {  
     imagejpeg($om,'./imgs/wapthumbnails/'.$p, 90);
    }
    else if (strstr($p, '.gif') || strstr($p, '.GIF')) 
           {
            $p = str_replace('.gif', '.jpg', $p); 
            imagejpeg($om,'./imgs/wapthumbnails/'.$p, 90);
           }      
    imagedestroy($om);       
  } 
  return 'wapthumbnails/'.$p;
} 


function apply_bbcode($text, $uid, $uname)
{
	$text = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1&#058;", $text);

	// pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
	// This is important; bbencode_quote(), bbencode_list(), and bbencode_code() all depend on it.
	$text = " " . $text;

	// First: If there isn't a "[" and a "]" in the message, don't bother.
	if (! (strpos($text, "[") && strpos($text, "]")) )
	{
		// Remove padding, return.
		$text = substr($text, 1);
		return $text;
	}

	$text = preg_replace('#\[quote(?:=&quot;)#',"<table width=\"95%\" cellspacing=\"1\" cellpadding=\"3\" align=\"center\" class=\"quote\"><tr><th>", $text);
	$text = preg_replace('#&quot;:#',"</th></tr><tr><td><p align=\"justify\" class=\"textblacksmall\">", $text);
	$text = preg_replace('#&quot;\]#',"</th></tr><tr><td><p align=\"justify\" class=\"textblacksmall\">", $text);
//$text = preg_replace('#quote=&quot;(.*?)&quot;\]#ie', "'dquote=&quot;' . str_replace(array('[', ']'), array('&#91;', '&#93;'), '\$1') . '&quot;]'", $text);
//$text = preg_replace('#\[quote(?:=&quot;(.*?)&quot;)?\]#',"<table width=\"95%\" cellspacing=\"1\" cellpadding=\"3\" border=\"0\" align=\"center\" class=\"quote\"><tr><td><p align=\"justify\" class=\"textblacksmall\">", $text);
	$text = str_replace("[/quote:$uid]", "</p></td></tr></table>", $text);
	$text = str_replace("[/quote]", "</p></td></tr></table>", $text);

	// New one liner to deal with opening quotes with usernames...
	// replaces the two line version that I had here before..
	$text = preg_replace("/\[quote:$uid=\"(.*?)\"\]/si", "<table width=\"95%\" cellspacing=\"1\" cellpadding=\"3\" border=\"0\" align=\"center\" class=\"quote\"><tr><td class=\"textblacksmall\"><p align=\"justify\" class=\"textblacksmall\">", $text);

	// [list] and [list=x] for (un)ordered lists.
	// unordered lists
//	$text = str_replace("[list:$uid]", $bbcode_tpl['ulist_open'], $text);
	// li tags
//	$text = str_replace("[*:$uid]", $bbcode_tpl['listitem'], $text);
	// ending tags
//	$text = str_replace("[/list:u:$uid]", $bbcode_tpl['ulist_close'], $text);
//	$text = str_replace("[/list:o:$uid]", $bbcode_tpl['olist_close'], $text);
	// Ordered lists
//	$text = preg_replace("/\[list=([a1]):$uid\]/si", $bbcode_tpl['olist_open'], $text);

	// colours
        $bbcode_tpl['color_open'] = '<span style="color: {COLOR}">';
        $bbcode_tpl['color_open'] = str_replace('{COLOR}', '\\1', $bbcode_tpl['color_open']);
        $text = preg_replace("/\[color=(\#[0-9A-F]{6}|[a-z]+):$uid\]/si", $bbcode_tpl['color_open'], $text);          
	$text = str_replace("[/color:$uid]", '</span>', $text);


	// size
	$bbcode_tpl['size_open'] = '<span style="font-size: {SIZE}%; line-height: normal">';
        $bbcode_tpl['size_open'] = str_replace('{SIZE}', '\\1', $bbcode_tpl['size_open']);
	$text = preg_replace("#\[size=([\-\+]?\d+):$uid\]#s", $bbcode_tpl['size_open'], $text);
	$text = str_replace("[/size:$uid]", "</span>", $text);


	// [b] and [/b] for bolding text.
	$text = str_replace("[b:$uid]", "<b>", $text);
	$text = str_replace("[/b:$uid]", "</b>", $text);
	$text = str_replace("[b]", "<b>", $text);
	$text = str_replace("[/b]", "</b>", $text);
	// [b] and [/b] for bolding text.


	// [u] and [/u] for underlining text.
	$text = str_replace("[u:$uid]", "<u>", $text);
	$text = str_replace("[/u:$uid]", "</u>", $text);

	// [i] and [/i] for italicizing text.
	$text = str_replace("[i:$uid]", "<i>", $text);
	$text = str_replace("[/i:$uid]", "</i>", $text);
	$text = str_replace("[i]", "<i>", $text);
	$text = str_replace("[/i]", "</i>", $text);


	$text = str_replace("[img:$uid]", "<img src='", $text);
	$text = str_replace("[/img:$uid]", "' border='0'/>", $text);
	$text = str_replace("[img]", "<img src='", $text);
	$text = str_replace("[/img]", "' border='0'/>", $text);
	$text = str_replace("[IMG]", "<img width=\"400\" src='", $text);
	$text = str_replace("[/IMG]", "' border='0'/>", $text);

	// Patterns and replacements for URL and email tags..
	$patterns = array();
	$replacements = array();

	// [img]image_url_here[/img] code..
	// This one gets first-passed..
//	$patterns[] = "#\[img:$uid\]([^?].*?)\[/img:$uid\]#i";
//	$replacements[] = $bbcode_tpl['img'];

	// matches a [url]xxxx://www.phpbb.com[/url] code..
//	$patterns[] = "#\[url\]([\w]+?://[^ \"\n\r\t<]*?)\[/url\]#is";
//	$replacements[] = $bbcode_tpl['url1'];
//
	// [url]www.phpbb.com[/url] code.. (no xxxx:// prefix).
//	$patterns[] = "#\[url\]((www|ftp)\.[^ \"\n\r\t<]*?)\[/url\]#is";
//	$replacements[] = $bbcode_tpl['url2'];

	// [url=xxxx://www.phpbb.com]phpBB[/url] code..
//	$patterns[] = "#\[url=([\w]+?://[^ \"\n\r\t<]*?)\]([^?].*?)\[/url\]#i";
//	$replacements[] = $bbcode_tpl['url3'];

	// [url=www.phpbb.com]phpBB[/url] code.. (no xxxx:// prefix).
//	$patterns[] = "#\[url=((www|ftp)\.[^ \"\n\r\t<]*?)\]([^?].*?)\[/url\]#i";
//	$replacements[] = $bbcode_tpl['url4'];

	// [email]user@domain.tld[/email] code..
//	$patterns[] = "#\[email\]([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\[/email\]#si";
//	$replacements[] = $bbcode_tpl['email'];

//	$text = preg_replace($patterns, $replacements, $text);

	// Remove our padding from the string..
//	$text = substr($text, 1);
	$text = str_replace("$uid]", "", $text);
	return $text;

} // bbencode_second_pass()

function apply_wap_bbcode($text, $uid)
{
	$text = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1&#058;", $text);

	// pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
	// This is important; bbencode_quote(), bbencode_list(), and bbencode_code() all depend on it.
	$text = " " . $text;

	// First: If there isn't a "[" and a "]" in the message, don't bother.
	if (! (strpos($text, "[") && strpos($text, "]")) )
	{
		// Remove padding, return.
		$text = substr($text, 1);
		return $text;
	}

	// [QUOTE] and [/QUOTE] for posting replies with quote, or just for quoting stuff.
	$text = str_replace("[quote:$uid]", "<table width=\"95%\" cellspacing=\"1\" cellpadding=\"3\" border=\"0\" align=\"center\"><tr><td><p align=\"justify\" class=\"textblacksmall\">", $text);
	$text = str_replace("[/quote:$uid]", "</p></td></tr></table>", $text);

	// New one liner to deal with opening quotes with usernames...
	// replaces the two line version that I had here before..
	$text = preg_replace("/\[quote:$uid=\"(.*?)\"\]/si", "<table width=\"95%\" cellspacing=\"1\" cellpadding=\"3\" border=\"0\" align=\"center\"><tr><td class=\"textblacksmall\"><p align=\"justify\" class=\"textblacksmall\">", $text);

	// [list] and [list=x] for (un)ordered lists.
	// unordered lists
//	$text = str_replace("[list:$uid]", $bbcode_tpl['ulist_open'], $text);
	// li tags
//	$text = str_replace("[*:$uid]", $bbcode_tpl['listitem'], $text);
	// ending tags
//	$text = str_replace("[/list:u:$uid]", $bbcode_tpl['ulist_close'], $text);
//	$text = str_replace("[/list:o:$uid]", $bbcode_tpl['olist_close'], $text);
	// Ordered lists
//	$text = preg_replace("/\[list=([a1]):$uid\]/si", $bbcode_tpl['olist_open'], $text);

	// colours
//	$text = preg_replace("/\[color=(\#[0-9A-F]{6}|[a-z]+):$uid\]/si", $bbcode_tpl['color_open'], $text);
//	$text = str_replace("[/color:$uid]", $bbcode_tpl['color_close'], $text);

	// size
//	$text = preg_replace("/\[size=([1-2]?[0-9]):$uid\]/si", $bbcode_tpl['size_open'], $text);
//	$text = str_replace("[/size:$uid]", $bbcode_tpl['size_close'], $text);

	// [b] and [/b] for bolding text.
	$text = str_replace("[b:$uid]", "<b>", $text);
	$text = str_replace("[/b:$uid]", "</b>", $text);
	$text = str_replace("[b]", "<b>", $text);
	$text = str_replace("[/b]", "</b>", $text);
	// [b] and [/b] for bolding text.


	// [u] and [/u] for underlining text.
	$text = str_replace("[u:$uid]", "<u>", $text);
	$text = str_replace("[/u:$uid]", "</u>", $text);

	// [i] and [/i] for italicizing text.
	$text = str_replace("[i:$uid]", "<i>", $text);
	$text = str_replace("[/i:$uid]", "</i>", $text);
	$text = str_replace("[i]", "<i>", $text);
	$text = str_replace("[/i]", "</i>", $text);


	$text = str_replace("[img:$uid]", "<img src='", $text);
	$text = str_replace("[/img:$uid]", "' border='0'/>", $text);
	$text = str_replace("[img]", "<img src='", $text);
	$text = str_replace("[/img]", "' border='0'/>", $text);

	// Patterns and replacements for URL and email tags..
	$patterns = array();
	$replacements = array();

	// [img]image_url_here[/img] code..
	// This one gets first-passed..
//	$patterns[] = "#\[img:$uid\]([^?].*?)\[/img:$uid\]#i";
//	$replacements[] = $bbcode_tpl['img'];

	// matches a [url]xxxx://www.phpbb.com[/url] code..
//	$patterns[] = "#\[url\]([\w]+?://[^ \"\n\r\t<]*?)\[/url\]#is";
//	$replacements[] = $bbcode_tpl['url1'];
//
	// [url]www.phpbb.com[/url] code.. (no xxxx:// prefix).
//	$patterns[] = "#\[url\]((www|ftp)\.[^ \"\n\r\t<]*?)\[/url\]#is";
//	$replacements[] = $bbcode_tpl['url2'];

	// [url=xxxx://www.phpbb.com]phpBB[/url] code..
//	$patterns[] = "#\[url=([\w]+?://[^ \"\n\r\t<]*?)\]([^?].*?)\[/url\]#i";
//	$replacements[] = $bbcode_tpl['url3'];

	// [url=www.phpbb.com]phpBB[/url] code.. (no xxxx:// prefix).
//	$patterns[] = "#\[url=((www|ftp)\.[^ \"\n\r\t<]*?)\]([^?].*?)\[/url\]#i";
//	$replacements[] = $bbcode_tpl['url4'];

	// [email]user@domain.tld[/email] code..
//	$patterns[] = "#\[email\]([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\[/email\]#si";
//	$replacements[] = $bbcode_tpl['email'];

//	$text = preg_replace($patterns, $replacements, $text);

	// Remove our padding from the string..
//	$text = substr($text, 1);

	return $text;

} // bbencode_second_pass()

function flattenArray($array,$keyname='')
{
   $tmp = array();
   foreach($array as $key => $value)
   {
       if(is_array($value))
           $tmp = array_merge($tmp,flattenArray($value,$key));
       else
           $tmp[$key.$keyname] = $value;
   }
   return $tmp;
}

function phpbb_preg_quote($str, $delimiter)
{
	$text = preg_quote($str);
	$text = str_replace($delimiter, '\\' . $delimiter, $text);
	
	return $text;
}

function encode_ip($dotquad_ip)
{
	$ip_sep = explode('.', $dotquad_ip);
	return sprintf('%02x%02x%02x%02x', $ip_sep[0], $ip_sep[1], $ip_sep[2], $ip_sep[3]);
}

function gen_rand_string($hash, $length)
{
	$chars = array( 'a', 'A', 'b', 'B', 'c', 'C', 'd', 'D', 'e', 'E', 'f', 'F', 'g', 'G', 'h', 'H', 'i', 'I', 'j', 'J',  'k', 'K', 'l', 'L', 'm', 'M', 'n', 'N', 'o', 'O', 'p', 'P', 'q', 'Q', 'r', 'R', 's', 'S', 't', 'T',  'u', 'U', 'v', 'V', 'w', 'W', 'x', 'X', 'y', 'Y', 'z', 'Z', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
	
	$max_chars = count($chars) - 1;
	srand( (double) microtime()*1000000);
	
	$rand_str = '';
	for($i = 0; $i < $length; $i++)
	{
		$rand_str = ( $i == 0 ) ? $chars[rand(0, $max_chars)] : $rand_str . $chars[rand(0, $max_chars)];
	}

	return ( $hash ) ? md5($rand_str) : $rand_str;
}

function RemoveXSS($val) {
   // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
   // this prevents some character re-spacing such as <java\0script>
   // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
   $val = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $val);
   
   // straight replacements, the user should never need these since they're normal characters
   // this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
   $search = 'abcdefghijklmnopqrstuvwxyz';
   $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
   $search .= '1234567890!@#$%^&*()';
   $search .= '~`";:?+/={}[]-_|\'\\';
   for ($i = 0; $i < strlen($search); $i++) {
      // ;? matches the ;, which is optional
      // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars
   
      // &#x0040 @ search for the hex values
      $val = preg_replace('/(&#[x|X]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
      // &#00064 @ 0{0,7} matches '0' zero to seven times
      $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
   }
   
   if (!is_array($val))
     while( preg_match("/<(.*)?\s?onclick.+?=?\s?.+?(['\"]).*?\\2\s?(.*)?>/i", $val) )       
	$val = preg_replace("/<(.*)?\s?onclick.+?=?\s?.+?(['\"]).*?\\2\s?(.*)?>/i", "<$1$3>", $val); 

   // now the only remaining whitespace attacks are \t, \n, and \r
   $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'bgsound', 'base');
   $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
   $ra = array_merge($ra1, $ra2);
   
   $found = true; // keep replacing as long as the previous round replaced something
   while ($found == true) {
      $val_before = $val;
      for ($i = 0; $i < sizeof($ra); $i++) {
         $pattern = '/';
         for ($j = 0; $j < strlen($ra[$i]); $j++) {
            if ($j > 0) {
               $pattern .= '(';
               $pattern .= '(&#[x|X]0{0,8}([9][a][b]);?)?';
               $pattern .= '|(&#0{0,8}([9][10][13]);?)?';
               $pattern .= ')?';
            }
            $pattern .= $ra[$i][$j];
         }
         $pattern .= '/i';
         $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
         $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
         if ($val_before == $val) {
            // no replacements were made, so exit the loop
            $found = false;
         }
      }
   }
   return $val;
}

function array_to_string($array) {
   $retval = '';
   $null_value = "^^^";
   foreach ($array as $index => $val) {
       if(gettype($val)=='array') 
         $retval .= array_to_string($val);    
       else $value=$val;
       if (!$value)
           $value = $null_value;
       $retval .=  $index . " | " . $value . "\r\n";
   }
   return $retval;
}



function getDirectory( $path = '.', $level = 0 )
{
  $dirs = '';
  // Directories to ignore when listing output.
  $ignore = array( '.', '..', '.svn' );

  // Open the directory to the handle $dh
  $dh = @opendir( $path );

  // Loop through the directory
  $c = 0;
  $dirs[0] = 'root';
  while ( false !== ( $file = readdir( $dh ) ) ) {
   // Check that this file is not to be ignored
    if ( !in_array( $file, $ignore ) )  {
      // Show directories only
      if(is_dir( "$path/$file" ) ) {
         $dirs[$file] = $file;
         $c++; 
      // Re-call this same function but on a new directory.
      // this is what makes function recursive.
       //echo "$spaces<a href='$path/$file/index.php'>$file</a><br />";
       //getDirectory( "$path/$file", ($level+1) );
      }
    }
  }
   // Close the directory handle
  closedir( $dh );
  return $dirs;
} 

function getmicrotime(){ 
  list($usec, $sec) = explode(" ",microtime()); 
  return ((float)$usec + (float)$sec); 
} 

function get_translation($params, $smarty)
{
  global $langs;
  if (!isset($langs[$params["fonema"]]))
    return $params["fonema"];
  else return $langs[$params["fonema"]];
}


function closetags ( $html )
{
  #put all opened tags into an array
  preg_match_all ( "#<([a-z]+)( .*)?(?!/)>#iU", $html, $result );
  $openedtags = $result[1];
 
  #put all closed tags into an array
  preg_match_all ( "#</([a-z]+)>#iU", $html, $result );
  $closedtags = $result[1];
  $len_opened = count ( $openedtags );
  # all tags are closed
  if( count ( $closedtags ) == $len_opened ) {
    return $html;
  }
  $openedtags = array_reverse ( $openedtags );
  # close tags
  for( $i = 0; $i < $len_opened; $i++ ) {
   if ( !in_array ( $openedtags[$i], $closedtags ) && $openedtags[$i] != 'br' && $openedtags[$i] != 'img')  {
     $html .= "</" . $openedtags[$i] . ">";
   } else {
     unset ( $closedtags[array_search ( $openedtags[$i], $closedtags)] );
   }
  }
  return $html;
}

function decrypt($string, $key) {
  return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
}

?>