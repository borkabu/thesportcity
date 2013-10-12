<?php
// 
// ============================================================================
// form.class.php                                         revision: 20010506-01
// ----------------------------------------------------------------------------
// Universal form handling class
// 
// GLOBAL VARIABLES USED:
//            $conf_form_file
// 
// STATUS:    usable
// 
// TASK LIST: + main class
//            + constructor
//            + form definition file loading
//            + getInput method
//            + public method
//            - separate methods for each type of form inputs
//            - date combined form generation methods
//            * error handling (form definition file not found, etc.)
//            - separate CSS support
//            - client side validation
//            ( - not started yet, * in progress, + done, ? uner question )
// 
// BUGS:      -
// 
// ----------------------------------------------------------------------------
// Authors:   Martynas Majeris <martynas@xxl101.lt>
// ============================================================================
// 

/**
 * form class
 */

class form {
  // {{{ properties
  
  var $form_file    = '';
  var $form_data    = array();
  var $form_options = array();
  
  // common settings
  var $class;
  
  // error state
  var $error      = FALSE;
  var $error_text = '';
  
  // }}}
  // {{{ class constructor
  
  /**
  ** form constructor.
  **
  ** @param $form_file full or relative path to form definition file (optional)
  ** @access public
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function form ($form_file='') {
    global $conf_form_file;
    
    if (!empty($form_file))
      $this->setFormFile($form_file);
    elseif (!empty($conf_form_file))
      $this->setFormFile($conf_form_file);
  }
  
  // }}}
  // {{{ setFormFile()
  
  /**
  ** Load form definition file
  **
  ** @param $form_file full or relative path to form definition file
  ** @access public
  ** @return bool TRUE if file loaded suscessefully, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function setFormFile ($form_file) {
    if ($form_file != $this->form_file) {
      if (!include_once($form_file)) {
        // error reading form definition file
        $this->setError(ERR_FORM_FILE);
        return FALSE;
      }
      
      if (!is_array($form_data)) {
        // erroneous form data
        $this->setError(ERR_FORM_DATA);
        return FALSE;
      }
      $this->form_file    = $form_file;
      $this->form_data    = $form_data;
//      $this->form_options = $form_options;
      return TRUE;
    }
    else {
      // this form definition file has already been loaded
      return TRUE;
    }
  }
  
  // }}}
  // {{{ setClass()
  
  /**
  ** Set common stylesheet for all form elements
  **
  ** @param $class stylesheet class
  ** @access public
  ** @author martynas / martynas@xxl101.lt / 2001.04.28
  **/
  function setClass ($class) {
    $this->class = $class;
  }
  
  // }}}
  // {{{ getInput()
  
