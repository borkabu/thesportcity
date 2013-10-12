
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

?>
</div>
<div style="width:200px;float:left;text-align:center;">
<!-- TheSportCity.Net_160x600 -->
<script type='text/javascript'>
GA_googleFillSlot("TheSportCity.Net_160x600");
</script>
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