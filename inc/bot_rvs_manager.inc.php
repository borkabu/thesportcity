
</div>
<div style="width:200px;float:left;">
<?php 
  echo $countdown = $countdownbox->getCountdownBox();
  if ($auth->userOn()) {
    echo $supporter = $supporterbox->getSupporterBox();
  }
 echo $shortcutsbox->getShortcutsBox();
 echo $forumbox->getTopicBox();  
 if (defined('FORUM') && !isset($_GET['topic_id']))
   echo $forumbox->getForumBox();
 echo $managerbox->getManagerSeasonBox(false, '', $manager->mseason_id);
// if (isset($rvs_manager_user) && isset($rvs_manager_user->inited) && $rvs_manager_user->inited && isset($managerbox)) {
   echo $managerbox->getRvsManagerSummaryBox($auth);
// }

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