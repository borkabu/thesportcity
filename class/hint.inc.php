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

class Hint {
  
   function Hint() {
   }

   function getHint($hint_type, $hint_level) {
     global $db;
     global $_SESSION;
           
     $hint='';
     $sql = "SELECT HD.DESCR FROM hints H, hints_details HD
		WHERE H.HINT_ID=HD.HINT_ID
			AND H.HINT_TYPE=".$hint_type."
			AND H.HINT_LEVEL IN (-1, ".$hint_level.")
			AND H.PUBLISH='Y'
			AND HD.LANG_ID=".$_SESSION['lang_id']."
		ORDER BY RAND()
		LIMIT 1";
     $db->query($sql);
     if ($row=$db->nextRow()) {
       $hint = $row['DESCR'];
     }

     return $hint;
   }

}
?>