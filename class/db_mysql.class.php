<?php
// 
// ============================================================================
// db_mysql.class.php                                    revision: 20010317-01
// ----------------------------------------------------------------------------
// mysql support file for db.class.php
// 
// GLOBAL VARIABLES USED: -
// 
// WARNING:   -
// 
// STATUS:    usable
// 
// TASK LIST: + isPersistent()
//            + dbConnect()
//            + dbClose()
//            + dbPConnect()
//            + dbSetDb()
//            + dbQuery()
//            + dbFree()
//            + dbNumRows()
//            + dbNumFields()
//            + dbAffectedRows()
//            + dbInsertId()
//            + dbNextRow()
//            + dbJump()
//            + dbNativeError()
//            + dbNativeErrorText()
//            + dbFormatDate()
//            + dbFormatDateTime()
//            + dbFormatTime()
//            + dbSubselect()
//            - dbParseNative()
//            ( - not started yet, * in progress, + done, ? uner question )
// 
// BUGS:      -
// 
// ----------------------------------------------------------------------------
// Authors:   Martynas Majeris <martynas@xxl101.lt>
// ============================================================================
// 

/**
 * db class extends one of the database classes.
 * db type (mysql, pgsql) should be set in $conf_db_type variable
 */
 
class db_mysql extends db {
	// {{{ properties
  
  var $mysql_results;
  var $mysql_rows = 0;
  var $mysql_pos = 0;
  var $mysql_rowid;
  var $enhance;
  
	// }}}
  // {{{ class constructor
  
  /**
  ** Constructor
  ** Sets default db settings
  ** @access public
  ** @author martynas / martynas@xxl101.lt / 2001.03.19
  **/
  function db_mysql () {
    global $conf_db_type;
    global $conf_db_server;
    global $conf_db_user;
    global $conf_db_password;
    global $conf_db_dbase;
    global $conf_db_mysql_enhance;
    
    $this->type     = $conf_db_type;
    $server = 0;//rand(0, 1);
    
    $this->server = $conf_db_server[$server];
//echo $conf_db_server[$server];
//    $this->server   = $conf_db_server;
    $this->user     = $conf_db_user;
    $this->password = $conf_db_password;
    $this->dbase    = $conf_db_dbase;
    
    $this->enhance  = $conf_db_mysql_enhance;
  }
  
  // }}}
  // {{{ isPersistent
  
  /**
  ** Check wether database supports persistent connections
  ** 
  ** @access public
  ** @return bool TRUE if yes, FALSE if no
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function isPersistent () {
    // mysql supports persistent connections
    return TRUE;
	}
  
  // }}}
  // {{{ dbConnect
  
  /**
  ** Connect to database server
  ** 
  ** @param $server SID of the local Oracle service or alias defined in
  **                tnsnames.ora file
  ** @param $user username
  ** @param $password password
  ** @access private
  ** @return int connection id on succeseful connect, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
  function getmicrotime(){ 
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 
  } 

  function dbConnect ($server, $user, $password) {
    if (!$conn = mysql_connect($server, $user, $password)) {
echo "<!--".$server."-->";
      // error connecting to a database
/*      $this->setError(ERR_DB_CONNECT, 
                      $this->dbNativeError(), 
                      $this->dbNativeErrorText());*/
      return FALSE;
     }
     else {
      //echo $conf_db_dbase;
      $this->timerstart = $this->getmicrotime();
      mysql_select_db('sportcity');      
//      mysql_query("SET time_zone = '+6:00'");
//      mysql_set_charset('utf8',$conn); 
      mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'", $conn);
/*      mysql_query("SET NAMES 'utf'", $conn);
      mysql_query("SET CHARACTER SET 'utf8'", $conn);
      mysql_query("SET collation_connection='utf_general_ci'", $conn);
      mysql_query("SET SESSION collation_connection='utf_general_ci'", $conn);*/


      return $conn;
    }
  }
  
  // }}}
  // {{{ dbClose
  
  /**
  ** Close connection
  ** 
  ** @access private
  ** @return TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
  function dbClose () {
     global $_GET;
     global $conf_home_dir;
    if (!mysql_close($this->conn)) {
      // error closing connection
      $this->setError(ERR_DB_CLOSE, 
                      $this->dbNativeError(), 
                      $this->dbNativeErrorText());
      return FALSE;
    }
    else {
      $this->timerstop = $this->getmicrotime();
      if ( $this->timerstop-$this->timerstart > 3) {
/*        $handle = fopen($conf_home_dir."connections", "a");
        fwrite($handle, ($this->timerstop-$this->timerstart)." ".$_SERVER["PATH_TRANSLATED"]."\n");
        fclose($handle);*/
      }
      return TRUE;
    }
