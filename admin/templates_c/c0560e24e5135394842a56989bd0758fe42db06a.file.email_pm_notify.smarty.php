<?php /* Smarty version Smarty-3.1.11, created on 2012-10-04 22:01:52
         compiled from "c:\xampp\htdocs\thesportcity\smarty_tpl\email_pm_notify.smarty" */ ?>
<?php /*%%SmartyHeaderCode:23975506df9401be4b4-29079772%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'c0560e24e5135394842a56989bd0758fe42db06a' => 
    array (
      0 => 'c:\\xampp\\htdocs\\thesportcity\\smarty_tpl\\email_pm_notify.smarty',
      1 => 1320432170,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '23975506df9401be4b4-29079772',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'data' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.11',
  'unifunc' => 'content_506df9401e5598_96866424',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_506df9401e5598_96866424')) {function content_506df9401e5598_96866424($_smarty_tpl) {?><?php echo get_translation(array('fonema'=>'LANG_HI_U'),$_smarty_tpl);?>
 <?php echo $_smarty_tpl->tpl_vars['data']->value['USER_NAME'];?>


<?php echo get_translation(array('fonema'=>'LANG_EMAIL_PM_NOTIFY_LINE_1'),$_smarty_tpl);?>
 <?php echo $_smarty_tpl->tpl_vars['data']->value['AUTHOR'];?>
 <?php echo get_translation(array('fonema'=>'LANG_EMAIL_PM_NOTIFY_LINE_2'),$_smarty_tpl);?>
 "<?php echo $_smarty_tpl->tpl_vars['data']->value['TOPIC_NAME'];?>
"

<?php echo get_translation(array('fonema'=>'LANG_EMAIL_PM_NOTIFY_LINE_3'),$_smarty_tpl);?>
 <?php echo $_smarty_tpl->tpl_vars['data']->value['URL'];?>


<?php echo get_translation(array('fonema'=>'LANG_EMAIL_LAST_LINE'),$_smarty_tpl);?>
<?php }} ?>