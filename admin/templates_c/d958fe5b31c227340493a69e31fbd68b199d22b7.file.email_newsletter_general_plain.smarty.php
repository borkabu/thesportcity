<?php /* Smarty version Smarty-3.0.5, created on 2011-11-06 19:09:18
         compiled from "c:\xampp\htdocs\thesportcity\smarty_tpl/email_newsletter_general_plain.smarty" */ ?>
<?php /*%%SmartyHeaderCode:117714eb6db5e9e2525-75444506%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'd958fe5b31c227340493a69e31fbd68b199d22b7' => 
    array (
      0 => 'c:\\xampp\\htdocs\\thesportcity\\smarty_tpl/email_newsletter_general_plain.smarty',
      1 => 1320606557,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '117714eb6db5e9e2525-75444506',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<?php echo $_smarty_tpl->getVariable('data')->value['TITLE'];?>

===============================================
<?php echo get_translation(array('fonema'=>'LANG_HI_U'),$_smarty_tpl);?>
, <?php echo $_smarty_tpl->getVariable('data')->value['USER_NAME'];?>

===============================================
<?php echo $_smarty_tpl->getVariable('data')->value['HEADER'];?>

===============================================
<?php echo get_translation(array('fonema'=>'LANG_NEWSLETTER_HTML_SUPPORT_U'),$_smarty_tpl);?>

http://www.thesportcity.net/user_newsletter.php?id=<?php echo $_smarty_tpl->getVariable('data')->value['QUEUE_ID'];?>

===============================================
<?php echo get_translation(array('fonema'=>'LANG_NEWSLETTER_UNSUBSCRIBE_INSTR_U'),$_smarty_tpl);?>
 <a href="<?php echo $_smarty_tpl->getVariable('data')->value['URL'];?>
"><?php echo get_translation(array('fonema'=>'LANG_NEWSLETTER_UNSUBSCRIBE_U'),$_smarty_tpl);?>
</a>
===============================================
<?php echo get_translation(array('fonema'=>'LANG_EMAIL_LAST_LINE'),$_smarty_tpl);?>
