<?php
// 
// ============================================================================
// db.class.php                                           revision: 20010823-01
// ----------------------------------------------------------------------------
// Database abstraction class.
// 
// GLOBAL VARIABLES USED:
//            $conf_db_type
//            $conf_db_server
//            $conf_db_user
//            $conf_db_password
//            $conf_db_dbase
// 
// WARNING:   -
// 
// STATUS:    development in progress
// 
// TASK LIST: + implement main class
//            + dummy methods
//            + persistent connection support
//            - subselect handling
//            * error constants
//            - error messages
//            ? db field type definition
//            - table alteration methods
//            - native DB function mapping
//            + db close and recordset deletion methods
//            + date/time formatting functions
//            - date/time comparisson formatting functions
//            - transaction support (commit/rollback)
//            + insert methods
//            + update methods
//            + QueryBuilder methods
//            + javadoc documentation
//            ( - not started yet, * in progress, + done, ? uner question )
// 
// BUGS:      -
// 
// ----------------------------------------------------------------------------
// Authors:   Martynas Majeris <martynas@xxl101.lt>
// ============================================================================
// 

include_once($conf_home_dir.'class/db_'.$conf_db_type.'.class.php'); // include appropriate db type file

/**
 * main db class
 */

class db {
	// {{{ properties
  
  // database info
  var $type     = 'mysql'; // database type
  var $server   = 'localhost'; // host of the server
  var $user     = 'sportotiltas1'; // username
  var $password = 'slisdfo3242'; // password
  var $dbase    = 'krepsinis'; // database
  
  // connection state variables
  var $conn = FALSE;
  var $timerstart;
  var $timerstop;
  
  // recordset creation variables
  var $query = array();
  
  // error reporting variables
  var $err             = FALSE;
  var $err_native      = FALSE;
  var $err_native_text = '';
  
  // result variables
  var $rs            = array();
  var $num_rows      = array();
  var $num_fields    = array();
  var $affected_rows = array();
  var $insert_id     = array();
  var $fields        = array(); // ???
  
  // state variables
  var $row    = array();
  var $currow = array();
  
  // page manipulation variables
  var $curpage = array();
  var $perpage = array();
	
	var $showquery = FALSE; // added by saulius for testing purposes
	
	// }}}
  // {{{ class constructor
  
  /**
  ** Constructor
  ** Sets settings from config
  ** @access public
  ** @author martynas / martynas@xxl101.lt / 2001.08.23
  **/
  function db () {
    global $conf_db_type;
    global $conf_db_server;
    global $conf_db_user;
    global $conf_db_password;
    global $conf_db_dbase;
    
    if (!empty($conf_db_type))
      $this->type = $conf_db_type;

    $server = 0;// rand(0, 1);
    
    if (!empty($conf_db_server[$server]))
      $this->server = $conf_db_server[$server];
    
    if (!empty($conf_db_user))
      $this->user = $conf_db_user;
    
    if (!empty($conf_db_password))
      $this->password = $conf_db_password;
    
    if (!empty($conf_db_dbase))
      $this->dbase = $conf_db_dbase;
  }
  
  // }}}
  
  // ======== DATABASE CONNECTION MANIPULATION =================================
  // {{{ connect
  
