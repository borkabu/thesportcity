<?php

class Category {
 
  function Category() {
  }
   
  function addCategory($category) {
    global $db;
    global $auth;
    global $_SESSION;
    global $langs;
    global $conf_site_url;

      unset($sdata);
      $sdata['CAT_NAME'] = "'".$category."'";
      $sdata['USER_ID'] = "'".$auth->getUserId()."'";
      $sdata['LANG_ID'] = "'".$_SESSION['lang_id']."'";
      $sdata['STATUS'] = 0;
      $sdata['DATE_SUGGESTED'] = "NOW()";
      $actkey = gen_rand_string(0, 10);
      $sdata['ACTKEY'] = "'".$actkey."'";
      $db->insert('cats_suggested', $sdata);
      $cat_id = $db->id();

      $edata['USER_NAME'] = $_SESSION['_user']['USER_NAME'];
      $edata['CATEGORY'] = $category;
      $edata['URL_APPROVE'] = $conf_site_url."user_category_activation.php?mode=cat_approve&cat_id=".$cat_id."&actkey=".$actkey;
      $edata['URL_IGNORE'] = $conf_site_url."user_category_activation.php?mode=cat_ignore&cat_id=".$cat_id."&actkey=".$actkey;
      $edata['URL_DISAPPROVE'] = $conf_site_url."user_category_activation.php?mode=cat_disapprove&cat_id=".$cat_id."&actkey=".$actkey;
      
      $email = new Email($langs, $_SESSION['_lang']);
      $email->getEmailFromTemplate ('email_category_suggest', $edata) ;
      $subject = $langs['LANG_EMAIL_CATEGORY_SUGGEST_LINE_1'];
      if ($email->sendAdmin($subject))
        return true;
      else return false;
    // send email
  }

}

?>