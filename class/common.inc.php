<?php
/*
===============================================================================
common.inc.php
-------------------------------------------------------------------------------
===============================================================================
*/

function getLetter ($data) {
  global $tpl;

  // content
  $tpl->setSection('index');
  $tpl->setInstance('letter');
  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/bar_letter.tpl.html');
  if (!$tpl->isCached()) {
    // not cached. add data
    $tpl->addData($data);
  }
  return $tpl->parse();
}

// generate paging info for the template
/*function getPaging ($rows, $pg = 0, $pp = 0) {
  global $page;
  global $perpage;
  global $page_size;
  global $tpl;
  $data = '';
  
  // settings
  if ($pg > 0)
    $page = $pg;
  if (empty($page))
    $page = 1;
  if ($pp > 0)
    $perpage = $pp;
  if (empty($perpage))
    $perpage = $page_size;
  
  // generate data
  $data['NUMROWS'] = $rows;
  $page_tmp = 0;
  for ($c = 0; $c < $rows; $c += $perpage) {
    $page_tmp++;
    $data['PAGES'][$page_tmp-1]['PAGENUM'] = $page_tmp;
    $data['PAGES'][$page_tmp-1]['URL'] = url('page', $page_tmp);
    if ($page_tmp == $page) {
      $data['PAGES'][$page_tmp-1]['SELECTED'][0]['PAGENUM'] = $page_tmp;
    }
    else {
      $data['PAGES'][$page_tmp-1]['NORMAL'][0]['PAGENUM'] = $page_tmp;
      $data['PAGES'][$page_tmp-1]['NORMAL'][0]['URL'] = url('page', $page_tmp);
    }
  }
  
  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/bar_paging.tpl.html');
  if (!$tpl->isCached()) {
    // not cached. add data
    $tpl->addData($data);
  }
  return $tpl->parse();

//  return $data;
} */
?>