  /**
  ** Connect to database server
  ** 
  ** @param $dbase database to connect to (optional)
  ** @param $server host of the db server (optional)
  ** @param $user username (optional)
  ** @param $password password (optional)
  ** @param $persistent whether to use persistent connection or not (optional)
  ** @access public
  ** @return bool TRUE on succeseful connect, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function connect ($dbase='', $server='', $user='', 
                    $password='', $persistent=FALSE) {
    // evaluate parameters
    if (!($dbase = $this->evalDbase($dbase)) || !$this->setDbase($dbase)) {
      // Error: no database specified
      $this->setError(ERR_DB_DBASE);
      return FALSE;
    }
    
    if (!($server = $this->evalServer($server)) || !$this->setServer($server)) {
      // Error: no server host specified
      $this->setError(ERR_DB_SERVER);
      return FALSE;
    }
    
    if (!($user = $this->evalUser($user)) || !$this->setUser($user)) {
      // Error: no username specified
      $this->setError(ERR_DB_USER);
      return FALSE;
    }
    
    // password can be zero-lenght
    $password = $this->evalPassword($password);
    if (!$this->setPassword($password)) {
      $this->setError(ERR_DB_PASSWORD);
      return FALSE;
    }
    
    if ($persistent && !$this->isPersistent()) {
      // persistent connection is not allowed
      $this->setError(ERR_DB_PCONNECT);
      return FALSE;
    }
    
    if ($persistent)
      $conn = $this->dbPConnect($server, $user, $password);
    else
      $conn = $this->dbConnect($server, $user, $password);
    
    if ($conn === FALSE) {
      // error connecting to database
      return FALSE;
    }
    
    // set connection
    $this->conn = $conn;
    
    // set database
    if (!$this->dbSetDb($dbase)) {
      // error setting the database
      return FALSE;
    }
    
    // return with a success (TRUE)
    return TRUE;
	}
  
  // }}}
  // {{{ close
  
  /**
  ** Close connection to server
  ** 
  ** @access public
  ** @return bool TRUE on succeseful connect, FALSE on error
  **/
  
	function close () {
    return $this->dbClose();
	}
  
  // }}}
  // {{{ pconnect
  
