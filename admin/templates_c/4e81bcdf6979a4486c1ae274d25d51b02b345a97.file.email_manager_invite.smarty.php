<?php /* Smarty version Smarty-3.1.11, created on 2012-10-04 22:01:51
         compiled from "c:\xampp\htdocs\thesportcity\smarty_tpl\email_manager_invite.smarty" */ ?>
<?php /*%%SmartyHeaderCode:26214506df93fb7d706-50575207%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '4e81bcdf6979a4486c1ae274d25d51b02b345a97' => 
    array (
      0 => 'c:\\xampp\\htdocs\\thesportcity\\smarty_tpl\\email_manager_invite.smarty',
      1 => 1349375662,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '26214506df93fb7d706-50575207',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'data' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.11',
  'unifunc' => 'content_506df93fcb1f65_38085487',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_506df93fcb1f65_38085487')) {function content_506df93fcb1f65_38085487($_smarty_tpl) {?><?php echo get_translation(array('fonema'=>'LANG_HI_U'),$_smarty_tpl);?>
, <?php echo $_smarty_tpl->tpl_vars['data']->value['USER_NAME'];?>


<?php echo get_translation(array('fonema'=>'LANG_EMAIL_MANAGER_INVITE_LINE_1'),$_smarty_tpl);?>
 <b><?php echo $_smarty_tpl->tpl_vars['data']->value['SEASON_TITLE'];?>
</b>

<?php echo get_translation(array('fonema'=>'LANG_EMAIL_MANAGER_INVITE_LINE_2'),$_smarty_tpl);?>
 <b><?php echo $_smarty_tpl->tpl_vars['data']->value['START_DATE'];?>
</b>

<?php echo get_translation(array('fonema'=>'LANG_EMAIL_MANAGER_INVITE_LINE_3'),$_smarty_tpl);?>
 <a href="<?php echo $_smarty_tpl->tpl_vars['data']->value['URL'];?>
"><?php echo $_smarty_tpl->tpl_vars['data']->value['URL'];?>
</a>

<?php echo get_translation(array('fonema'=>'LANG_EMAIL_LAST_LINE'),$_smarty_tpl);?>

<?php }} ?>