  /**
  ** Format and return input
  **
  ** @param $type type of the form input (FORM_INPUT_TEXT, etc.)
  ** @param $name name of the input
  ** @param $value value of the input (optional)
  ** @param $params input parameters: string or associative array (optional)
  ** @param $selected selected value (optional)
  ** @access public
  ** @return string rendered HTML form tag
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function getInput ($type, $name, $value='', $params=array(), $selected='') {
    global $langs;

    $return = '';
    // assign value over default one
    if (!empty($params['value_force'])) {
      $params['value'] = $params['value_force'];
      unset($params['value_force']);
    }
    elseif (!empty($value)) {
      $params['value'] = $value;
    }
    $param_str = $this->renderParams($params);
    if (!strpos(strtolower($param_str), 'class=') && !empty($this->class)) {
      $param_str .= ' class="'.$this->class.'"';
    }
    switch($type) {
      case FORM_INPUT_TEXT:
        $return = '<input type="text" id="' . $name .'" name="' . $name .'"' . $param_str . '/>';
        break;
        
      case FORM_INPUT_PASSWORD:
        $return = '<input type="password" name="' . $name .'"' . $param_str . '/>';
        break;
        
      case FORM_INPUT_RADIO:
        $return = '<input type="radio" name="' . $name . '"' . $param_str;
        if ($params['value'] === $selected || $selected === TRUE)
          $return .= ' checked';
        $return .= '/>';
        break;
        
      case FORM_INPUT_CHECKBOX:
        $return = '<input type="checkbox" name="' . $name . '"' . $param_str;
        if ($params['value'] == $selected || $selected === TRUE)
          $return .= ' checked';
        $return .= '/>';
        break;
        
      case FORM_INPUT_TEXTAREA:
        $return = '<textarea id="' . $name . '" name="' . $name . '"'. $param_str . '>'
                  . (isset($params['value'])?$params['value']:'') . '</textarea>';
        break;
      
      case FORM_INPUT_HIDDEN:
        $return = '<input type="hidden" name="' . $name .'"' . $param_str . '/>';
        break;
      
      case FORM_INPUT_SELECT:
	$multiple = '';
        if (isset($params['multiple']) && $params['multiple']) {
          // multiple selection box. add [] to name
          $name = $name.'[]';
          $multiple .= ' multiple';
        }
        $return = '<select name="' . $name . '" id="' . $name . '"'. $param_str;
        $return .= $multiple.'>';
        // generate option list
        if (isset($params['options']) && is_array($params['options'])) {
          $selact = FALSE;
          while (list($key, $val) = each($params['options'])) {
            if ($key === '[E]')
              $key = '';
	    $style="";
            if (is_array($val)) {
              if (isset($val['style']))
                $style=$val['style'];
              $val = $val['value'];
            } 

            $return .= '<option '.$style.' value="' . $key . '"';
            if ($key == $selected && (!$selact || isset($params['multiple']))) {
              $return .= ' selected';
              $selact = TRUE;
            }
            if (is_array($selected) && in_array($key, $selected)) {
              $return .= ' selected';
              $selact = TRUE;
            }
            if (isset($langs[$val]))
              $return .= '>' . $langs[$val] . "</option>\n";
            else $return .= '>' . $val . "</option>\n";
          }
        }
        $return .= '</select>';
        break;
        
      case FORM_INPUT_IMAGE:
        $return = '<input type="image" name="'.$name.'"'.$param_str.'/>';
        break;
        
      case FORM_INPUT_BUTTON:
        $return = '<input type="button" name="'.$name.'"'.$param_str.'/>';
        break;
        
      case FORM_INPUT_SUBMIT:
        $return = '<input type="submit" name="'.$name.'"'.$param_str.'>';
        break;
        
      case FORM_INPUT_RESET:
        $return = '<input type="reset" name="'.$name.'"'.$param_str.'>';
        break;
        
      case FORM_INPUT_DATE:
        $blank = TRUE;
        if (isset($params['noblank']))
          $blank = FALSE;
        $inputs = $this->dateInput($params['value'], $name, '', '', $params, '', $blank);
        while (list($key, $val) = each($inputs)) {
          $return .= "$val ";
        }
        break;
      
      case FORM_INPUT_DATETIME:
        $blank = TRUE;
        if (isset($params['noblank']))
          $blank = FALSE;
        // process date
        $inputs = $this->dateInput(isset($params['value']) ? $params['value']: '', $name, '', '', $params, '', $blank);
        while (list($key, $val) = each($inputs)) {
          $return .= "$val ";
        }
        // process time
        $inputs = $this->timeInput(isset($params['value']) ? $params['value']: '', $name, $params, '', $blank);
        while (list($key, $val) = each($inputs)) {
          $return .= "$val ";
        }
        break;
        
      default:
        // invalid input type
        $this->setError(ERR_FORM_INPUT);
        return '';
    }
    return $return;
  }
  
  // }}}
  // {{{ getInputWithValue()
  
  /**
  ** Wrapper function for getInput which looks for the field value in posted
  ** and passed values through querystring
  **
  ** @param $part part of the file
  ** @param $name name of the input
  ** @param $value value of the input (optional)
  ** @param $selected selected value (optional)
  ** @access public
  ** @return string rendered HTML form tag
  ** @author martynas / martynas@xxl101.lt / 2001.05.04
  **/
  function getInputWithValue ($type, $name, $params=array()) {
    global $_POST;
    global $_GET;
    global $PRESET_VARS;

    $value = '';
    if (isset($_POST[$name]))
      $value = $_POST[$name];
    elseif (isset($_GET[$name]))
      $value = $_GET[$name];
    else if (isset($PRESET_VARS[$name]))
      $value = $PRESET_VARS[$name];

    return $this->getInput($type, $name, $value, $params, $value);
  }
  
