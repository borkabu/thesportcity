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

class Group {
   var $group_id;
  
   function Group($group_id = '') {
     $this->group_id= $group_id;
   }

   function getGroupMembers() {
     global $db;

     $sql="SELECT UA.USER_ID, U.USER_NAME, UA.DATE_JOINED, UA.GROUP_ID, UA.LEVEL
             FROM forum_groups_members UA, users U
             WHERE UA.GROUP_ID = ".$this->group_id."
		   and UA.USER_ID=U.USER_ID
		   and UA.LEVEL=2
            ORDER BY U.USER_NAME ASC";
     $db->query($sql);
     $c = 0;
//echo $sql;
     $groups = '';
     while ($row = $db->nextRow()) {
       $groups['MEMBERS'][$c] = $row;
       $c++; 
     }

     return $groups;
   }

   function getGroupMembersData($moderate = false) {
     global $smarty;
     global $db;
     global $_GET;

     $sql="SELECT UA.USER_ID, U.USER_NAME, UA.DATE_JOINED, UA.GROUP_ID, UA.LEVEL
             FROM forum_groups_members UA, users U
             WHERE UA.GROUP_ID = ".$this->group_id."
		   and UA.USER_ID=U.USER_ID
            ORDER BY U.USER_NAME ASC";
     $db->query($sql);
     $group_members = array();
     while ($row = $db->nextRow()) {
       $group_member = $row;
       if ($row['LEVEL'] == 2)
         $group_member['REMOVE'] = 1;
       $group_members[] = $group_member;
     }

     if (count($group_members) > 0)
       $smarty->assign("group_members", $group_members);

     $start = getmicrotime();
     if ($moderate)
       $template = "bar_group_members_moderate";
     else  
       $template = "bar_group_members";

     $output = $smarty->fetch('smarty_tpl/'.$template.'.smarty');
     $stop = getmicrotime();
     if (isset($_GET['debugphp']))
       echo 'smarty_tpl/'.$template.'.smarty'.($stop-$start);    
     return $output;
   }

   function addNewMember($user_id) {
     global $db;

     $sdata['GROUP_ID'] = $this->group_id;
     $sdata['USER_ID'] = $user_id;
     $sdata['LEVEL'] = 2;
     $sdata['DATE_JOINED'] = 'NOW()';
     $db->insert('forum_groups_members', $sdata);
     unset($sdata);
     $sdata['GROUP_MEMBERS'] = 'GROUP_MEMBERS+1';
     $db->update('forum_groups', $sdata, "GROUP_ID=".$this->group_id);
   }

   function removeMember($user_id) {
     global $db;

     $db->delete('forum_groups_members', 'GROUP_ID='.$this->group_id.' AND USER_ID='.$user_id.' AND LEVEL=2');
     unset($sdata);
     $sdata['GROUP_MEMBERS'] = 'GROUP_MEMBERS-1';
     $db->update('forum_groups', $sdata, "GROUP_ID=".$this->group_id);
   }

   function isGroupModerator($user_id) {
     global $db;

     $sql="SELECT UA.USER_ID, UA.LEVEL
             FROM forum_groups_members UA
             WHERE UA.GROUP_ID = ".$this->group_id."
		   and UA.USER_ID=".$user_id."
		   and UA.LEVEL in (1, 3)";
     $db->query($sql);
     if ($row = $db->nextRow()) {
       return true;
     }

     return false;
   }

   function getGroupPrezident() {
     global $db;

     $sql="SELECT UA.USER_ID, UA.LEVEL
             FROM forum_groups_members UA
             WHERE UA.GROUP_ID = ".$this->group_id."
		   and UA.LEVEL=3";
     $db->query($sql);
     if ($row = $db->nextRow()) {
       return $row['USER_ID'];
     }
     return 0;
   }

   function hasMember($user_id) {
     global $db;

     $sql="SELECT UA.USER_ID
             FROM forum_groups_members UA
             WHERE UA.GROUP_ID = ".$this->group_id."
		   and UA.USER_ID=".$user_id;
     $db->query($sql);
     if ($row = $db->nextRow()) {
       return true;
     }

     return false;
   }

   function hasEventParticipant($user_id, $event_id) {
     global $db;

     $sql="SELECT FGEM.USER_ID
             FROM forum_groups_events_members FGEM, forum_groups_events FGE
             WHERE FGE.GROUP_ID = ".$this->group_id."
		   and FGE.EVENT_ID = ".$event_id."
		   and FGEM.EVENT_ID = FGE.EVENT_ID
		   and FGEM.USER_ID=".$user_id;
     $db->query($sql);
     if ($row = $db->nextRow()) {
       return true;
     }

     return false;
   }

}
?>