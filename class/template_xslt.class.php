<?php
// 
// ============================================================================
// template.class.php                                     revision: 20010504-02
// ----------------------------------------------------------------------------
// Universal template class with multiple data level, loop, object and caching
// support.
// 
// GLOBAL VARIABLES USED:
//            $conf_tpl_cache_ttl
//            $conf_tpl_cache_level
//            $conf_tpl_cache_type
//            $conf_tpl_cache_use_instance
//            $conf_tpl_cache_use_noncachable
//            $conf_tpl_cache_path
//            $conf_tpl_cache_database
//            $conf_tpl_cache_table
//            $conf_tpl_warn_level
//            $conf_tpl_max_include_levels
//            $conf_tpl_process_inputs
//            $conf_tpl_use_language_class
//            $conf_tpl_allow_global
//            $conf_db_server
//            $conf_db_user
//            $conf_db_password
// 
// WARNING:   Not compatible with code written for template.class.php revision
//            prior to 20010118-01.
// 
// STATUS:    Mostly functional. Not tested thoroughly. Not suitable for use in
//            production enviroment yet.
// 
// TODO:      + define constants and configuration variables
//            + implement main classes and methods
//            + implement data parsing
//            + implement a simple data addition mechanism
//            + implement object parsing
//            * implement error handling
//            + implement file caching
//            + object instantiation class and method checking
//            - implement database caching [POSTPONED]
//            - printable area selection
//            - improve performance of addDataItem method !!!
//            + allow not cachable items (decreases performance, remove maybe?)
//            + add optional page instance modifier for caching
//            - cache hit statistics counting (where to log to?) [POSTPONED]
//            + use language class for unused tags
//            + implement special input tag to be used directly with form class
//            + implement global tags (with an underscore: <_TPL:XXX>)
//            + other template inclusion
//            + performance measurement
//            + add diagnostics functions (cache info, etc)
//            ? preparsing of included templates
//            + javadoc style documentation of classes and methods
//            ( - not started yet, * in progress, + done, ? uner question )
// 
// BUGS:      / microtimer sometimes gives out a negative value
//            + block regular expression matches too much
//            ( - pending, * in progress, + fixed, / bogus)
// 
// ----------------------------------------------------------------------------
// Authors:   Martynas Majeris <martynas@xxl101.lt>
// ============================================================================
// 

/**
 * template class
 */

class template_xslt {
  // {{{ properties
  
  var $template_file;
  var $template_contents;
  var $instance; // set to REQUEST_URI in constructor
  var $section; 
  var $cached; 
  var $debug = false; 
  
  var $data;
  
  var $cache_ttl             = 10;
  var $cache_level           = TPL_CACHE_NOTHING;
  var $cache_type            = TPL_CACHE_FILE;
  var $cache_use_instance    = TRUE;
  var $cache_use_noncachable = TRUE;
  var $cache_path            = ''; // set to DOCUMENT_ROOT in constructor
  var $cache_database        = 'cache';
  var $cache_table           = 'cache';
  var $warn_level            = TPL_WARN_CRITICAL;
  var $max_include_levels    = 10;
  var $process_inputs        = TRUE;
  var $use_language_class    = TRUE;
  var $allow_global          = TRUE;
  var $db_server             = 'localhost';
  var $db_user               = 'user';
  var $db_password           = 'password';
  
  // error state
  var $error = FALSE;
  
  // diagnostic variables
  var $start_time = array();
  var $stop_time  = array();
  var $time_took  = array();
  var $cache_used = FALSE;
  
  // various
  var $cache_die_phrase = "<?die('cachefile');?>";
  
  // }}}
  // {{{ class constructor
  