  // }}}
  // {{{ getField()
  
  /**
  ** Get particular field based on parameters from form definition file
  **
  ** @param $part part of the file
  ** @param $name name of the input
  ** @param $value value of the input (optional)
  ** @param $selected selected value (optional)
  ** @access public
  ** @return string rendered HTML form tag
  ** @author martynas / martynas@xxl101.lt / 2001.01.20
  **/
  function getField ($part, $name, $value='', $selected='') {
    global $lng;
    global $lang;

    $data = $this->form_data[$part][$name];
    if (isset($data['params']['value']) && empty($value)) {
      $value = $data['params']['value'];
    }
    // if value equals TRUE it means use language class to determine the text
    if ($value === TRUE) {
      if (is_object($lng)) {
        $value = $lng->getText($part, $name);
      }
      else {
        // language class $lng has not been instantiated.
        $value = '';
      }
    }
    
    // process 
    
    if (!is_array($data)) {
      // error: no such field defined
      $this->setError(ERR_FORM_INPUT);
      return '';
    }
    else {
      $params = isset($data['params']) ? $data['params'] : '';
      // process SELECT input's options
      if ($data['type'] == FORM_INPUT_SELECT) {
        if (!is_array($params['options']) && !empty($params['options'])) {
          $params['options'] = $this->form_options[$params['options']];
        }
        // check options for language specific data
        while (list($key, $val) = each($params['options'])) {
          if (is_array($val)) {
            if (is_object($lng)) {
              $params['options'][$key] = $val[$lng->lang()];
            }
            else {
              $params['options'][$key] = $val[$lang];
            }
          }
        }
      }

      return $this->getInput($data['type'], $name, $value, 
                             $params, $selected);
    }
  }
  
  // }}}
  // {{{ getFieldWithValue()
  
