<?php
// 
// ============================================================================
// language.class.php                                     revision: 20010320-01
// ----------------------------------------------------------------------------
// Language class to handle multilanguage sites. To determine which language is
// used, class checks $lang variable. If it is empty class checks global
// configuration variable $conf_lang. If it is empty too, class assumes english
// language (EN).
// 
// GLOBAL VARIABLES USED:
//            $conf_lang
//            $conf_lang_file
//            $lang
// 
// STATUS:    usable
// 
// TASK LIST: + main class
//            + constructor
//            + language file loading
//            + getText method
//            + error handling (language file not found, etc.)
//            ( - not started yet, * in progress, + done, ? uner question )
// 
// BUGS:      -
// 
// ----------------------------------------------------------------------------
// Authors:   Martynas Majeris <martynas@xxl101.lt>
// ============================================================================
// 

/**
 * language class
 */

class language {
  // {{{ properties
  
  var $lang      = EN;
  var $lang_file = '';
  var $lang_data = array();
  
  // error state
  var $error      = FALSE;
  var $error_text = '';
  
  // }}}
  // {{{ class constructor
  
  /**
  ** language constructor.
  **
  ** @param $lang_file full or relative path to language file (optional)
  ** @access public
  ** @author martynas / martynas@xxl101.lt / 2001.03.18
  **/
  function language ($lang_file='') {
    global $conf_lang;
    global $conf_lang_file;
    global $lang;
    
    if (!empty($lang)) {
      $this->setLanguage($lang);
    }
    elseif (!empty($conf_lang))
      $this->setLanguage($conf_lang);
    
    if (!empty($lang_file))
      $this->setLangFile($lang_file);
    elseif (!empty($conf_lang_file))
      $this->setLangFile($conf_lang_file);
  }
  
  // }}}
  // {{{ setLangFile()
  
  /**
  ** Load language file
  **
  ** @param $lang_file full or relative path to language file
  ** @access public
  ** @return bool TRUE if file loaded suscessefully, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.18
  **/
  function setLangFile ($lang_file) {
    if ($lang_file != $this->lang_file) {
      if (!@include_once($lang_file)) {
        // error reading language file
        $this->setError(ERR_LANG_FILE);
        return FALSE;
      }
      
      if (!is_array($lang_data)) {
        // erroneous language data
        $this->setError(ERR_LANG_DATA);
        return FALSE;
      }
      
      $this->lang_file = $lang_file;
      $this->lang_data = $lang_data;
      return TRUE;
    }
    else {
      // this language file has already been loaded
      return TRUE;
    }
  }
  
  // }}}
  // {{{ getText()
  
  /**
  ** Get text message in selected language
  **
  ** @param $part part of the language data, i.e. 'orders', etc
  ** @param $key particular key (optional)
  ** @access public
  ** @return string text message, FALSE if no language file was set
  ** @author martynas / martynas@xxl101.lt / 2001.03.18
  **/
  function getText ($part, $key='') {
    if (empty($this->lang_file)) {
      // error: no language file was set
      $this->setError(ERR_LANG_NOFILE);
      return FALSE;
    }
    
    // No error checking wether the part/key was set.
    // This behaviour is by design. Might change in the future though.
    if (empty($key)) {
      // no key specified. assuming part is the key
      return $this->lang_data[$part][$this->lang];
    }
    else {
      return $this->lang_data[$part][$key][$this->lang];
    }
  }
  
  // }}}
  // {{{ setLanguage()
  
  /**
  ** Set language
  **
  ** @param $lang language code: en, lt
  ** @access public
  ** @author martynas / martynas@xxl101.lt / 2001.03.18
  **/
    
  function setLanguage ($lang) {
    $this->lang = $lang;
  }
  
  // }}}
  // {{{ lang()
  
  /**
  ** Get language
  **
  ** @access public
  ** @return string language
  ** @author martynas / martynas@xxl101.lt / 2001.03.18
  **/
    
  function lang () {
    return $this->lang;
  }
  
  // }}}
  // {{{ langUrl()
  
  /**
  ** Get language setting to appent to query string
  **
  ** @param $sep separator to use in querystring (default '?')
  ** @access public
  ** @return string language
  ** @author martynas / martynas@xxl101.lt / 2001.03.20
  **/
    
  function langUrl ($sep='?') {
    global $conf_lang;
    if ($conf_lang == $this->lang) {
      // default language. no passing through query string required
      return '';
    }
    else {
      return $sep . 'lang=' . $this->lang;
    }
  }
  
  // }}}
  // {{{ setError()
  
  /**
  ** Set error.
  **
  ** @param $error error code
  ** @access private
  ** @author martynas / martynas@xxl101.lt / 2001.03.18
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
  ** @author martynas / martynas@xxl101.lt / 2001.03.18
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
}
?>