  /**
  ** template constructor.
  **
  ** @access public
  ** @return int error code if there was an error, 0 if success
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function template_xslt () {
    global $conf_tpl_cache_level;
    global $conf_tpl_cache_type;
    global $conf_tpl_cache_use_instance;
    global $conf_tpl_cache_use_noncachable;
    global $conf_tpl_cache_path;
    global $conf_tpl_cache_database;
    global $conf_tpl_cache_table;
    global $conf_tpl_warn_level;
    global $conf_tpl_max_include_levels;
    global $conf_tpl_process_inputs;
    global $conf_tpl_use_language_class;
    global $conf_tpl_allow_global;
    global $conf_db_server;
    global $conf_db_user;
    global $conf_db_password;
    
    global $DOCUMENT_ROOT;
    global $REQUEST_URI;
    $this->instance = $REQUEST_URI;
    if(!empty($conf_tpl_cache_ttl))$this->setCacheTtl($conf_tpl_cache_ttl);
    if(!empty($conf_tpl_cache_level))$this->setCacheLevel($conf_tpl_cache_level);
	if(!empty($conf_tpl_cache_type))$this->setCacheType($conf_tpl_cache_type);
    if(!empty($conf_tpl_cache_use_instance))$this->setCacheUseInstance($conf_tpl_cache_use_instance);
	if(!empty($conf_tpl_cache_use_noncachable))$this->setCacheUseNoncachable($conf_tpl_cache_use_noncachable);
	if(!empty($conf_tpl_cache_database))$this->setCacheDatabase($conf_tpl_cache_database);
	if(!empty($conf_tpl_cache_table))$this->setCacheTable($conf_tpl_cache_table);
	if(!empty($conf_tpl_cache_path))$this->setCachePath($conf_tpl_cache_path);
		else $this->setCachePath($DOCUMENT_ROOT);
	if(!empty($conf_tpl_warn_level))$this->setWarnLevel($conf_tpl_warn_level);
	if(!empty($conf_tpl_max_include_levels))$this->setMaxIncludeLevels($conf_tpl_max_include_levels);
    if(!empty($conf_tpl_process_inputs))$this->setProcessInputs($conf_tpl_process_inputs);
	if(!empty($conf_tpl_use_language_class))$this->setUseLanguageClass($conf_tpl_use_language_class);
	if(!empty($conf_tpl_allow_global))$this->setAllowGlobal($conf_tpl_allow_global);
	if(!empty($conf_db_server))$this->setDbServer($conf_db_server);
	if(!empty($conf_db_user))$this->setDbUser($conf_db_user);
	if(!empty($conf_db_password))$this->setDbPassword($conf_db_password);
	return $this->error();
}
  // }}}
  // {{{ parse()
  
  /**
  ** Parse the template
  ** This method determines correct caching mechanisms and returns cached 
  ** version of the page or initiates template parsing using doParse private 
  ** method.<br><br>
  ** It also logs cache hits.
  ** @param $template_file (not required) full or relative path to template file
  ** @param $data (not required) data for parsing
  ** @param $cache_level (not required) level of caching
  ** @access public
  ** @return string rendered page on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function parse ($tdata) {
    global $REQUEST_URI;
    global $_SERVER;
    global $_GET;
    // start the microtimer to calculate parsing performance
    $this->startTimer('parse');

    if (empty($template_file_in))
      $template_file = $this->template_file;
    else
      $template_file = $template_file_in;
    
    if (empty($cache_level_in))
      $cache_level = $this->cache_level;
    else
      $cache_level = $cache_level_in;
    
    if (empty($instance_in))
      $instance = $this->instance;
    else
      $instance = $instance_in;
    
    if ($cache_level === TPL_CACHE_ALL 
        && $this->isCached($template_file, $instance)) {
      // get rendered page from the cache and return it
      $return = $this->getCachedPage($template_file, $instance);
      // process non-cachable items
      if ($this->cache_use_noncachable && preg_match('/<TPL_NOCACHE/i', $return))
        $return = $this->doParseNoCache($return, $data);
       
      $this->logPageCacheHit($template_file, $instance);
      $this->cache_used = TRUE;
      return $return;
    }
    
    // cache was not used. proceed with the parsing
    // set template file
    // redering begins.... NOW
    if (!$rendered_page = $this->doParse($tdata)) {
      // CRITICAL ERROR!
      // there was an error parsing the template
      // return with an error (FALSE)
      return FALSE;
    }
    
    // template parsed and rendered successefully
    if ($cache_level == TPL_CACHE_ALL) {
      $this->saveCachedPage($rendered_page, $template_file, $instance);
    }
    
    // process non-cachable items
    if ($this->cache_use_noncachable && preg_match('/<TPL_NOCACHE/i', $rendered_page))
      $rendered_page = $this->doParseNoCache($rendered_page, $data);
    
    // stop parsing microtimer
    $this->stopTimer('parse');

    if (isset($_GET['debugphp'])) 
      echo $template_file.$this->showTimer('parse')."<br>";
    return $rendered_page;
  }

  function parseNothing ($template_file_in='', $data_in='', 
                  $cache_level_in='', $instance_in='') {
    global $REQUEST_URI;
    global $_SERVER;
    
    if (empty($template_file_in))
      $template_file = $this->template_file;
    else
      $template_file = $template_file_in;
    
    if (empty($data_in))
      $data = $this->data;
    else
      $data = $data_in;
    
    $data['SELF'] = $_SERVER["PHP_SELF"];
    
    if (empty($cache_level_in))
      $cache_level = $this->cache_level;
    else
      $cache_level = $cache_level_in;
    
    if (empty($instance_in))
      $instance = $this->instance;
    else
      $instance = $instance_in;
   
    // template parsed and rendered successefully
    if ($cache_level == TPL_CACHE_ALL) {
      $this->saveCachedPage('', $template_file, $instance);
    }
    
    return '';
  }

  
  // }}}
  // {{{ doParse()
  
  /**
  ** Performs the preparsing of template.
  ** Blocks and single value names are replaced by whole path to them. 
  ** Eg. <TPL:TITLE> which is the child of block <TPL_SUB:PRODUCT> will be 
  ** replaced with <TPL:PRODUCT.TITLE>
  ** @param $block the block of a template to be parsed
  ** @param $data the data
  ** @access private
  ** @return string rendered block, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.05.04
  **/
  function doParse ($tdata) {

    $xp = new XsltProcessor();
    $xsl = new DomDocument;
    $xsl->load($this->template_file);
    $xp->importStylesheet($xsl);
    
    $xml_doc = new DomDocument;
    $xml_doc->loadXML($tdata->getXMLString());

    $block = $xp->transformToXML($xml_doc);

    // process unused tags with language class
    if ($this->use_language_class) {
      $block = $this->populateLang($block);
    }
    
    return $block;
  }
  
  // }}}
  // {{{ populateBlock()
  