  /**
  ** Attempt to make a persistent connection to database server
  ** This is just a wrapper function which calls connect function with the
  ** persistent parameter set to TRUE.
  ** @param $dbase database to connect to (optional)
  ** @param $server host of the db server (optional)
  ** @param $user username (optional)
  ** @param $password password (optional)
  ** @access public
  ** @return bool TRUE on succeseful connect, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function pconnect ($dbase='', $server='', $user='', $password='') {
    return $this->connect($dbase, $server, $user, $password, TRUE);
	}
  
  // }}}
  // {{{ setDbase
  
  /**
  ** Set database
  ** 
  ** @param $dbase database
  ** @access public
  ** @return bool always TRUE
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function setDbase ($dbase) {
    $this->dbase = $dbase;
    return TRUE;
  }
  
  // }}}
  // {{{ setServer
  
  /**
  ** Set server
  ** 
  ** @param $server server
  ** @access public
  ** @return bool always TRUE
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function setServer ($server) {
    $this->server = $server;
    return TRUE;
  }
  
  // }}}
  // {{{ setUser
  
  /**
  ** Set username
  ** 
  ** @param $user username
  ** @access public
  ** @return bool always TRUE
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function setUser ($user) {
    $this->user = $user;
    return TRUE;
  }
  
  // }}}
  // {{{ setPassword
  
  /**
  ** Set password
  ** 
  ** @param $password password
  ** @access public
  ** @return bool always TRUE
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function setPassword ($password) {
    $this->password = $password;
    return TRUE;
  }
  
  // }}}
  // ===========================================================================
  
  // ======== RECORDSET/QUERY MANIPULATION =====================================
  // {{{ query
  
  /**
  ** Execute database query
  ** 
  ** @param $query SQL query
  ** @param $inst query instance (optional)
  ** @access public
  ** @return bool TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
  function query ($query, $inst='') {
    $inst = $this->getInstance($inst);
    
    // remove paging
    $this->setPage();
		
		if ($this->showquery === TRUE)	{	// added by saulius for testing purposes
			echo '<!-- '.$query.' -->';									 	// added by saulius for testing purposes
//                   mail("borka@tdd.lt", "sql debug", $query, "From: admin@krepsinis.net");
                }
		
    // execute query
    if ($rs = $this->dbQuery($query)) {
      // sucsessefuly executed query
      $this->rs[$inst] = $rs;
      if (preg_match("/^select/i", $query)) {
        // SELECT statement. Gather number of rows and fields
        $this->num_rows[$inst] = $this->dbNumRows($rs);
        $this->num_fields[$inst] = $this->dbNumFields($rs);
      }
      if (preg_match("/^insert/i", $query)) {
        // INSERT. Get ID of the last inserted row in autonumber field
        $this->insert_id[$inst] = $this->dbInsertId();
      }
      if (preg_match("/^insert/i", $query)
          || preg_match("/^update/i", $query)
          || preg_match("/^delete/i", $query)) {
        // various update queries. Get number of affected rows
        $this->affected_rows[$inst] = $this->dbAffectedRows();
        $this->rs[$inst] = FALSE;
      }
      if ($this->curpage[$inst] > 0 && $this->perpage[$inst] > 0) {
        // move to a specific page
        $row = ($this->curpage[$inst]-1) * $this->perpage[$inst];
        $this->jump($row, $inst);
      }
      return TRUE;
    }
    else {
      // error in query
      return FALSE;
    }
	}
  
  // }}}
  // {{{ free
  
  /**
  ** Jump to first row in resultset
  ** This is a wrapper function for jump method
  ** @param $inst query instance (optional)
  ** @access public
  ** @return bool TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function free ($inst='') {
    $inst = $this->getInstance($inst);    
    if (isset($this->rs[$inst]) && $this->rs[$inst] > 0) {
      $res=$this->rs[$inst];
      // resultset exists for this instance
      unset($this->rs[$inst]);
      unset($this->num_rows[$inst]);
      unset($this->num_fields[$inst]);
      unset($this->row[$inst]);
      unset($this->currow[$inst]);
      unset($this->curpage[$inst]);
      unset($this->perpage[$inst]);
      return $this->dbFree($res);
		}
		else {
      // no resultset exist for this instance
			return FALSE;
		}
	}
  
  // }}}
  // {{{ nextRow
  
  /**
  ** Get next row in a recordset
  ** 
  ** @param $inst query instance (optional)
  ** @access public
  ** @return array data row, FALSE on EOF on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
  function nextRow ($inst='') {
    $inst = $this->getInstance($inst);
    if (isset($this->rs[$inst]) && $this->rs[$inst] > 0) {
      // resultset exists for this instance
	if ($this->perpage[$inst] == 0
          || $this->currow[$inst] < $this->perpage[$inst] * $this->curpage[$inst]) {
		if (isset($this->currow[$inst]))
   		  $this->currow[$inst]++;
		$this->row[$inst] = $this->dbNextRow($this->rs[$inst]);
		$this->row[$inst] = str_replace('\\', '', $this->row[$inst]);
		return $this->row[$inst];
	}
	else {
        // end of page reached
	     return FALSE;
      	}
    }
    else {
      // no resultset exist for this instance
      $this->setError(ERR_DB_RS_MISSING);
		return FALSE;
    }
  }
  
  // }}}
  // {{{ jump
  
  /**
  ** Jump to a particular row in resultset/page
  ** 
  ** @param $row which row to jump to (first is 0)
  ** @param $inst query instance (optional)
  ** @access public
  ** @return bool TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function jump($row=0, $inst='') {
    $inst = $this->getInstance($inst);
		if ($this->rs[$inst] > 0) {
      // resultset exists for this instance
      if ($this->dbJump($this->rs[$inst], $row)) {
        $this->currow[$inst] = $row;
        return TRUE;
      }
      else {
        // this database does not support jump functionality
        return FALSE;
      }
		}
		else {
      // no resultset exist for this instance
      $this->setError(ERR_DB_RS_MISSING);
			return FALSE;
		}
	}
  
  // }}}
  // {{{ jumpFirst
  
  /**
  ** Jump to first row in resultset
  ** This is a wrapper function for jump method
  ** @param $inst query instance (optional)
  ** @access public
  ** @return bool TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function jumpFirst($inst='') {
    $inst = $this->getInstance($inst);
    return $this->jump(0, $inst);
	}
  
  // }}}
  // {{{ jumpLast
  
  /**
  ** Jump to last row in resultset
  ** This is a wrapper function for jump method
  ** @param $inst query instance (optional)
  ** @access public
  ** @return bool TRUE on success, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function jumpLast($inst='') {
    $inst = $this->getInstance($inst);
    if ($this->num_rows[$inst] > 0) {
      return $this->jump($this->num_rows[$inst]-1, $inst);
    }
    else {
      return $this->jump(0, $inst);
    }
	}
  
  // }}}
  // {{{ setPage
  
  /**
  ** Set paging options
  ** If called w/o parameters or with parameters equal to zero paging is turned 
  ** off and internal pointer is set to the first record of the recordset (if
  ** present). First page is 1.
  ** @param $curpage current page number (optional)
  ** @param $perpage number of records per page (optional)
  ** @param $inst query instance (optional)
  ** @access public
  ** @return bool always TRUE
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
  function setPage ($curpage=0, $perpage=0, $inst='') {
    $inst = $this->getInstance($inst);
    if ($curpage > 0 && $perpage > 0) {
	$this->curpage[$inst] = $curpage;
	$this->perpage[$inst] = $perpage;
	if (isset($this->rs[$inst]) && $this->rs[$inst] > 0) {
           $row = ($this->curpage[$inst]-1) * $this->perpage[$inst];
           $this->jump($row, $inst);
        }
     }
     else {
	$this->curpage[$inst] = 0;
	$this->perpage[$inst] = 0;
	if (isset($this->rs[$inst]) && $this->rs[$inst] > 0)
	        $this->jump(0, $inst);
     }
  }
  
  // }}}
  // ===========================================================================
  
  // ======== PUBLIC SUPPORT FUNCTIONS =========================================
  // {{{ conn
  
  /**
  ** Return database connection id
  ** 
  ** @access public
  ** @return int db connection id
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function conn () {
    return $this->conn;
  }
  
  // }}}
  // {{{ runQueryBuilder
  
  /**
  ** Executes a QueryBuilder
  ** QueryBuilder builds query from a set of parameters and executes it using
  ** query method.
  ** @param $type type of the operation (DB_QUERY_SELECT, DB_QUERY_INSERT, etc.)
  ** @param $tables array or string of tables
  ** @param $fields array or string of fields
  ** @param $values array of values (key - field name, value - value (value 
  **                should be enclosed in singlequotes for string values))
  ** @param $where array or string for WHERE conditions. Different trees will be
  **               anclosed in parenthesis. Every statement, except first in a
  **               tree has to be preceded with AND/OR operator.
  ** @param $order array or string of ORDER BY options
  ** @param $add string to add to query string to (optional)
  ** @param $inst instance of the query (optional)
  ** @access public
  ** @return bool TRUE on successeful query execution, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function runQueryBuilder ($type, $tables,
                            $fields, $values,
                            $where, $order,
                            $add='', $inst='') {
    $inst = $this->getInstance($inst);
    $query = '';
    $tables = $this->buildList($tables);
    $fields = $this->buildList($fields);
    $where = $this->buildWhere($where);
    $order = $this->buildList($order);
    switch ($type) {
      case DB_QUERY_SELECT:
        // SELECT
        $query .= 'SELECT ';
        $query .= $fields;
        $query .= ' FROM ';
        $query .= $tables;
        if (!empty($where))
          $query .= ' WHERE ' . $where;
        if (!empty($order))
          $query .= ' ORDER BY ' . $order;
//        echo $query.'<br>';
        break;
        
      case DB_QUERY_INSERT:
        // INSERT
        $query .= 'INSERT INTO ';
        $query .= $tables;
        $query .= ' (' . $this->buildKeys($values) . ') ';
        $query .= ' VALUES ';
        $query .= ' (' . $this->buildList($values) . ') ';
//      echo $query;
        break;
        
      case DB_QUERY_UPDATE:
        // UPDATE
        $query .= 'UPDATE ';
        $query .= $tables;
        $query .= ' SET ';
        $query .= $this->buildKeyList($values);
        if (!empty($where))
          $query .= ' WHERE ' . $where;
//      echo $query;
        break;

      case DB_QUERY_REPLACE:
        // UPDATE
        $query .= 'REPLACE ';
        $query .= $tables;
        $query .= ' SET ';
        $query .= $this->buildKeyList($values);
        if (!empty($where))
          $query .= ' WHERE ' . $where;
//      echo $query;
        break;
       
      case DB_QUERY_DELETE:
        // SELECT
        $query .= 'DELETE FROM ';
        $query .= $tables;
        if (!empty($where))
          $query .= ' WHERE ' . $where;
//        echo $query;
        break;
    }
    
    $query .= $add;
		
//		echo $query.'<br><br>';
		
    return $this->query($query);
	}
  
  // }}}
  // {{{ select
  
  /**
  ** Execute a SELECT query using QueryBuilder
  ** Wrapper function for runQueryBuilder
  ** @param $tables array or string of tables
  ** @param $fields array or string of fields
  ** @param $where array or string for WHERE conditions. Different trees will be
  **               anclosed in parenthesis. Every statement, except first in a
  **               tree has to be preceded with AND/OR operator.
  ** @param $order array or string of ORDER BY options
  ** @param $add string to add to query string to (optional)
  ** @param $inst instance of the query (optional)
  ** @access public
  ** @return bool TRUE on successeful query execution, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
   function select ($tables, $fields, $where='', $order='', $add='', $inst='') {
     return $this->runQueryBuilder(DB_QUERY_SELECT, $tables, 
                                  $fields, '',
                                  $where, $order,
                                  $add, $inst);
   }
  
  // }}}
  // {{{ insert
  
  /**
  ** Execute a INSERT query using QueryBuilder
  ** Wrapper function for runQueryBuilder
  ** @param $tables array or string of tables
  ** @param $values array of values (key - field name, value - value (value 
  **                should be enclosed in singlequotes for string values))
  ** @param $add string to add to query string to (optional)
  ** @param $inst instance of the query (optional)
  ** @access public
  ** @return bool TRUE on successeful query execution, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function insert ($tables, $values, $add='', $inst='') {
    return $this->runQueryBuilder(DB_QUERY_INSERT, $tables, 
                                  '', $values,
                                  '', '',
                                  $add, $inst);
	}
  
  // }}}
  // {{{ update
  
  /**
  ** Execute a UPDATE query using QueryBuilder
  ** Wrapper function for runQueryBuilder
  ** @param $tables array or string of tables
  ** @param $values array of values (key - field name, value - value (value 
  **                should be enclosed in singlequotes for string values))
  ** @param $where array or string for WHERE conditions. Different trees will be
  **               anclosed in parenthesis. Every statement, except first in a
  **               tree has to be preceded with AND/OR operator.
  ** @param $add string to add to query string to (optional)
  ** @param $inst instance of the query (optional)
  ** @access public
  ** @return bool TRUE on successeful query execution, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function update ($tables, $values, $where='', $add='', $inst='') {
    return $this->runQueryBuilder(DB_QUERY_UPDATE, $tables, 
                                  '', $values,
                                  $where, '',
                                  $add, $inst);
	}


	function replace ($tables, $values, $where='', $add='', $inst='') {
    return $this->runQueryBuilder(DB_QUERY_REPLACE, $tables, 
                                  '', $values,
                                  $where, '',
                                  $add, $inst);
	}
  
  // }}}
  // {{{ delete
  
  /**
  ** Execute a SELECT query using QueryBuilder
  ** Wrapper function for runQueryBuilder
  ** @param $tables array or string of tables
  ** @param $where array or string for WHERE conditions. Different trees will be
  **               anclosed in parenthesis. Every statement, except first in a
  **               tree has to be preceded with AND/OR operator.
  ** @param $add string to add to query string to (optional)
  ** @param $inst instance of the query (optional)
  ** @access public
  ** @return bool TRUE on successeful query execution, FALSE on error
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function delete ($tables, $where='', $add='', $inst='') {
    return $this->runQueryBuilder(DB_QUERY_DELETE, $tables, 
                                  '', '',
                                  $where, '',
                                  $add, $inst);
	}
  
  // }}}
  // {{{ rows
  
  /**
  ** Return number of rows in resultset
  ** 
  ** @param $inst query instance (optional)
  ** @access public
  ** @return int number of rows in a resultset, FALSE if such resultset does not
  ** exist or query was of other type than SELECT
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function rows ($inst='') {
    $inst = $this->getInstance($inst);
    if (isset($this->num_rows[$inst]))
      return $this->num_rows[$inst];
    else
      return FALSE;
	}
  
  // }}}
  // {{{ current
  
  /**
  ** Return current row number in resultset
  ** 
  ** @param $inst query instance (optional)
  ** @access public
  ** @return int current row number, FALSE if such resultset does not
  ** exist or query was of other type than SELECT
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function current ($inst='') {
    $inst = $this->getInstance($inst);
    if (isset($this->currow[$inst]))
      return $this->currow[$inst];
    else
      return FALSE;
	}
  
  // }}}
  // {{{ eof
  
  /**
  ** Indicate wether the end of resultset has been reached
  ** 
  ** @param $inst query instance (optional)
  ** @access public
  ** @return bool TRUE if yes, FALSE on not
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function eof ($inst='') {
    $inst = $this->getInstance($inst);
    if ($this->num_rows[$inst] == $this->currow[$inst])
      return TRUE;
    else
      return FALSE;
	}
  
  // }}}
  // {{{ fields
  
  /**
  ** Return number of fields in resultset
  ** 
  ** @param $inst query instance (optional)
  ** @access public
  ** @return int number of fields in a resultset, FALSE if such resultset does not
  ** exist or query was of other type than SELECT
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function fields ($inst='') {
    $inst = $this->getInstance($inst);
    if (isset($this->num_fields[$inst]))
      return $this->num_fields[$inst];
    else
      return FALSE;
	}
  
  // }}}
  // {{{ affected
  
  /**
  ** Return number of affected rows in last UPDATE statement
  ** 
  ** @param $inst query instance (optional)
  ** @access public
  ** @return int number of affected rows, FALSE if such resultset does not
  ** exist or query was of other type than UPDATE
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function affected ($inst='') {
    $inst = $this->getInstance($inst);
    if (isset($this->affected_rows[$inst]))
      return $this->affected_rows[$inst];
    else
      return FALSE;
	}
  
  // }}}
  // {{{ id
  
  /**
  ** Return the ID of the record generated in the last INSERT statement
  ** 
  ** @param $inst query instance (optional)
  ** @access public
  ** @return int ID of the last INSERT statement, FALSE if such resultset does not
  ** exist or query was of other type than INSERT
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  
	function id ($inst='') {
    $inst = $this->getInstance($inst);
    if (isset($this->insert_id[$inst]))
      return $this->insert_id[$inst];
    else
      return FALSE;
	}
  
  // }}}
  // {{{ getNativeError
  
  /**
  ** Get native db error code
  ** 
  ** @access public
  ** @return int native db error code
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function getNativeError () {
    return $this->err_native;
  }
  
  // }}}
  // {{{ getNativeErrorText
  
  /**
  ** Get text message of native db error
  ** 
  ** @access public
  ** @return string native db error message
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function getNativeErrorText () {
    return $this->err_native_text;
  }
  
  // }}}
  // {{{ formatDate
  
  /**
  ** Format date
  ** 
  ** @param $y year or UNIX timestamp
  ** @param $m month (optional if using UNIX timestamp)
  ** @param $d day (optional if using UNIX timestamp)
  ** @access public
  ** @return string native db error message
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function formatDate ($y, $m=0, $d=0) {
    if ($m > 0 && $d > 0) {
      // convert date to UNIX timestamp
      $y = mktime(0, 0, 1, $m, $d, $y);
    }
    return $this->dbFormatDate($y);
  }
  
  // }}}
  // {{{ formatDateTime
  
  /**
  ** Format date and time
  ** 
  ** @param $y year or UNIX timestamp
  ** @param $m month (optional if using UNIX timestamp)
  ** @param $d day (optional if using UNIX timestamp)
  ** @param $h hour (optional if using UNIX timestamp)
  ** @param $n minute (optional if using UNIX timestamp)
  ** @param $s second (optional if using UNIX timestamp)
  ** @access public
  ** @return string native db error message
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function formatDateTime ($y, $m=0, $d=0, $h=0, $n=0, $s=0) {
    if ($m > 0 && $d > 0) {
      // convert date to UNIX timestamp
      $y = mktime($h, $n, $s, $m, $d, $y);
    }
    return $this->dbFormatDateTime($y);
  }
  
  // }}}
  // {{{ formatTime
  
  /**
  ** Format time
  ** 
  ** @param $h hour or UNIX timestamp
  ** @param $n minute (optional if using UNIX timestamp)
  ** @param $s second (optional if using UNIX timestamp)
  ** @access public
  ** @return string native db error message
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function formatTime ($h, $n=0, $s=0) {
    if ($n > 0 && $s > 0) {
      // convert time to UNIX timestamp
      $h = mktime($h, $n, $s, 12, 31, 2000);
    }
    return $this->dbFormattimeTime($h);
  }
  
  // }}}
  // {{{ getDateV
  
  /**
  ** Get year, month, day values from the database 'date' field
  ** 
  ** @param $date date in database format
  ** @access public
  ** @return array populated with year, month and day values
  ** @author saulius / saulius@xxl101.lt / 2001.05.05
  **/
  function getDateV ($date) {
    return $this->dbGetDateV($date);
  }
  