  /**
  ** Wrapper function for getField which looks for the field value in posted
  ** and passed values through querystring
  **
  ** @param $part part of the file
  ** @param $name name of the input
  ** @param $value value of the input (optional)
  ** @param $selected selected value (optional)
  ** @access public
  ** @return string rendered HTML form tag
  ** @author martynas / martynas@xxl101.lt / 2001.05.04
  **/
  function getFieldWithValue ($part, $name) {
    global $_POST;
    global $HTTP_GET_VARS;
    global $PRESET_VARS;
    $value='';
    if (isset($_POST[$name])) {
	if ( get_magic_quotes_gpc() )
	  $value = htmlspecialchars( stripslashes( $_POST[$name] ) ) ;
	else
  	  $value = htmlspecialchars( $_POST[$name] ) ;
//      $value = stripslashes($_POST[$name]);
    }
    elseif (isset($HTTP_GET_VARS[$name])) {
      $value = $HTTP_GET_VARS[$name];
    }
    elseif (isset($PRESET_VARS[$name])) {
      $value = $PRESET_VARS[$name];
    }
    elseif ($this->form_data[$part][$name]['type'] == FORM_INPUT_DATE) {
      // DATE format. assemble value from the pieces
      $empty = TRUE;
      $vars = array('y', 'm', 'd');
      for ($c = 0; $c < sizeof($vars); $c++) {
        $key = $name.'_'.$vars[$c];
        if (isset($_POST[$key]))
          $x[$vars[$c]] = stripslashes($_POST[$key]);
        elseif (isset($HTTP_GET_VARS[$key]))
          $x[$vars[$c]] = $HTTP_GET_VARS[$key];
        else if (isset($PRESET_VARS[$key]))
          $x[$vars[$c]] = $PRESET_VARS[$key];
        
        // check if value is not empty
        if (empty($x[$vars[$c]])) {
          $empty = TRUE;
        }
      }
      if ($empty)
        $value = '';
      else
        $value = $x['y'].'-'.$x['m'].'-'.$x['d'];
    }
    elseif ($this->form_data[$part][$name]['type'] == FORM_INPUT_DATETIME) {
      // DATETIME format. assemble value from the pieces
      $empty = TRUE;
      $vars = array('y', 'm', 'd', 'h', 'i');
      for ($c = 0; $c < sizeof($vars); $c++) {
        $key = $name.'_'.$vars[$c];
        if (isset($_POST[$key]))
          $x[$vars[$c]] = stripslashes($_POST[$key]);
        elseif (isset($HTTP_GET_VARS[$key]))
          $x[$vars[$c]] = $HTTP_GET_VARS[$key];
        else if (isset($PRESET_VARS[$key]))
          $x[$vars[$c]] = $PRESET_VARS[$key];
        
        // check if value is not empty
        if (empty($x[$vars[$c]])) {
          $empty = TRUE;
        }
      }
      if ($empty)
        $value = '';
      else
        $value = $x['y'].'-'.$x['m'].'-'.$x['d'].' '.$x['h'].':'.$x['i'];
    }
    return $this->getField($part, $name, $value, $value);
  }
  
  // }}}
  // {{{ renderParams()
  
  /**
  ** Render a list of parameters
  **
  ** @param $params associative array (key - parameter, value - parameter value)
  ** @access private
  ** @return string generated parameter string
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
  function renderParams ($params) {
    $return = '';
    if (is_array($params)) {
      while (list($key, $val) = each($params)) {
        if (strtolower($key) != 'options' &&
            strtolower($key) != 'multiple') {
          // omit option and multiple tags
          if (strtolower($key) == 'value') {
            $val = $this->safeEncode($val);
          }
          $return .= " $key=\"$val\"";
        }
      }
      return $return;
    }
    else {
      // parameter is simple string. pass back unchanged
      return $params;
    }
  }
  
  // }}}
  // {{{ dateInput()
  
  /**
  ** Generate three dorpdown boxes for date (year, month, day)
  **
  ** @param $date selected date in UNIX timestamp or commond format (YYYY-MM-DD)
  ** @param $name name of the field
  ** @param $from lower range for year dropdown (default: 1930)
  ** @param $to upper range for year dropdown (default: current year)
  ** @param $params parameter array
  ** @param $append simple array of suffixes to be appended to each dropdown 
  **                name to (i.e. array('_year', '_month', '_day'))
  ** @param $blank if TRUE prepends all dropdowns with blank option
  ** @access public
  ** @return array with three formated dropdown boxes
  ** @author martynas / martynas@xxl101.lt / 2001.06.05
  **/
  function dateInput ($date, $name, $from='', $to='', $params=array(), 
                      $append='', $blank=FALSE) {
    $return = array();
    
    if (empty($date))
      $date = time();
    
    if (!empty($params['year_from']))
      $from = $params['year_from'];
    
    if (empty($from))
      $from = 1930;
    
    if (!empty($params['year_to']))
      $to = $params['year_to'];
    
    if (empty($to))
      $to = date('Y', time());
    
    if (empty($append)) {
      $append = array('_y', '_m', '_d');
    }
    
    // find out year, month and day values
    if (is_int($date)) {
      // $date is in UNIX timestamp format
      $y = date('Y', $date);
      $m = date('n', $date);
      $d = date('j', $date);
    }
    else {
      // $date is in MySQL date format
      preg_match('/([0-9]*)-([0-9]*)-([0-9]*)/i', $date, $arr);
      $y = $arr[1];
      $m = $arr[2];
      $d = $arr[3];
    }
    
    // generate year dropdown
    $opt = array();
    if ($blank) {
      $opt['[E]'] = ' ';
    }
    for ($c=$to; $c>=$from; $c--) {
      $opt[$c] = $c;
    }
    $prm = $params;
    $prm['options'] = $opt;
    $return[$name.$append[0]] = $this->getInput(FORM_INPUT_SELECT, $name.$append[0], '', $prm, $y);
    
    // generate month dropdown
    $opt = array();
    if ($blank) {
      $opt['[E]'] = ' ';
    }
    for ($c=1; $c<=12; $c++) {
      $opt[$c] = $c;
    }
    $prm = $params;
    $prm['options'] = $opt;
    $return[$name.$append[1]] = $this->getInput(FORM_INPUT_SELECT, $name.$append[1], '', $prm, $m);
    
    // generate day dropdown
    $opt = array();
    if ($blank) {
      $opt['[E]'] = ' ';
    }
    for ($c=1; $c<=31; $c++) {
      $opt[$c] = $c;
    }
    $prm = $params;
    $prm['options'] = $opt;
    $return[$name.$append[2]] = $this->getInput(FORM_INPUT_SELECT, $name.$append[2], '', $prm, $d);
    
    return $return;
  }
  // }}}
  // {{{ timeInput()
  