  /**
  ** Populate parsed template with data
  **
  ** @param $block block to be parsed
  ** @param $data data for block pupulation
  ** @access private
  ** @return string rendered block, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.05
  **/
  function populateBlock ($block, $data, $prefix='') {
    $start_time=0;
    if ($this->debug) {
      $time = gettimeofday();
      $start_time = $time['sec'] . '.' . $time['usec'];
    }

    if (is_array($data)) {
      while (list($key, $val) = each($data)) {
        // replace global variables
        if (empty($prefix) && !is_array($val))
        {
          $block = preg_replace("/<_TPL:$key>/", $val, $block);
        }  
        // replace normal variables and blocks
        if (is_array($val)) {
          // block
          $regex  = "/<TPL_SUB:$prefix$key>(.*?)<\/TPL_SUB:$prefix$key>/is";
          if (preg_match ($regex, $block, $arr)) {
            $block_part = $arr[1];
            $block_hash = '';
            while (list($key2, $val2) = each($val)) {
              $block_hash .= $this->populateBlock ($block_part, $val2, 
                                                   $prefix.$key.'.');
            }
            $block = preg_replace($regex, $block_hash, $block);
          }
          else {
            // echo "&gt;&gt;&gt; DEBUG: no value found &lt;&lt;&lt;<br><br>\n\n";
          }
        }
        else {
          // single value
          $block = preg_replace("/<TPL:$prefix$key>/is", $val, $block);
          $block = preg_replace("/<[\/]?TPL_SUB:$prefix$key>/is", '', $block);
        }
      }
    }
    if ($this->debug) {
      $stop_time = 0;
      $time = gettimeofday();
      $stop_time = $time['sec'] . '.' . $time['usec'];
      if ($stop_time - $start_time > 0.1) {
        echo $this->template_file.($stop_time - $start_time)."<br>";
  //      echo $block;
      }
    }

    return $block;
  }
  
  // }}}
  // {{{ populateLang()
  
  /**
  ** Populates unused items using language class
  **
  ** @param $block the block of a template to be parsed
  ** @access private
  ** @return string rendered block, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.25
  **/
  function populateLang ($block) {
    global $langs;
    $tags = array();
    if (is_array($langs)) {
      preg_match_all ("/<[\\_]?TPL:([^.>]*)>/is",
                      $block, $tags, PREG_SET_ORDER);

      for ($i=0; $i < sizeof($tags); $i++) {
        $tag = $tags[$i][1];
        //$name = strtolower($tags[$i][1]);
        if (isset($langs[$tag]))
          $text = $langs[$tag];
        else $text = $tag;
        $block = str_replace($tags[$i][0], $text, $block);
      }
    }
    else {
      // language object $lng has not been instantiated. issue warning
      //      $block = $this->reportWarningMessage('Language object has not been '
      //                                           .'instantiated. Unable to process '
      //                                           .' unused tags.')
      //               . $block;
    }
    return $block;
  }
  
  // }}}
  // {{{ doParseNoCache()
  
  /**
  ** Performs parsing and population with data of not cachable items
  **
  ** @param $block the block of a template to be parsed
  ** @param $data the data
  ** @access private
  ** @return string rendered block, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.06
  **/
  function doParseNoCache ($block, $data) {
    $tags = array();
    preg_match_all ("/<TPL_NOCACHE:([^>]*)>/i",
                    $block, $tags, PREG_SET_ORDER);
    for ($i=0; $i < sizeof($tags); $i++) {
      $name = $tags[$i][1];
      $block = eregi_replace("<TPL_NOCACHE:$name>", $data[$name], $block);
    }
    return $block;
  }
  
  // }}}
  // {{{ procObject()
  
  /**
  ** Process object.
  ** Instantiates an object, executes its paramaters, returns output.
  ** @param $object_name name of the object to instantiate
  ** @param $object_params array of methods with parameters to be passed to 
  ** object
  ** @param $object_output name of the method which generates the output
  ** @access private
  ** @return string output of the object, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.06
  **/
  function procObject ($object_name, $object_params, $object_output) {
    $return = '';
    if (empty($object_output)) {
      // No <TPL_OBJECT_OUTPUT:xxx> parameter specified
      $return =   "No <TPL_OBJECT_OUTPUT:xxx> parameter "
                . " specified for object [$object_name]!";
      return $this->reportErrorMessage($return);
    }
    
    if (!class_exists($object_name)) {
      // no such class has been defined
      $return = "Unable to instantiate object [$object_name]!";
      return $this->reportErrorMessage($return);
    }
    
    $obj = new $object_name;
    for ($i=0; $i < sizeof($object_params); $i++) {
      $method = eregi_replace('\(.*\)', '', $object_params[$i]);
      if (method_exists($obj, $method)) {
        $param = '$obj->'.$object_params[$i].';';
        eval($param);
      }
      else {
        // method does not exist. Set a warning
        $warn = "Method [$method] does not exist in class [$object_name]!";
        $return .= $this->reportErrorMessage($warn);
      }
    }
    $param = '$return .= $obj->'.$object_output.';';
    eval($param);
    return $return;
  }
  
  // }}}
  // {{{ getCachedPage()
  
  /**
  ** Return rendered page from cache
  **
  ** @param $template_file a full or relative path to template file. Not
  ** required if it was already set using method <b>setTemplateFile</b>
  ** @param $instance instance name (optional)
  ** @access private
  ** @return string rendered page, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.01.19
  **/
  function getCachedPage ($template_file='', $instance='') {
    global $db;
    if (empty($template_file))
      $template_file = $this->template_file;
    
    $sql = "SELECT PAGE FROM cache where id='".$this->instance."'";
    $db->query($sql);
    if ($row = $db->nextRow()) {
      return $row['PAGE'];
    }
    else return false;
/*    $cache_file = $this->cache_path
                  . 'tpl_'
                  . md5("$template_file|$instance")
                  . '.php';
    // read cache
  //echo $cache_file;
    if (file_exists($cache_file)) {
      
      if (!($tf=@fopen($cache_file,"r"))) {
        // error reading cache
        return FALSE;
      }
      else {
        // read and return cache file contents
        $return = fread($tf,filesize($cache_file));
        $return = str_replace($this->cache_die_phrase, '', $return);
        fclose($tf);
        return $return;
      }
    }
    else {
      // cache file does not exist. Return FALSE
      return FALSE;
    }*/
  }
  
