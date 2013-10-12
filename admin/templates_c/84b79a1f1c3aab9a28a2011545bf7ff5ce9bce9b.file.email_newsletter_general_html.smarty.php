<?php /* Smarty version Smarty-3.0.5, created on 2012-01-01 16:58:48
         compiled from "c:\xampp\htdocs\thesportcity\smarty_tpl/email_newsletter_general_html.smarty" */ ?>
<?php /*%%SmartyHeaderCode:107084f0090c854fd22-79706904%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '84b79a1f1c3aab9a28a2011545bf7ff5ce9bce9b' => 
    array (
      0 => 'c:\\xampp\\htdocs\\thesportcity\\smarty_tpl/email_newsletter_general_html.smarty',
      1 => 1325431679,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '107084f0090c854fd22-79706904',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<html><head>
<?php $_template = new Smarty_Internal_Template('../smarty_tpl/email_css.smarty', $_smarty_tpl->smarty, $_smarty_tpl, $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null);
 echo $_template->getRenderedTemplate();?><?php $_template->updateParentVariables(0);?><?php unset($_template);?>
</head><body style="font-family: Arial, sans-serif;">
<div class="portlet">
  <div class="content">
<img src="http://www.thesportcity.net/img/herbas.jpg" align="left" margin=5> <h1><?php echo $_smarty_tpl->getVariable('data')->value['TITLE'];?>
</h1>
<span style="color: #999; font-size: 11px;"><?php echo $_smarty_tpl->getVariable('data')->value['DESCR'];?>
</span>
<h3><?php echo get_translation(array('fonema'=>'LANG_HI_U'),$_smarty_tpl);?>
, <?php echo $_smarty_tpl->getVariable('data')->value['USER_NAME'];?>
</h3>

<?php echo $_smarty_tpl->getVariable('data')->value['HEADER'];?>

</div>

<?php echo $_smarty_tpl->getVariable('data')->value['SITE_NEWS'];?>

<div style="clear:both;"></div>
<?php if (isset($_smarty_tpl->getVariable('data',null,true,false)->value['SITE_GAMES'])){?>
<div class="portlet">
 <div class="header"><?php echo get_translation(array('fonema'=>'LANG_SITE_GAMES_U'),$_smarty_tpl);?>
</div>
 <?php if (isset($_smarty_tpl->getVariable('data',null,true,false)->value['SITE_GAMES']['MANAGER'])){?>
  <?php  $_smarty_tpl->tpl_vars['manager'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('data')->value['SITE_GAMES']['MANAGER']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['manager']->key => $_smarty_tpl->tpl_vars['manager']->value){
?>
  <div class="content">
   <b><?php echo get_translation(array('fonema'=>'LANG_MANAGER_U'),$_smarty_tpl);?>
:</b> <a href="http://www.thesportcity.net/f_manager_control.php?season_id=<?php echo $_smarty_tpl->tpl_vars['manager']->value['SEASON_ID'];?>
"><b><?php echo $_smarty_tpl->tpl_vars['manager']->value['SEASON_TITLE'];?>
</b></a>
  </div>
  <?php }} ?>
 <?php }?>
 <?php if (isset($_smarty_tpl->getVariable('data',null,true,false)->value['SITE_GAMES']['RVS_MANAGER'])){?>
  <?php  $_smarty_tpl->tpl_vars['manager'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('data')->value['SITE_GAMES']['RVS_MANAGER']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['manager']->key => $_smarty_tpl->tpl_vars['manager']->value){
?>
  <div class="content">
   <b><?php echo get_translation(array('fonema'=>'LANG_FANTASY_LEAGUE_U'),$_smarty_tpl);?>
:</b> <a href="http://www.thesportcity.net/rvs_manager_league.php?mseason_id=<?php echo $_smarty_tpl->tpl_vars['manager']->value['SEASON_ID'];?>
"><b><?php echo $_smarty_tpl->tpl_vars['manager']->value['SEASON_TITLE'];?>
</b></a>
  </div>
  <?php }} ?>
 <?php }?>
 <?php if (isset($_smarty_tpl->getVariable('data',null,true,false)->value['SITE_GAMES']['WAGER'])){?>
  <?php  $_smarty_tpl->tpl_vars['wager'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('data')->value['SITE_GAMES']['WAGER']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['wager']->key => $_smarty_tpl->tpl_vars['wager']->value){
?>
  <div class="content">
  <b><?php echo get_translation(array('fonema'=>'LANG_WAGER_U'),$_smarty_tpl);?>
:</b> <a href="http://www.thesportcity.net/wager_control.php?season_id=<?php echo $_smarty_tpl->tpl_vars['wager']->value['SEASON_ID'];?>
"><b><?php echo $_smarty_tpl->tpl_vars['wager']->value['SEASON_TITLE'];?>
</b></a>
  </div>
  <?php }} ?>
 <?php }?>
 <?php if (isset($_smarty_tpl->getVariable('data',null,true,false)->value['SITE_GAMES']['ARRANGER'])){?>
  <?php  $_smarty_tpl->tpl_vars['arranger'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('data')->value['SITE_GAMES']['ARRANGER']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['arranger']->key => $_smarty_tpl->tpl_vars['arranger']->value){
?>
  <div class="content">
  <b><?php echo get_translation(array('fonema'=>'LANG_ARRANGER_U'),$_smarty_tpl);?>
:</b> <a href="http://www.thesportcity.net/bracket_control.php?season_id=<?php echo $_smarty_tpl->getVariable('wager')->value['SEASON_ID'];?>
"><b><?php echo $_smarty_tpl->getVariable('wager')->value['SEASON_TITLE'];?>
</b></a>
  </div>
  <?php }} ?>
 <?php }?>
<?php }?>
</div>
<div  style="clear:both"></div>
</div>
<div class="portlet">
  <div class="content">
<span style="color: #999; font-size: 11px; font-family: Arial, sans-serif;"><?php echo get_translation(array('fonema'=>'LANG_NEWSLETTER_UNSUBSCRIBE_INSTR_U'),$_smarty_tpl);?>
<br>
<a href="<?php echo $_smarty_tpl->getVariable('data')->value['URL'];?>
"><?php echo get_translation(array('fonema'=>'LANG_NEWSLETTER_UNSUBSCRIBE_U'),$_smarty_tpl);?>
</a></span><br>
<br>
<?php echo get_translation(array('fonema'=>'LANG_EMAIL_LAST_LINE'),$_smarty_tpl);?>

</div>
</div>
</div>
</body></html>