  // }}}
  // ===========================================================================
  
  // ======== PRIVATE SUPPORT FUNCTIONS ========================================
  // {{{ getInstance
  
  /**
  ** Get instance name of the query
  ** If $inst is empty returns 'default'
  ** @param $inst query instance
  ** @access private
  ** @return string instance name
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function getInstance ($inst='') {
    if (empty($inst))
      return 'default';
    else
      return strtolower($inst);
  }
  
  // }}}
  // {{{ setError
  
  /**
  ** Set the error
  ** If no errorcode specified it assumes general db error (ERR_DB)
  ** @param $err error code
  ** @param $err_native db native error code
  ** @param $err_native_text native error text message
  ** @access private
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function setError ($err=0, $err_native=0, $err_native_text='') {
    // set error code
    if ($err > 0)
      $this->err = $err;
    else
      $this->err = ERR_DB;
    
    // set native db error code
    if ($err_native > 0)
      $this->err_native = $err_native;
    
    // set native db error text
    if (!empty($err_native_text))
      $this->err_native_text = $err_native_text;
  }
  
  // }}}
  // {{{ setNativeError
  
  /**
  ** Set native db error code
  ** 
  ** @param $err_native db native error code
  ** @access private
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function setNativeError ($err_native=0) {
    $this->err_native = $err_native;
  }
  
  // }}}
  // {{{ setNativeErrorText
  
  /**
  ** Set text message of native db error
  ** 
  ** @param $err_native_text db native error text
  ** @access private
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function setNativeErrorText ($err_native_text='') {
    $this->err_native_text = $err_native_text;
  }
  
  // }}}
  // {{{ evalDbase
  
  /**
  ** Evaluate given database name
  ** If no database name supplied, check whether class-scope variable was set.
  ** Return database name, or FALSE if no database name was set.
  ** @param $dbase database
  ** @access private
  ** @return string database name, FALSE if nothing specified
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function evalDbase ($dbase='') {
    if (empty($dbase))
      $dbase = $this->dbase;
    
    if (empty($dbase))
      return FALSE;
    else
      return $dbase;
  }
  
  // }}}
  // {{{ evalServer
  
  /**
  ** Evaluate given server host
  ** If no server host supplied, check whether class-scope variable was set.
  ** Return server host, or FALSE if no server host was set.
  ** @param $server server host
  ** @access private
  ** @return string server host, FALSE if nothing specified
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function evalServer ($server='') {
    if (empty($server))
      $server = $this->server;
    
    if (empty($server))
      return FALSE;
    else
      return $server;
  }
  
  // }}}
  // {{{ evalUser
  
  /**
  ** Evaluate given username
  ** If no username supplied, check whether class-scope variable was set.
  ** Return username, or FALSE if no username was set.
  ** @param $user username
  ** @access private
  ** @return string username, FALSE if nothing specified
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function evalUser ($user='') {
    if (empty($user))
      $user = $this->user;
    
    if (empty($user))
      return FALSE;
    else
      return $user;
  }
  
  // }}}
  // {{{ evalPassword
  
  /**
  ** Evaluate given password
  ** If no password supplied, check whether class-scope variable was set.
  ** Return password, or FALSE if no password was set.
  ** @param $password password
  ** @access private
  ** @return string password, FALSE if nothing specified
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function evalPassword ($password='') {
    if (empty($password))
      $password = $this->password;
    
    if (empty($password))
      return FALSE;
    else
      return $password;
  }
  
  // }}}
  // {{{ buildList
  
  /**
  ** Build a separated list of values
  ** 
  ** @param $list array or string of values
  ** @param $sep separator character or string
  ** @access private
  ** @return string list
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function buildList ($list, $sep=', ') {
    $return = '';
    $first = '';
    if (is_array($list)) {
      while (list($key, $val) = each($list)) {
        $return .= $first . $val;
        $first = $sep;
      }
    }
    else {
      $return = $list;
    }
    return $return;
  }
  
  // }}}
  // {{{ buildKeys
  
  /**
  ** Build a separated list of array keys
  ** 
  ** @param $list array or string of values
  ** @param $sep separator character or string
  ** @access private
  ** @return string list
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function buildKeys ($list, $sep=', ') {
    $return = '';
    $first = '';
    if (is_array($list)) {
      while (list($key, $val) = each($list)) {
        $return .= $first . $key;
        $first = $sep;
      }
    }
    else {
      $return = $list;
    }
    return $return;
  }
  
  // }}}
  // {{{ buildKeyList
  
  /**
  ** Build a separated list of array's key=value pairs
  ** 
  ** @param $list array or string of values
  ** @param $sep separator character or string
  ** @access private
  ** @return string list
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function buildKeyList ($list, $sep=', ') {
    $return = '';
    $first = '';
    if (is_array($list)) {
      while (list($key, $val) = each($list)) {
        $return .= "$first$key=$val";
        $first = $sep;
      }
    }
    else {
      $return = $list;
    }
    return $return;
  }
  
  // }}}
  // {{{ buildWhere
  
  /**
  ** Build a nested WHERE clause
  ** 
  ** @param $where array or string where conditions. Array key must be either
  ** AND or OR
  ** @access private
  ** @return string WHERE clause
  ** @author martynas / martynas@xxl101.lt / 2001.03.15
  **/
  function buildWhere ($where) {
    $return = '';
    if (is_array($where)) {
      while (list($key, $val) = each($where)) {
        if (is_array($val)) {
          // subarray condition
          $return .= ' (' . $this->buildWhere($val) . ')';
        }
        else {
          // normal where condition
          $return .= " $val";
        }
        $prefix = TRUE;
      }
    }
    else {
      $return = $where;
    }
    return $return;
  }
  
