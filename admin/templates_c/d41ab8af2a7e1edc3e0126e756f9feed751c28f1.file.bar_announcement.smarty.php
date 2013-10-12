<?php /* Smarty version Smarty-3.0.5, created on 2011-05-21 13:10:26
         compiled from "c:\xampp\htdocs\thesportcity\smarty_tpl/bar_announcement.smarty" */ ?>
<?php /*%%SmartyHeaderCode:57564dd7abb2c77f46-71895478%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'd41ab8af2a7e1edc3e0126e756f9feed751c28f1' => 
    array (
      0 => 'c:\\xampp\\htdocs\\thesportcity\\smarty_tpl/bar_announcement.smarty',
      1 => 1304370991,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '57564dd7abb2c77f46-71895478',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
)); /*/%%SmartyHeaderCode%%*/?>
<div class="portlet">
<div class="header"><?php echo get_translation(array('fonema'=>'LANG_ANNOUNCEMENTS_U'),$_smarty_tpl);?>
</div>
  <?php  $_smarty_tpl->tpl_vars['news_item'] = new Smarty_Variable;
 $_from = $_smarty_tpl->getVariable('news')->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
if ($_smarty_tpl->_count($_from) > 0){
    foreach ($_from as $_smarty_tpl->tpl_vars['news_item']->key => $_smarty_tpl->tpl_vars['news_item']->value){
?>
	<div class="content">
		<div><?php if (isset($_smarty_tpl->tpl_vars['news_item']->value['MANAGER_SEASON'])){?><b><?php echo $_smarty_tpl->tpl_vars['news_item']->value['MANAGER_SEASON']['SEASON_TITLE'];?>
</b><br><?php }?>
		     <?php if (isset($_smarty_tpl->tpl_vars['news_item']->value['WAGER_SEASON'])){?><b><?php echo $_smarty_tpl->tpl_vars['news_item']->value['WAGER_SEASON']['SEASON_TITLE'];?>
</b><br><?php }?>
		   <a href="news.php?news_id=<?php echo $_smarty_tpl->tpl_vars['news_item']->value['NEWS_ID'];?>
&lang_id=<?php echo $_smarty_tpl->tpl_vars['news_item']->value['LANG'];?>
"><b><?php echo $_smarty_tpl->tpl_vars['news_item']->value['TITLE'];?>
</b></a> (<?php echo $_smarty_tpl->tpl_vars['news_item']->value['POSTS'];?>
)</div>
		<div style="float:right;"><?php echo $_smarty_tpl->tpl_vars['news_item']->value['DATE_PUBLISHED'];?>
</div>
		<div style="clear:both;"></div>

		<?php echo $_smarty_tpl->tpl_vars['news_item']->value['DESCR'];?>

     <br><a href="news.php?news_id=<?php echo $_smarty_tpl->tpl_vars['news_item']->value['NEWS_ID'];?>
&lang_id=<?php echo $_smarty_tpl->tpl_vars['news_item']->value['LANG'];?>
"><?php echo get_translation(array('fonema'=>'LANG_READ_ALL_NEWS_U'),$_smarty_tpl);?>
</a>
	</div>
   <?php }} ?>
   <?php if (isset($_smarty_tpl->getVariable('more',null,true,false)->value)){?>
<div class="content">
	<a href="news.php?genre=<?php echo $_smarty_tpl->getVariable('more')->value['GENRE'];?>
"><b><?php echo get_translation(array('fonema'=>'LANG_MORE_U'),$_smarty_tpl);?>
</b></a>
</div>
<?php }?>
</div>
