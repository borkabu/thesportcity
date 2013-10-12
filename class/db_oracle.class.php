<?php
// 
// ============================================================================
// db_oracle.class.php                                    revision: 20010317-01
// ----------------------------------------------------------------------------
// Oracle8 support file for db.class.php
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
 
class db_oracle extends db {
	// {{{ properties
  
  var $oracle_results;
  var $oracle_rows = 0;
  var $oracle_pos = 0;
  var $oracle_rowid;
  var $enhance;
  
	// }}}
  // {{{ class constructor
  
  /**
  ** Constructor
  ** Sets default db settings
  ** @access public
  ** @author martynas / martynas@xxl101.lt / 2001.03.19
  **/
  function db_oracle () {
    global $conf_db_type;
    global $conf_db_server;
    global $conf_db_user;
    global $conf_db_password;
    global $conf_db_dbase;
    global $conf_db_oracle_enhance;
    
    $this->type     = $conf_db_type;
    $this->server   = $conf_db_server;
    $this->user     = $conf_db_user;
    $this->password = $conf_db_password;
    $this->dbase    = $conf_db_dbase;
    
    $this->enhance  = $conf_db_oracle_enhance;
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
    // Oracle supports persistent connections
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
  
	function dbConnect ($server, $user, $password) {
//    echo 'connect - '.$server.'<br>';
    if (!$conn = OCILogon($user, $password, $server)) {
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
  // {{{ dbClose
  
  /**
  ** Close connection
  ** 
  ** @access private
  ** @return TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.17
  **/
  
	function dbClose () {
    if (!@OCILogoff($this->conn)) {
      // error closing connection
      $this->setError(ERR_DB_CLOSE, 
                      $this->dbNativeError(), 
                      $this->dbNativeErrorText());
      return FALSE;
    }
    else {
      return TRUE;
    }
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
    if (!$conn = @OCIPLogon($user, $password, $server)) {
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
    // replace magic quotes with two signle quotes
    $query = str_replace("\'", "''", $query);
    $query = str_replace('\"', '"', $query);
    
    if (!$rs = @OCIParse($this->conn, $query)) {
      // error parsing query
      $this->setError(ERR_DB_QUERY, 
                      $this->dbNativeError(), 
                      $this->dbNativeErrorText());
      return FALSE;
    }
    else {
      // query parsed. execute it
      @OCIExecute($rs);
      
      if ($this->enhance) {
        // enhanced mode specified. fetch all results into array
        $this->oracle_rows = @OCIFetchStatement($rs, $this->oracle_results);
        $this->oracle_pos = 0;
        @OCIFreeStatement($rs);
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
      unset($this->oracle_results);
      $this->oracle_rows = 0;
      $this->oracle_pos = 0;
      unset($this->oracle_rowid);
    }
    elseif (!@OCIFreeStatement($rs)) {
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
      return $this->oracle_rows;
    }
    else {
      // enhanced mode is turned off. return 0
      return 0;
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
    return @OCINumCols($rs);
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
  
	function dbAffectedRows () {
    return @OCIRowCount($this->conn);
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
  
	function dbInsertId () {
    return 0;
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
      if ($this->oracle_pos < $this->oracle_rows) {
        reset($this->oracle_results);
        while (list($key, $val) = each($this->oracle_results)) {
          $row[$key] = $val[$this->oracle_pos];
        }
        $this->oracle_pos++;
        return $row;
      }
      else {
        return FALSE;
      }
    }
    else {
      // enhanced mode is turned off
      @OCIFetchInto($rs, $row, OCI_ASSOC + OCI_RETURN_NULLS + OCI_RETURN_LOBS);
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
      if ($row <= $this->oracle_rows) {
        $this->oracle_pos = $row;
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    else {
      return FALSE;
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
    $error = OCIError($this->conn);
    return $error['code'];
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
    $error = OCIError($this->conn);
    return $error['message'];
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