//return TRUE;
	}
  
  // }}}
  // {{{ dbPConnect
  
  /**
  ** Make a persistent connection to a database server
  ** 
  ** @param $server SID of the local Oracle service or alias defined in
  **                tnsnames.ora file
  ** @param $user username
  ** @param $password password
  ** @access private
  ** @return int connection id on succeseful connect, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
	function dbPConnect ($server, $user, $password) {
    if (!$conn = mysql_pconnect($server, $user, $password)) {
      // error connecting to a database
      $this->setError(ERR_DB_CONNECT, 
                      $this->dbNativeError(), 
                      $this->dbNativeErrorText());
      return FALSE;
    }
    else {
      return $conn;
    }
	}
  
  // }}}
  // {{{ dbSetDb
  
  /**
  ** Dummy method
  ** Set a database is not aplicable for Oracle.
  ** @param $dbase database
  ** @access private
  ** @return bool always TRUE
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
	function dbSetDb ($dbase='') {
    return TRUE;
	}
  
  // }}}
  // {{{ dbQuery
  
  /**
  ** Execute query
  ** 
  ** @param $query SQL query
  ** @access private
  ** @return int ID of the resultset, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/    
  
 function dbQuery ($query) {
    global $_GET;
    // replace magic quotes with two signle quotes
    global $HTTP_REFERER;
    $query = str_replace("\'", "''", $query);
//    $query = str_replace('\"', '"', $query);
//    echo $query;
    $timerstart = getmicrotime();
    if (!empty($query) && !$rs = mysql_query($query, $this->conn)) {
      // error parsing query
      $this->setError(ERR_DB_QUERY, 
                      $this->dbNativeError(), 
                      $this->dbNativeErrorText());
//       echo $this->err_native_text."<br>";
//      if (strpos($query, "UPDATE"))
//	mail("borka@tdd.lt", "sql error", $_SERVER["PHP_SELF"].$rs.$this->conn.$query.$this->dbNativeErrorText().$HTTP_REFERER, "From: admin@krepsinis.net");
      return FALSE;
    }
    else {
      // query parsed. execute it
     
     if ($this->enhance) {
      echo "enhance";
/*        // enhanced mode specified. fetch all results into array
        $this->mysql_rows = @OCIFetchStatement($rs, $this->oracle_results);
        $this->mysql_pos = 0;
        @OCIFreeStatement($rs);*/
      }
      $timerstop = getmicrotime();
      if (isset($_GET['debug'])) {
        echo ($timerstop-$timerstart)."<br>";
        if ($timerstop-$timerstart > 0.1)
          echo $query;
      }

      return $rs;
    }
  }
  
  // }}}
  // {{{ dbFree
  
  /**
  ** Free resultset
  ** 
  ** @param $rs resultset id
  ** @access private
  ** @return bool TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
	function dbFree ($rs) {
    if ($this->enhance) {
      // clear variables in enhanced mode
      unset($this->mysql_results);
      $this->mysql_rows = 0;
      $this->mysql_pos = 0;
      unset($this->mysql_rowid);
    }
    elseif (!mysql_free_result($rs)) {
      // error freeing resultset
      $this->setError(ERR_DB_FREE, 
                      $this->dbNativeError(), 
                      $this->dbNativeErrorText());
      return FALSE;
    }
    else {
      // seccesefuly freed resultset
      return TRUE;
    }
	}
  
  // }}}
  // {{{ dbNumRows
  
  /**
  ** Return the number of rows in resultset
  ** Not supported by OCI8. Always returns zero (0).
  ** @param $rs resultset id
  ** @access private
  ** @return int number of rows
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
	function dbNumRows ($rs) {
    if ($this->enhance) {
      return $this->mysql_rows;
    }
    else {
      // enhanced mode is turned off. return 0
      return mysql_num_rows($rs);
    }
	}
  
  // }}}
  // {{{ dbNumFields
  
  /**
  ** Return the number of fields in resultset
  ** 
  ** @param $rs resultset id
  ** @access private
  ** @return int number of fields
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
	function dbNumFields ($rs) {
    return mysql_num_fields($rs);
	}
  
  // }}}
  // {{{ dbAffectedRows
  
  /**
  ** Return the number of affected rows by last UPDATE/INSERT/DELETE query
  ** 
  ** @access private
  ** @return int number of affected rows
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
	function dbAffectedRows ($inst='') {
    return mysql_affected_rows($this->conn);
	}
  
  // }}}
  // {{{ dbInsertId
  
  /**
  ** Return ID generated for an autonumber field by last INSERT query
  ** Not supported by OCI8. Always returns zero (0).
  ** @access private
  ** @return int ID generated for an autonumber field
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
	function dbInsertId ($inst='') {
	    return  mysql_insert_id();;
	}
  
  // }}}
  // {{{ dbNextRow
  
  /**
  ** Return an array of next row data
  ** 
  ** @param $rs resultset id
  ** @access private
  ** @return array row data, FALSE on EOF
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
	function dbNextRow ($rs) {
    if ($this->enhance) {
      // use the result array. build data row
      if ($this->mysql_pos < $this->mysql_rows) {
        reset($this->mysql_results);
        while (list($key, $val) = each($this->mysql_results)) {
          $row[$key] = $val[$this->mysql_pos];           
        }
        $this->mysql_pos++;
        return $row;
      }
      else {
        return FALSE;
      }
    }
    else {
      // enhanced mode is turned off
      $row = mysql_fetch_assoc($rs);
      return $row;
    }
	}
  
  // }}}
  // {{{ dbJump
  
  /**
  ** Jump to a specific row in resultset
  ** Not supported by OCI8. Returns FALSE unless oracle enhanced mode is 
  ** specified.
  ** @param $rs resultset id
  ** @param $row row (first 0)
  ** @access private
  ** @return bool TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
	function dbJump ($rs, $row) {
    if ($this->enhance) {
      if ($row <= $this->mysql_rows) {
        $this->mysql_pos = $row;
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    else {
      if ($row > 0)
        return mysql_data_seek($rs, $row);
      else return TRUE; 
    }
	}
  
  // }}}
  // {{{ dbNativeError
  
  /**
  ** Return last error code generated by database
  ** 
  ** @access private
  ** @return int error code
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
   function dbNativeError () {
    $error = mysql_errno($this->conn);
    return $error;
   }
  
  // }}}
  // {{{ dbNativeErrorText
  
  /**
  ** Return last error message generated by database
  ** 
  ** @access private
  ** @return string error code
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
	function dbNativeErrorText () {
    $error = mysql_error($this->conn);
    return $error;
	}
  
  // }}}
  // {{{ dbFormatDate
  
  /**
  ** Return date in relative format
  ** 
  ** @param $timestamp UNIX timestamp
  ** @access private
  ** @return string formated date
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function dbFormatDate ($timestamp) {
    return "'".date('Y-m-d', $timestamp)."'";
	}
  
  // }}}
  // {{{ dbFormatDateTime
  
  /**
  ** Return date and time in relative format
  ** 
  ** @param $timestamp UNIX timestamp
  ** @access private
  ** @return string formated date and time
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function dbFormatDateTime ($timestamp) {
    return "'".date('Y-m-d H:i:s', $timestamp)."'";
	}
  
  // }}}
  // {{{ dbFormatTime
  
  /**
  ** Return time in relative format
  ** 
  ** @param $timestamp UNIX timestamp
  ** @access private
  ** @return string formated time
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function dbFormatTime ($timestamp) {
    return "'".date('H:i:s', $timestamp)."'";
	}
  
  // }}}
  // {{{ dbSubselect
  
  /**
  ** Parse SQL query for subselects
  ** Oracle supports subselectes so no modification to query is necessary
  ** @param $query SQL query
  ** @access private
  ** @return string SQL query
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
	function dbSubselect ($query) {
    return $query;
	}
  
  // }}}
  // {{{ dbParseNative
  
  /**
  ** Parse SQL query for native functions and replace them
  ** 
  ** @param $query SQL query
  ** @access private
  ** @return string SQL query
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function dbParseNative ($query) {
    return $query;
	}
  
  // }}}
}
 ?>