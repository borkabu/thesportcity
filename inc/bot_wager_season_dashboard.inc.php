
</div>
<div style="width:200px;float:left;">
<?php 
 echo $shortcutsbox->getShortcutsBox();
 if (isset($wagerbox) && isset($wager_user) && $wager_user->inited) {
    $wagerbox = new WagerBox($langs, $_SESSION['_lang']);
    echo $wagerbox->getWagerSummaryBox($auth, true);
 } 
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