  /**
  ** Generate two dorpdown boxes for time (hours and minutes)
  **
  ** @param $date selected date in UNIX timestamp or commond format (YYYY-MM-DD)
  ** @param $name name of the field
  ** @param $class stylesheet class to be used
  ** @access public
  ** @return array with two formated dropdown boxes
  ** @author martynas / martynas@xxl101.lt / 2001.06.05
  **/
  function timeInput ($date, $name, $params=array(), $append='', $blank=FALSE) {
    $return = array();
    
    if (empty($date))
      $date = time();
    
    if (empty($append)) {
      $append = array('_h', '_i');
    }
    
    // find out hour and minute values
    if (is_int($date)) {
      // $date is in UNIX timestamp format
      $h = date('G', $date);
      $m = date('i', $date);
    }
    else {
      // $date is in MySQL date format
      preg_match('/([0-9]{1,2}):([0-9]{1,2})/', $date, $arr);
      $h = $arr[1];
      $m = $arr[2];
    }
    
    // generate hour dropdown
    $opt = array();
    if ($blank) {
      $opt['[E]'] = ' ';
    }
    for ($c = 0; $c < 24; $c++) {
      $opt[$c] = $c;
    }
    $prm = $params;
    $prm['options'] = $opt;
    $return[$name.$append[0]] = $this->getInput(FORM_INPUT_SELECT, $name.$append[0], '', $prm, $h);
    
    // generate minute dropdown
    $opt = array();
    if ($blank) {
      $opt['[E]'] = ' ';
    }
    for ($c = 0; $c < 60; $c++) {
      $opt[$c] = $c;
    }
    $prm = $params;
    $prm['options'] = $opt;
    $return[$name.$append[1]] = $this->getInput(FORM_INPUT_SELECT, $name.$append[1], '', $prm, $m);
    
    return $return;
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
  // {{{ safeEncode()
  
  /**
  ** Encode values to be safely included
  **
  ** @access public
  ** @return bool TRUE if there was an error, FALSE if no
  ** @author martynas / martynas@xxl101.lt / 2001.01.18
  **/
    
  function safeEncode ($str) {
    $str = str_replace('"', '&quot;', $str);
    $str = str_replace('>', '&gt;', $str);
    $str = str_replace('<', '&lt;', $str);
    return $str;
  }
  
  // }}}

}
?>