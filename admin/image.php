<?php
/*
===============================================================================
image.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of files
  - deletes files

TABLES USED: 
  - none

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/adm_menu.php');

// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/language.class.php');
include('../class/form.class.php');
include('../class/files.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$frm = new form;
$fl = new files;

// security layer
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');
if (empty($_SESSION["_admin"][MENU_PARAMETERS_IMAGE]) || strcmp($_SESSION["_admin"][MENU_PARAMETERS_IMAGE], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_PARAMETERS_IMAGE], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATES ----------------------------------------------------------
// delete
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_PARAMETERS_IMAGE], 'FA') == 0) {
  $fl->delete($_GET['del']);
}

// rename
if (isset($_POST['folder']) && ($_POST['folder'] != '0')) {
  $folder = 'img/'.$_POST['folder']."/";
} else {
  $folder = 'img/';
}
$fl->set($conf_home_dir.$folder);
$rename = trim(isset($_GET['rename']) ? $_GET['rename'] : '');
$rename_to = trim(isset($_GET['rename_to']) ? $_GET['rename_to'] : '');
if (!empty($rename) && !empty($rename_to) && !$ro && strcmp($_SESSION["_admin"][MENU_PARAMETERS_IMAGE], 'FA') == 0) {
  $fl->frename($rename, $rename_to);
}
// upload
if (isset($_FILES['upload']) && !$ro) {
  $uploaded = $fl->uploads("image");
  $show = $uploaded[0];
}
// --- END UPDATES ------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));

// presets
if (!isset($_GET['page']))
  $page = 1;
else $page = $_GET['page'];
if (!isset($perpage))
  $perpage = $page_size;
if (empty($_GET['order']))
  $order = 'FILE asc';
  
// filtering
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

if (isset($_GET['query']) && !empty($_GET['query'])) {
  $data['FILTERED'][0]['X'] = 1;
}

// get file list
$dir = $fl->getfiles();

echo sizeof($dir);
$filtered = 0;
for ($i = 0; $i < sizeof($dir); $i++) {
  $name = $dir[$i]['name'];
  if (!empty($_GET['query']) && preg_match($_GET['query'], $name))
   {
    $filtereddir[$filtered]['name'] = $dir[$i]['name'];
    $filtereddir[$filtered]['size'] = $dir[$i]['size'];
    $filtered++;
   }
}

if ($filtered > 0)
 {
  $rows = $filtered;
  $from = ($page - 1) * $perpage;
  $to = $from + $perpage;
  if ($to > $rows)
    $to = $rows;
  sort($filtereddir);
  $c = 0;
  set_time_limit(100); 

  for ($i = $from; $i < $to; $i++) {
    $name = $filtereddir[$i]['name'];
    $size = $filtereddir[$i]['size'];
  //  echo $query;
    if (empty($query) || eregi($query, $name)) {
      $data['ITEM'][$c]['FILE'] = $name;
      $data['ITEM'][$c]['SIZE'] = $size;
  //    $data['ITEM'][$c]['SHOW_URL'] = $PHP_SELF.url('show', $name);
  //     $data['ITEM'][$c]['SHOW'] = $PHP_SELF.url('show', $name);
      if (strcmp($_SESSION["_admin"][MENU_PARAMETERS_IMAGE], 'FA') == 0)
        $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $name);
      if ($c & 2 > 0)
        $data['ITEM'][$c]['ODD'][0]['X'] = 1;
      else
        $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
      $c++;
    }
  }

 }
else
  {
   $rows = sizeof($dir);

  //echo $rows;
  $from = ($page - 1) * $perpage;
  $to = $from + $perpage;
  if ($to > $rows)
    $to = $rows;
  sort($dir);
  $c = 0;
  set_time_limit(100); 

  for ($i = $from; $i < $to; $i++) {
    $name = $dir[$i]['name'];
    $size = $dir[$i]['size'];
  //  echo $query;
    if (empty($query) || eregi($query, $name)) {
      $data['ITEM'][$c]['FILE'] = $folder.$name;
      $data['ITEM'][$c]['SIZE'] = $size;
      if (strcmp($_SESSION["_admin"][MENU_PARAMETERS_IMAGE], 'FA') == 0)
        $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $folder.$name);
      if ($c & 2 > 0)
        $data['ITEM'][$c]['ODD'][0]['X'] = 1;
      else
        $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
      $c++;
    }
  }
}

echo $c;
if ($rows == 0) {
  $data['NORECORDS'][0]['X'] = 1;
}

// paging
$data['PAGING'][0]['NUMROWS'] = $rows;
$page_tmp = 0;
for ($c = 0; $c < $rows; $c += $perpage) {
  $page_tmp++;
  if ($page_tmp == $page) {
    $data['PAGING'][0]['PAGES'][$page_tmp-1]['SELECTED'][0]['PAGENUM'] = $page_tmp;
  }
  else {
    $data['PAGING'][0]['PAGES'][$page_tmp-1]['NORMAL'][0]['PAGENUM'] = $page_tmp;
    $data['PAGING'][0]['PAGES'][$page_tmp-1]['NORMAL'][0]['URL'] = url('page', $page_tmp);
  }
}

// image preview
if (!empty($show)) {
  $src = $conf_site_url.$folder.$show;
  $data['SHOW'][0]['SRC'] = $src;
}

$opt['class'] = 'input';
$opt['options'] = getDirectory($conf_home_dir."/img", 0);
//print_r($dirs);
$data['DIRS'] = $frm->getInput(FORM_INPUT_SELECT, 'folder', $folder, $opt, $folder);
$data['CHOOSE_DIRS'] = $frm->getInput(FORM_INPUT_SELECT, 'folder', $folder, $opt, $folder);
$data['CURRENT_DIR'] = $folder;

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/image.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');

?>