  // }}}
  // {{{ saveCachedPage()
  
  /**
  ** Save rendered page to cache
  **
  ** @param $template_file a full or relative path to template file. Not
  ** required if it was already set using method <b>setTemplateFile</b>
  ** @param $instance instance name (optional)
  ** @access private
  ** @return bool TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.01.19
  **/
  function saveCachedPage ($rendered_page, $template_file='', $instance='') {
    global $db;

    if (empty($template_file))
      $template_file = $this->template_file;
    
    if (empty($instance) && $this->cache_use_instance)
      $instance = $this->instance;
  

    $db->query("start transaction");
    $sql="REPLACE INTO cache
	           VALUES ('" . $instance . 
			"','" . str_replace("'", "\'", $rendered_page) . 
			"',NOW(),0)";
		//echo $sql;
    $db->query ( $sql );
    $db->query("commit");
    // create new file
/*    if (!($tf=@fopen($cache_file,"w"))) {
      // error creating cache file
      return FALSE;
    }
    else {
      // file created successefuly
      fwrite($tf, $this->cache_die_phrase . $rendered_page);
      fclose($tf);
      if ($this->cache_path == '/var/www/krepsinis.net/cache/') {
        $headers = "From: $from\r\n";
        $headers .= "Content-Type: text/plain; charset=\"Windows-1257\"\nContent-Transfer-Encoding: 7bit\n";
        mail("borka@tdd.lt", "cache", $_SERVER["PHP_SELF"], $headers);
      }

      return TRUE;
    }

*/
  }
  
  // }}}
  // {{{ getCachedObject()
  
  /**
  ** Return rendered object from cache
  **
  ** @param $object_name name of the object
  ** @param $object_instance object instance set in template
  ** @access private
  ** @return string rendered object, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.01.19
  **/
  function getCachedObject ($object_name, $object_instance) {
    $cache_file = $this->cache_path
                  . 'obj_'
                  . md5("$object_name|$object_instance")
                  . '.php';
    // read cache
    if (file_exists($cache_file)) {
      
      if (!($tf=@fopen($cache_file,"r"))) {
        // error reading cache
        return FALSE;
      }
      else {
        // read and return cache file contents
        $return = fread($tf,filesize($cache_file));
        $return = str_replace($this->cache_die_phrase, '', $return);
        fclose($tf);
        return $return;
      }
    }
    else {
      // cache file does not exist. Return FALSE
      return FALSE;
    }
  }
  
  // }}}
  // {{{ saveCachedObject()
  
  /**
  ** Save rendered object to cache
  **
  ** @param $object_content content of the processed object
  ** @param $object_name name of the object
  ** @param $object_instance object instance set in template
  ** @access private
  ** @return bool TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.06
  **/
  function saveCachedObject ($object_content, $object_name, $object_instance) {
    $cache_file = $this->cache_path
                  . 'obj_'
                  . md5("$object_name|$object_instance")
                  . '.php';
    // delete old cache file
    if (file_exists($cache_file))
      unlink($cache_file);
    
    // create new file
    if (!($tf=@fopen($cache_file,"w"))) {
      // error creating cache file
      return FALSE;
    }
    else {
      // file created successefuly
      $object_content = $this->cache_die_phrase . $object_content;
      fwrite($tf, $object_content);
      fclose($tf);
      return TRUE;
    }
    return '';
  }
  
  // }}}
  // {{{ setSection()
  
  /**
  ** Set template file to be used and load its contents.
  **
  ** @param $template_file full or relative path to template file
  ** @access public
  ** @return bool TRUE if file loaded suscessefully, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function setSection ($section) {
      $this->section = $section;
      return TRUE;
  }

  // }}}
  // {{{ setTemplateFile()
  
  /**
  ** Set template file to be used and load its contents.
  **
  ** @param $template_file full or relative path to template file
  ** @access public
  ** @return bool TRUE if file loaded suscessefully, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function setTemplateFile ($template_file) {
    if (!$contents = $this->readTemplateFile($template_file)) {
      // error opening template file. set the error
      $this->setError(ERR_TPL_FILE);
      return FALSE;
    }
    else {
      // file opened successefuly. load contents
      $this->template_file = $template_file;
      $this->template_contents = $contents;
      return TRUE;
    }
  }
  
  // }}}
  // {{{ readTemplateFile()
  
  /**
  ** Read template file contents
  **
  ** @param $template_file full or relative path to template file
  ** @access private
  ** @return string template contents, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.05
  **/
  function readTemplateFile ($template_file='') {
    if (!($tf=@fopen($template_file,"r"))) {
      // error opening template file.
      return FALSE;
    }
    else {
      // file opened successefuly. load contents
      $return = @fread($tf,filesize($template_file));
      fclose($tf);
      return $return;
    }
  }
  
  // }}}
  // {{{ addData()
  
  /**
  ** Add Data to be used in template.
  **
  ** @param $data an associative array with data to be used in parsing
  ** @access public
  ** @return bool TRUE if data loaded suscessefully, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function addData ($data) {
    if (!is_array($data)) {
      // $data is not an associative array. set error
      $this->setError(ERR_TPL_DATA);
      return FALSE;
    }
    else {
echo $data;
      $this->data = $data;
echo $this->data;
      return TRUE;
    }
  }
  
  // }}}
  // {{{ addDataItem()
  
  /**
  ** Add single key of data to a current row
  ** Parameter $key should hold a string representing the path to the data. I.e.
  ** 'FEATURES.ITEMS.PRICE'.<br>
  ** Parameter $data should hold an associative array or string value. If the 
  ** $key is a block item, then $data should hold an associative array of all 
  ** the subelements.
  ** @param $key key to add datarow to
  ** @param $data an associative array with data to be used in parsing
  ** @access public
  ** @return bool TRUE if data loaded suscessefully, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function addDataItem ($key, $data) {
    $path_arr = '';
    $last_key = 0;
    $path = explode('.', $key);
    // build an appropriate array key for data assignment
    for ($i=0; $i<sizeof($path); $i++) {
      $keytmp = $path[$i];
      if (!is_numeric($keytmp) && !is_numeric($last_key)) {
        // get the specific part of the array
        $eval = '$data_arr = $this->data'.$path_arr.';';
        eval($eval);
        if (is_array($data_arr)) {
          end($data_arr);
          $line = key($data_arr);
          if (isset($data_arr[$line][$keytmp]) && !isset($path[$i+1]))
            $line++;
          $path_arr .= '['.$line.']';
        }
        else {
          $path_arr .= '[0]';
        }
      }
      // check for special tags in key
      // [N] - start a new row
      // [C] - use current last row
      if ($keytmp == '[N]' || $keytmp == '[C]') {
        $newline = 0;
        // get the specific part of the array
        $eval = '$data_arr = $this->data'.$path_arr.';';
        eval($eval);
        if (is_array($data_arr)) {
          if ($keytmp == '[N]') {
            // new line. increment number
            $newline = 1;
          }
          // find out the key of the last element in the array
          end($data_arr);
          $keytmp = key($data_arr) + $newline;
        }
        else {
          // the key is not an array. so it's empty. assign zero
          $keytmp = 0;
        }
      }
      $last_key = $keytmp;
      $path_arr .= "['".strtoupper($keytmp)."']";
    }
    // assign data
    $eval = '$this->data'.$path_arr.' = $data;';
    eval($eval);
  }
  
  // }}}
  // {{{ resetData()
  
  /**
  ** Reset data
  **
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function resetData () {
    $this->data = array();
    return TRUE;
  }
  
  // }}}
  // {{{ setCacheTtl()
  
  /**
  ** Set cache TTL (time to live) in minutes
  ** Argument passed must be integer number of minutes. If caching is on, cached
  ** version of page will be checked if it was created with TTL number of
  ** minutes. If it was, no further parsing will occur and cached version will
  ** be returned by parse method.<br><br>
  ** Note: if set to 0, all caching will be truned off!
  ** @param $cache_ttl minutes to keep cached page
  ** @access public
  ** @return bool TRUE if value is valid, FALSE if value is NOT valid
  ** @author martynas / martynas@xxl101.lt / 2001.01.19
  **/
  function setCacheTtl ($cache_ttl) {
    if (!is_int($cache_ttl)) {
      // error - parameter must be an integer
      $this->setError(ERR_TPL_CACHE_TTL);
      return FALSE;
    }
    else {
      if ($cache_ttl == 0)
        $this->setCacheLevel(TPL_CACHE_NOTHING);
      else
        $this->cache_ttl = $cache_ttl;
      return TRUE;
    }
  }
  
  // }}}
  // {{{ setCacheLevel()
  
  /**
  ** Set cache level
  ** Sets cache level for a template. 
  ** <ul>
  ** <li>TPL_CACHE_ALL - caches rendered version of the page as well as all the
  ** objects in a template individually
  ** <li>TPL_CACHE_OBJECTS - cache only objects
  ** <li>TPL_CACHE_NOTHING - caching is turned off
  ** </ul>
  ** @param $cache_level cache level (TPL_CACHE_ALL, etc)
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function setCacheLevel ($cache_level) {
    $this->cache_level = $cache_level;
    return TRUE;
  }
  
  // }}}
  // {{{ setCacheType()
  
  /**
  ** Set cache type.
  **
  ** @param $cache_type TPL_CACHE_DB or TPL_CACHE_FILE
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function setCacheType ($cache_type) {
    $this->cache_type = $cache_type;
    return TRUE;
  }
  
  // }}}
  // {{{ setCacheUseInstance()
  
  /**
  ** Turn on/off using of instance modifier for caching operations.
  ** If instance modifier is turnet on (TRUE), caching will be performed for
  ** this particular instance (REQUEST_URI by default. Instance can be
  ** explicitly set using setInstance() method. It does not necesserily has to
  ** be URL of the page. It can be a general name like 'news headlines',
  ** 'request form', etc.
  ** @param $use_instance TRUE - use instance, FALSE - do not use them
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.03.06
  **/
  function setCacheUseInstance ($use_instance) {
    if ($use_instance === TRUE)
      $use_instance = TRUE;
    else
      $use_instance = FALSE;
    $this->cache_use_instance = $use_instance;
    return TRUE;
  }
  
  // }}}
  // {{{ setCacheUseNoncachable()
  
  /**
  ** Turn on/off using of noncachable items in templates
  ** If this option is turned on (default - off) by setting it to TRUE, template
  ** parser will check for special noncachable tags <TPL_NOCACHE:xxxxxx> in
  ** templates EVEN if template cache was used. It is disabled by default beause
  ** it significantly slows down cache performance. It is still faster than not
  ** use cache at all but significantly slower than without noncachable items -
  ** even if there are no noncachable items in particular template.
  ** @param $use_noncachable TRUE - use noncachable, FALSE - do not use them
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.03.07
  **/
  function setCacheUseNoncachable ($use_noncachable) {
    if ($use_noncachable === TRUE)
      $use_noncachable = TRUE;
    else
      $use_noncachable = FALSE;
    $this->cache_use_noncachable = $use_noncachable;
    return TRUE;
  }
  
  // }}}
  // {{{ setInstance()
  
  /**
  ** Set page instance for cache operations.
  ** Sets the instance of the page for cache operations. If using of instances
  ** is turned off, it turns them on.
  ** @param $instance page instance name
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.03.06
  **/
  function setInstance ($instance) {
    $this->instance = $instance;
    $this->cache_use_instance = TRUE;
    unset($this->cached);
    return TRUE;
  }
  
  // }}}
  // {{{ setCachePath()
  
  /**
  ** Set and validate template cache path.
  **
  ** @param $cache_path full or relative path to store templace cache files
  ** @access public
  ** @return bool TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function setCachePath ($cache_path) {
    if (!($td=@opendir($cache_path))) {
      // cannot open directory. set the error
      $this->setError(ERR_TPL_CACHE_FILE);
      return FALSE;
    }
    else {
      // directory opened successefuly. set cache path
      $this->cache_path = $cache_path;
      closedir($td);
      return TRUE;
    }
  }
  
  // }}}
  // {{{ setCacheDatabase()

  /**
  ** Set cache database.
  **
  ** @param $cache_database cache database
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function setCacheDatabase ($cache_database) {
    $this->cache_database = $cache_database;
    return TRUE;
  }

  // }}}
  // {{{ setCacheTable()

  /**
  ** Set cache table.
  **
  ** @param $cache_table cache table
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function setCacheTable ($cache_table) {
    $this->cache_table = $cache_table;
    return TRUE;
  }

  // }}}
  // {{{ setWarnLevel()

  /**
  ** Set warning level
  ** Warning level indicates what and if warnings should be shown in final
  ** rendered page.<br>
  ** <ul>
  ** <li>TPL_WARN_IGNORE - will show no warnings
  ** <li>TPL_WARN_CRITICAL - will show only critical errors (could not create
  ** an object, object returned error, data is incorrect
  ** <li>TPL_WARN_ALL - will show all warnings including dataset present in
  ** template but no corresponding data was supplied
  ** </ul>
  ** @param $warn_level warning level (possible values explained in method
  ** description
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function setWarnLevel ($warn_level) {
    $this->warn_level = $warn_level;
    return TRUE;
  }

  // }}}
  // {{{ setMaxIncludeLevels()

  /**
  ** Set maximum include levels.
  ** Maximum include level indicates how many passes to perform looking for
  ** include files. This template class support multiple includes, i.e. it will
  ** include template files that are included in files that are included in
  ** template.
  ** @param $max_include_levels maximum of file include levels
  ** description
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.03.05
  **/
  function setMaxIncludeLevels ($max_include_levels) {
    $this->max_include_levels = $max_include_levels;
    return TRUE;
  }

  // }}}
  // {{{ setProcessInputs()

  /**
  ** Set wether to process special input tags using form class or not
  ** If set to TRUE template class will parse template for form input tags
  ** <TPL_INPUT:xxx> and try to to replace them using form class.<br>
  ** <b>Note</b>: form class should already be instantiated using object name
  ** $frm. If it is not instantiated template class will not process input tags.
  ** @param $process_inputs TRUE/FALSE
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.03.20
  **/
  function setProcessInputs ($process_inputs) {
    $this->process_inputs = $process_inputs;
    return TRUE;
  }

  // }}}
  // {{{ setUseLanguageClass()

  /**
  ** Set wether to use language class for unused basic template items
  ** If set to TRUE template class will try to use language class to find data
  ** for unused items. Language class instance should be available in $lng 
  ** object. For language part $PHP_SELF variable is used. If it produces no 
  ** output, language class method is called again, with the only parameter.
  ** <br><br>
  ** <b>Note</b>: setting the data through template class methods overrides 
  ** language class. That is if the data for the item is set both ways, than 
  ** one set through template class will be used.
  ** @param $use_language_class TRUE/FALSE
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.03.25
  **/
  function setUseLanguageClass ($use_language_class) {
    $this->use_language_class = $use_language_class;
    return TRUE;
  }

  // }}}
  // {{{ setAllowGlobal()

  /**
  ** Set wether to allow global variables in templates
  ** If set to TRUE global tags will be processed (<_TPL:XXX>). Global means 
  ** that they will bear the same value when used alone or in any block or 
  ** subblock.<br>
  ** <b>Note</b>: usage of global tags introduces additional performance drain.
  ** Use it only if you require the functionality.
  ** @param $allow_global TRUE/FALSE
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.03.20
  **/
  function setAllowGlobal ($allow_global) {
    $this->allow_global = $allow_global;
    return TRUE;
  }

  // }}}
  // {{{ setDbServer()

  /**
  ** Set database server
  **
  ** @param $db_server database server
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function setDbServer ($db_server) {
    $this->db_server = $db_server;
    return TRUE;
  }
  
  // }}}
  // {{{ setDbUser()

  /**
  ** Set database server.
  **
  ** @param $db_user database user
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function setDbUser ($db_user) {
    $this->db_user = $db_user;
    return TRUE;
  }
  
  // }}}
  // {{{ setDbPassword()

  /**
  ** Set database password.
  **
  ** @param $db_password database user
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function setDbPassword ($db_password) {
    $this->db_password = $db_password;
    return TRUE;
  }
  
  // }}}
  // {{{ isCached()

  /**
  ** Check if there is valid cached version of this template available
  ** It is strongly advised to use this function prior to building any data in a
  ** php file for increased performance and reduced server load. Example:<br>
  ** <code>
  ** $template = '/lib/thetemplate.html';
  ** if ($tpl->isCached($template)) {
  **    echo $tpl->parse($template);
  ** }
  ** else {
  **    // data building
  **    // .............
  **    // .............
  **    echo $tpl->parse($template, $data);
  ** }
  ** </code>
  ** @param $template_file a full or relative path to template file. Not
  ** required if it was already set using method <b>setTemplateFile</b>
  ** @param $instance page instance (optional)
  ** @access public
  ** @return bool TRUE if valid cache exists, FALSE if there is no cached
  ** version or it is outdated (according to TTL value set)
  ** @author martynas / martynas@xxl101.lt / 2001.01.19
  **/
  function isCached ($template_file='', $instance='') {
    // return FALSE if caching is turned off without any further work
    global $db;

    if (isset($this->cached))
      return $this->cached;

    if ($this->cache_level === TPL_CACHE_NOTHING)
      return FALSE;
    
    if (empty($template_file))
      $template_file = $this->template_file;
    
    if (empty($instance) && $this->cache_use_instance)
      $instance = $this->instance;
    
    $db->query("start transaction");
    $sql = "SELECT CHANGING, DATE_GENERATED < DATE_ADD(NOW(), INTERVAL -".$this->cache_ttl." MINUTE) as EXPIRED FROM cache where id='".$this->instance."' LOCK IN SHARE MODE";
    $db->query($sql);
    if ($row = $db->nextRow()) {
      if ($row['CHANGING'] == 1 || $row['EXPIRED'] == 0) {
        $db->query("commit");
        $this->cached = true;
        return true;
      }
      else if ($row['EXPIRED'] == 1) {
        $sdata['changing'] = "1";
        $db->update('cache', $sdata, "id='".$this->instance."'");
        $db->query("commit");
        $this->cached = false;
        return false;
      } 
    }
    else {
      $sdata['key'] = "'".$this->instance."'";
      $sdata['date_generated'] = "NOW()";
      $sdata['changing'] = "1";
      $db->insert('cache', $sdata);
      $db->query("commit");
      $this->cached = false;
      return false;
    }

/*    $cache_file = $this->cache_path
                  . 'tpl_'
                  . md5("$template_file|$instance")
                  . '.php';
    // read cache
    if (file_exists($cache_file)) {
      $cache_time = filemtime($cache_file);
      $cache_age = (time()-$cache_time) / 60;
      if ($cache_age <= $this->cache_ttl) {
        // valid cache found
        return TRUE;
      }
      else {
        // cache is outdated
        return FALSE;
      }
    }
    else {
      // file does not exist. So does cache.
      return FALSE;
    }*/
  }
  
  // }}}
  // {{{ isObjectCached()

  /**
  ** Check if there is valid cached version of the object available
  **
  ** @param $object_name object name
  ** @param $object_instance object instance
  ** @access private
  ** @return bool TRUE if valid cache exists, FALSE if there is no cached
  ** version or it is outdated (according to TTL value set)
  ** @author martynas / martynas@xxl101.lt / 2001.03.06
  **/
  function isObjectCached ($object_name, $object_instance) {
    // return FALSE if caching is turned off without any further work
    if ($this->cache_level === TPL_CACHE_NOTHING)
      return FALSE;
    
    $cache_file = $this->cache_path
                  . 'obj_'
                  . md5("$object_name|$object_instance")
                  . '.php';
    // read cache
    if (file_exists($cache_file)) {
      $cache_time = filemtime($cache_file);
      $cache_age = (time()-$cache_time) / 60;
      if ($cache_age <= $this->cache_ttl) {
        // valid cache found
        return TRUE;
      }
      else {
        // cache is outdated
        return FALSE;
      }
    }
    else {
      // file does not exist. So does cache.
      return FALSE;
    }
  }
  
  // }}}
  // {{{ setDbPassword()

  /**
  ** Set database password.
  **
  ** @param $db_password database user
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
/*  function setDbPassword ($db_password) {
    $this->db_password = $db_password;
    return TRUE;
  }*/
  
  // }}}
  // {{{ logPageCacheHit()

  /**
  ** Log a hit to a page cache
  ** 
  ** @param $template_file a full or relative path to template file. Not
  ** required if it was already set using method <b>setTemplateFile</b>
  ** @param $instance page instance (optional)
  ** @access private
  ** @return bool TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.01.19
  **/
  function logPageCacheHit ($template_file='', $instance='') {
    if (empty($template_file))
      $template_file = $this->template_file;
    
    // --- NOT IMPLEMENTED YET ---
    // --- NOT IMPLEMENTED YET ---
    // --- NOT IMPLEMENTED YET ---
    // --- NOT IMPLEMENTED YET ---
    return TRUE;
  }
  
  // }}}
  // {{{ logObjectCacheHit()

  /**
  ** Log a hit to a object cache
  ** 
  ** @param $object_name name of the object
  ** @param $object_instance object instance set in template
  ** @access private
  ** @return bool TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.01.19
  **/
  function logObjectCacheHit ($object_name, $object_instance) {
    // --- NOT IMPLEMENTED YET ---
    // --- NOT IMPLEMENTED YET ---
    // --- NOT IMPLEMENTED YET ---
    // --- NOT IMPLEMENTED YET ---
    return TRUE;
  }
  
  // }}}
  // {{{ setError()
  
  /**
  ** Set error.
  **
  ** @param $error error code
  ** @access private
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
    
  function setError ($error) {
    $this->error = $error;
  }
  
  // }}}
  // {{{ error()
  
  /**
  ** Check if there was an error
  **
  ** @access public
  ** @return bool TRUE if there was an error, FALSE if no
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
    
  function error () {
    if ($this->error > 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
  
  // }}}
  // {{{ getError()
  
  /**
  ** Get the code of the last error
  **
  ** @access public
  ** @return int error code, 0 if there was no error
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
    
  function getError () {
    if ($this->error()) {
      return $this->error;
    }
    else {
      return 0;
    }
  }
  
  // }}}
  // {{{ getErrorMessage()
  
  /**
  ** Get the message of the last error
  **
  ** @access public
  ** @return string error message, NULL if there was no error
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
    
  function getErrorMessage () {
    if ($this->error()) {
      // --- NOT IMPLEMENTED YET ---
      // --- NOT IMPLEMENTED YET ---
      // --- NOT IMPLEMENTED YET ---
      // --- NOT IMPLEMENTED YET ---
      return $this->error;
    }
    else {
      return NULL;
    }
  }
  
  // }}}
  // {{{ resetError()
  
  /**
  ** Reset error as if there was no error
  **
  ** @access public
  ** @return bool TRUE always
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
    
  function resetError () {
    return $this->error = false;
  }
  
  // }}}
  // {{{ reportErrorMessage()
  
  /**
  ** Get error message. Checks against set warning level. Used for template
  ** parsing-time error reporting.
  **
  ** @param $error error text
  ** @param $part which part generated a warning (optional)
  ** @access public
  ** @return string warning message, zero-lenght string if warn level is set
  ** to TPL_WARN_IGNORE or TPL_WARN_CRITICAL
  ** @author martynas / martynas@xxl101.lt / 2001.03.05
  **/
    
  function reportErrorMessage ($error, $part='') {
    $return = '';
    if ($this->warn_level != TPL_WARN_IGNORE)
      $return = $this->formatErrorMessage($error, $part);
    return $return;
  }
  
  // }}}
  // {{{ formatErrorMessage()
  
  /**
  ** Format error message
  **
  ** @param $error warning text
  ** @param $part which part generated an error (optional)
  ** @access public
  ** @return string warning message
  ** @author martynas / martynas@xxl101.lt / 2001.03.05
  **/
    
  function formatErrorMessage ($error, $part='') {
    if (!empty($part))
      $part = " ($part)";
    return "<!-- TEMPLATE ERROR$part: $error -->\n";
  }
  
  // }}}
  // {{{ reportWarningMessage()
  
  /**
  ** Get warning message. Checks against set warning level. Used for template
  ** parsing-time warning reporting.
  **
  ** @param $warning warning text
  ** @param $part which part generated a warning (optional)
  ** @access public
  ** @return string warning message, zero-lenght string if warn level is set
  ** to TPL_WARN_IGNORE or TPL_WARN_CRITICAL
  ** @author martynas / martynas@xxl101.lt / 2001.03.05
  **/
    
  function reportWarningMessage ($warning, $part='') {
    $return = '';
    if ($this->warn_level != TPL_WARN_IGNORE && 
        $this->warn_level != TPL_WARN_CRITICAL)
      $return = $this->formatWarningMessage($warning, $part);
    return $return;
  }
  
  // }}}
  // {{{ formatWarningMessage()
  
  /**
  ** Format warning message
  **
  ** @param $warning warning text
  ** @param $part which part generated a warning (optional)
  ** @access public
  ** @return string warning message
  ** @author martynas / martynas@xxl101.lt / 2001.03.05
  **/
    
  function formatWarningMessage ($warning, $part='') {
    if (!empty($part))
      $part = " ($part)";
    return "<!-- TEMPLATE WARNING$part: $warning -->\n";
  }
  
  // }}}
  // {{{ startTimer()
  
  /**
  ** Start internal microtimer
  **
  ** @param $part named timer (optional)
  ** @access private
  ** @return bool always TRUE
  ** @author martynas / martynas@xxl101.lt / 2001.03.06
  **/
    
  function startTimer ($timer='main') {
    $time = gettimeofday();
    $start_time = $time['sec'] . '.' . $time['usec'];
    $this->start_time[$timer] = $start_time;
    return TRUE;
  }
  
  // }}}
  // {{{ stopTimer()
  
  /**
  ** Stop internal microtimer and calculate the total microtime
  **
  ** @param $part named timer (optional)
  ** @access private
  ** @return bool always TRUE
  ** @author martynas / martynas@xxl101.lt / 2001.03.06
  **/
    
  function stopTimer ($timer='main') {
    $time = gettimeofday();
    $stop_time = $time['sec'] . '.' . $time['usec'];
    $this->stop_time[$timer] = $stop_time;
    $this->time_took[$timer] =   $this->stop_time[$timer]
                               - $this->start_time[$timer];
    return TRUE;
  }
  
  // }}}
  // {{{ showTimer()
  
  /**
  ** Show time in microseconds for the particular timer
  **
  ** @param $part named timer (optional)
  ** @access public
  ** @return bool always TRUE
  ** @author martynas / martynas@xxl101.lt / 2001.03.06
  **/
    
  function showTimer ($timer='main') {
    return $this->time_took[$timer];
  }
  
  // }}}
  // {{{ cacheUsed(
  /**
  ** Shows wether cache was used for the latest parsing operation.
  **
  ** @access public
  ** @return bool TRUE if cache was used, FALSE if not
  ** @author martynas / martynas@xxl101.lt / 2001.03.06
  **/ 
  function cacheUsed () {
    return $this->cache_used;
  }
  // }}}

  function saveEmptyCache ($template_file='', $section='', $instance='') {
    return TRUE;
  }
}
?>