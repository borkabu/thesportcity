
</div>
<div style="width:200px;float:left;">
<?php 
  if ($auth->userOn()) {
    echo $supporter = $supporterbox->getSupporterBox();
  }
 echo $shortcutsbox->getShortcutsBox();
 echo $forumbox->getTopicBox();  
 if (defined('FORUM') && !isset($_GET['topic_id']))
   echo $forumbox->getForumBox();
 echo $managerbox->getManagerSeasonBox(false, '', $manager->mseason_id);
 if ($auth->userOn() && isset($manager_tournamentbox))
   echo $manager_tournamentbox->getManagerTournamentSummaryBox();

 echo $managerbox->getTourSchedule();
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