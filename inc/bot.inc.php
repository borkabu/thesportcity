
</div>
<div style="width:200px;float:left;">
<?php 
 if ($auth->userOn()) {
   echo $supporterbox->getSupporterBox();
 }
 echo $shortcutsbox->getShortcutsBox();
// echo $ssbox->getSSBox($auth);
// echo $bracketbox->getBracketSeasonBox();
// echo $wagerbox->getWagerSeasonBox();
// if (isset($manager_tournamentbox))
//   echo $manager_tournamentbox->getManagerTournamentSeasonBox();
// echo $managerbox->getManagerSeasonBox();
/* if (isset($manager_user) && isset($manager_user->inited) && $manager_user->inited && isset($managerbox)) {
    $managerbox = new ManagerBox($langs, $_SESSION['_lang']);
    echo $managerbox->getManagerSummaryBox($auth);
 } else if (isset($manager_user_small) && isset($managerbox)) {
    $managerbox = new ManagerBox($langs, $_SESSION['_lang']);
    echo $managerbox->getManagerSmallSummaryBox($auth);   
 }*/
// if (defined('CLANS'))
 echo $clanbox->getClanBox();;
 echo $forumbox->getTopicBox();  
 if (defined('FORUM') && !isset($_GET['topic_id']))
   echo $forumbox->getForumBox();
?>
</div>
</div>

<div style="clear:both">
<div class="bottomimage">
<div class="main_container">
<div class="portlet">
  <div class="copyright" style="text-align:center">
     TheSportCity.Net @ Copyright 2009-2011
<?php  $page_end_time=getmicrotime(); 
      echo "| ". (round($page_end_time - $page_start_time, 2)); ?>
  </div>
</div>
</div>
</div>
</div>
</body>
</html>