  // }}}
  // ===========================================================================
  
  // ======== DUMMY FUNCTIONS TO BE OVERLOADED =================================
	function isPersistent () {
    return FALSE;
	}
  
	function dbConnect ($server, $user, $password) {
    $this->setError(ERR_DB_TYPE);
    return FALSE;
	}
  
  function dbClose () {
    $this->setError(ERR_DB_TYPE);
    return FALSE;
	}
  
	function dbPConnect ($server, $user, $password) {
    $this->setError(ERR_DB_TYPE);
    return FALSE;
	}
  
	function dbSetDb ($dbase) {
    $this->setError(ERR_DB_TYPE);
    return FALSE;
	}
  
	function dbQuery ($query) {
    $this->setError(ERR_DB_TYPE);
    return FALSE;
	}
  
  function dbFree ($rs) {
    $this->setError(ERR_DB_TYPE);
    return FALSE;
	}
  
  function dbNumRows ($rs) {
    return -1;
	}
  
  function dbNumFields ($rs) {
    return -1;
	}
  
  function dbAffectedRows ($rs) {
    return -1;
	}
  
  function dbInsertId ($rs) {
    return -1;
	}
  
	function dbNextRow ($rs) {
    return FALSE;
	}
  
	function dbJump ($rs, $row) {
    return FALSE;
	}
  
	function dbNativeError () {
    return FALSE;
	}
  
	function dbNativeErrorText () {
    return FALSE;
	}
  
  function dbFormatDate ($timestamp) {
    return date('Y-m-d', $timestamp);
	}
  
  function dbFormatDateTime ($timestamp) {
    return date('Y-m-d H:i:s', $timestamp);
	}
  
  function dbFormatTime ($timestamp) {
    return date('H:i:s', $timestamp);
	}
  
  function dbSubselect ($query) {
    return $query;
	}
  
  function dbParseNative ($query) {
    return $query;
	}


  // ===========================================================================
}
 ?>