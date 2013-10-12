<?php
/*
===============================================================================
inputs.inc.php
-------------------------------------------------------------------------------
Functions to generate form inputs for widely used options
===============================================================================
*/

// generate SOURCES dropdown
function inputSources ($name, $crop = 25, $onchange='formtag()') {
  global $db;
  global $frm;
  $sopt = array('[E]' => ' ');
  $db->select('sources', '*', '', 'TITLE');
  $db->setPage();
  while ($row = $db->nextRow()) {
    $row['TITLE'] = truncateString($row['TITLE'], $crop);
    if (empty($row['LINK']))
      $sopt[] = $row['TITLE'];
    else
      $sopt[$row['LINK']] = $row['TITLE'];
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  $spara['onchange'] = $onchange;
  return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
}

function inputFooters ($name, $crop = 25) {
  global $db;
  global $frm;
  $sopt = array('[E]' => ' ');
  $db->select('sources', '*', "HAS_FOOTER='Y'", 'TITLE');
  $db->setPage();
  while ($row = $db->nextRow()) {
    $sopt[$row['SOURCE_ID']] = truncateString($row['TITLE'], $crop);
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
}


function inputBlogers ($name, $crop = 25) {
  global $db;
  global $frm;
  $sopt = array('[E]' => ' ');
  $db->select('busers', '*', "ALLOW_BLOG='Y'", 'LAST_NAME');
  $db->setPage();
  while ($row = $db->nextRow()) {
    $sopt[$row['USER_ID']] = truncateString($row['LAST_NAME'].", ".$row['FIRST_NAME'], $crop);
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
}

// generate category dropdown
function inputCats ($name, $sel = '') {
  global $db;
  global $frm;
  global $input_cats_cache;
  global $_SESSION;
  if (isset($input_cats_cache)) {
    // read from cache
    $sopt = $input_cats_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $sql = "SELECT C.CAT_ID, CD.CAT_NAME
        FROM cats  C, cats_details CD 
        WHERE C.PUBLISH='Y'
		aND C.CAT_ID = CD.CAT_ID  
		AND CD.LANG_ID=".$_SESSION['lang_id']."
        ORDER BY CD.CAT_NAME";
//echo $sql;
    $db->query($sql);
    while ($row = $db->nextRow()) {
      $sopt[$row['CAT_ID']] = $row['CAT_NAME'];
    }
    $input_cats_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate category dropdown
function inputForumCats ($name, $sel = '') {
  global $db;
  global $frm;
  global $_SESSION;
  global $input_forum_cats_cache;
  if (isset($input_forum_cats_cache)) {
    // read from cache
    $sopt = $input_forum_cats_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $sql = "SELECT C.CAT_ID, CD.CAT_NAME
        FROM forum_cats  C 
		left JOIN forum_cats_details CD ON C.CAT_ID = CD.CAT_ID  AND CD.LANG_ID=".$_SESSION['lang_id']."
        WHERE C.PUBLISH='Y'
        ORDER BY CD.CAT_NAME";
    $db->query($sql);
    while ($row = $db->nextRow()) {
      $sopt[$row['CAT_ID']] = $row['CAT_NAME'];
    }
    $input_forum_cats_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate category dropdown
function inputForumGroups ($name, $sel = '') {
  global $db;
  global $frm;
  global $_SESSION;
  global $input_forum_groups_cache;
  if (isset($input_forum_groups_cache)) {
    // read from cache
    $sopt = $input_forum_groups_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $sql = "SELECT C.GROUP_ID, CD.GROUP_NAME
        FROM forum_groups  C 
		left JOIN forum_groups_details CD ON C.GROUP_ID = CD.GROUP_ID  AND CD.LANG_ID=".$_SESSION['lang_id']."
        WHERE 1
        ORDER BY CD.GROUP_NAME";
    $db->query($sql);
    while ($row = $db->nextRow()) {
      $sopt[$row['GROUP_ID']] = $row['GROUP_NAME'];
    }
    $input_forum_groups_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate genre type dropdown
function inputGenreTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $genre_types;
  $spara['options'] = truncateString($genre_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate genre type dropdown
function inputStreamTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $stream_types;
  $spara['options'] = truncateString($stream_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate genre type dropdown
function inputGalleryTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $gallery_types;
  $spara['options'] = truncateString($gallery_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate genre type dropdown
function inputBannerPositions ($name, $sel = '', $crop = 25) {
  global $frm;
  global $banner_positions;
  $spara['options'] = truncateString($banner_positions, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputInfoblockPositions ($name, $sel = '', $crop = 25) {
  global $frm;
  global $infoblock_positions;
  $spara['options'] = truncateString($banner_positions, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate genre type dropdown
function inputMultimediaTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $multimedia_types;
print_r($multimedia_types);
  $spara['options'] = truncateString($multimedia_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate genre type dropdown
function inputRunningLineTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $running_line_types;
  $spara['options'] = truncateString($running_line_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate genre type dropdown
function inputTransferTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $transfer_types;
  $spara['options'] = truncateString($transfer_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate game type dropdown
function inputGameTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $game_types;
  $spara['options'] = truncateString($game_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate member type dropdown
function inputMemberTypes ($name, $part, $sel = '', $crop = 50) {
  global $frm;
  global $member_types;
  $spara['options'] = truncateString($member_types[$part], $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate member type dropdown
function inputMMSAttachmentTypes ($name, $sel = '', $crop = 50) {
  global $frm;
  global $mms_attachment_types;
  $spara['options'] = truncateString($mms_attachment_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate tournament type dropdown
function inputTournamentTypes ($name, $sel = '', $crop = 50) {
  global $frm;
  global $tournament_type;
  $spara['options'] = truncateString($tournament_type, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate team type dropdown
function inputTeamTypes ($name, $sel = '', $crop = 40) {
  global $frm;
  global $team_types;
  $spara['options'] = truncateString($team_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate team dropdown
function inputTeams ($name, $sel = '', $crop = 80) {
  global $db;
  global $frm;
  global $sports_l;
  global $input_teams_cache;
  if (is_array($input_teams_cache)) {
    // read from cache
    $sopt = $input_teams_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('teams', 'TEAM_ID, TEAM_NAME, SPORT_ID', '', 'TEAM_NAME');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['TEAM_ID']] = truncateString($row['TEAM_NAME'], $crop) . " (". $sports_l[$row['SPORT_ID']] . ")";
    }
    $db->free();
    $input_teams_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate team dropdown
function inputTeamsFiltered ($name, $sel = '', $filter = '', $crop= 40) {
  global $db;
  global $frm;
  global $input_teamsf_cache;
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('teams', 'TEAM_ID, TEAM_NAME', $filter, 'TEAM_NAME');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['TEAM_ID']] = truncateString($row['TEAM_NAME'], $crop);
    }
    $db->free();

  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate tournament dropdown
function inputTournaments ($name, $sel = '', $crop = 25, $spara = array()) {
  global $db;
  global $frm;
  global $input_tournaments_cache;
  if (isset($input_tournaments_cache)) {
    // read from cache
    $sopt = $input_tournaments_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $sql ="SELECT C.TOURNAMENT_ID, CD.TNAME
           FROM tournaments  C 
		left JOIN tournaments_details CD ON C.TOURNAMENT_ID = CD.TOURNAMENT_ID  AND CD.LANG_ID=".$_SESSION['lang_id']."
   	   ORDER BY CD.TNAME";
    $db->query($sql);

    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['TOURNAMENT_ID']] = truncateString($row['TNAME'], $crop);
    }
    $db->free();
    $input_tournaments_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate season dropdown
function inputSeasons ($name, $sel = '', $crop = 50) {
  global $db;
  global $frm;
  global $input_seasons_cache;
  if (isset($input_seasons_cache)) {
    // read from cache
    $sopt = $input_seasons_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $sql ="SELECT C.SEASON_ID, CD.SEASON_TITLE
           FROM seasons C 
		left JOIN seasons_details CD ON C.SEASON_ID = CD.SEASON_ID AND CD.LANG_ID=".$_SESSION['lang_id']."
   	   ORDER BY CD.SEASON_TITLE";
    $db->query($sql);

    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['SEASON_ID']] = truncateString($row['SEASON_TITLE'], $crop);
    }
    $db->free();
    $input_seasons_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputAllSeasons ($name, $sel = '', $crop = 25) {
  global $db;
  global $frm;
  global $input_seasons_cache;
  if (isset($input_seasons_cache)) {
    // read from cache
    $sopt = $input_seasons_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('seasons', 'SEASON_ID, SEASON_TITLE', 
                'STANDINGS=\'Y\' OR STATIC_STANDINGS=\'Y\'', 'START_DATE desc, SEASON_TITLE');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['SEASON_ID']] = truncateString($row['SEASON_TITLE'], $crop);
    }
    $db->free();
    $input_seasons_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputAllSeasonsWithStandings ($name, $sel = '', $crop = 50) {
  global $db;
  global $frm;
  global $input_all_seasons_st_cache;
  if (isset($input_all_seasons_st_cache)) {
    // read from cache
    $sopt = $input_all_seasons_st_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    // get source list
    $sql = "SELECT SEASON_ID AS ID, SEASON_TITLE,
               0 AS SOURCE
        FROM seasons
        WHERE (STATIC_STANDINGS='Y' OR STANDINGS='Y') AND PUBLISH='Y'
     
        UNION

        SELECT STANDINGS_ID AS ID, SEASON_TITLE, 1 AS SOURCE
        FROM standings
        WHERE PUBLISH='Y'
      
        ORDER BY SEASON_TITLE";
    $db->query($sql);
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['SOURCE']."_".$row['ID']] = truncateString($row['SEASON_TITLE'], $crop);
    }
    $db->free();
    $input_all_seasons_st_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputAllSeasonsWithStandingsCurrent ($name, $sel = '', $crop = 50) {
  global $db;
  global $frm;
  global $input_all_seasons_st_cache;
  if (isset($input_all_seasons_st_cache)) {
    // read from cache
    $sopt = $input_all_seasons_st_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    // get source list
    $sql = "SELECT SEASON_ID AS ID, SEASON_TITLE,
               0 AS SOURCE
        FROM seasons
        WHERE (STATIC_STANDINGS='Y' OR STANDINGS='Y') AND PUBLISH='Y'
              AND END_DATE > DATE_ADD( NOW( ) , INTERVAL -2 MONTH )  
     
        UNION

        SELECT STANDINGS_ID AS ID, SEASON_TITLE, 1 AS SOURCE
        FROM standings
        WHERE PUBLISH='Y' AND END_DATE > DATE_ADD( NOW( ) , INTERVAL -2 MONTH )  
      
        ORDER BY SEASON_TITLE";
    $db->query($sql);
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['SOURCE']."_".$row['ID']] = truncateString($row['SEASON_TITLE'], $crop);
    }
    $db->free();
    $input_all_seasons_st_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputStatsSeasons ($name, $sel = '', $crop = 30) {
  global $db;
  global $frm;
  global $input_seasons_cache;
  if (isset($input_seasons_cache)) {
    // read from cache
    $sopt = $input_seasons_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('seasons', 'SEASON_ID, SEASON_TITLE', 
                'TOPSTATS=\'Y\'', 'START_DATE desc, SEASON_TITLE');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['SEASON_ID']] = truncateString($row['SEASON_TITLE'], $crop);
    }
    $db->free();
    $input_seasons_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}


function inputGalleries ($name, $sel = '', $crop = 50) {
  global $db;
  global $frm;
  global $input_galleries_cache;
  if (isset($input_galleries_cache)) {
    // read from cache
    $sopt = $input_galleries_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('galleries', 'GALLERY_ID, TITLE', 
                '', 'CREATED_DATE DESC');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['GALLERY_ID']] = truncateString($row['TITLE'], $crop);
    }
    $db->free();
    $input_galleries_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate season dropdown
function inputTseasons ($name, $sel = '', $crop = 25) {
  global $db;
  global $frm;
  global $input_tseasons_cache;
  if (isset($input_tseasons_cache)) {
    // read from cache
    $sopt = $input_tseasons_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('tseasons', 'SEASON_ID, TSEASON_TITLE', 
                "END_DATE > NOW() and PUBLISH='Y' and WAP_ONLY='N'", 'TSEASON_TITLE');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['SEASON_ID']] = truncateString($row['TSEASON_TITLE'], $crop);
    }
    $db->free();
    $input_tseasons_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}


function inputTseasonsAll ($name, $sel = '', $crop = 25) {
  global $db;
  global $frm;
  global $input_tseasons_all_cache;
  if (isset($input_tseasons_all_cache)) {
    // read from cache
    $sopt = $input_tseasons_all_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('tseasons', 'SEASON_ID, TSEASON_TITLE', "PUBLISH='Y' and WAP_ONLY='N'", 'END_DATE DESC');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['SEASON_ID']] = truncateString($row['TSEASON_TITLE'], $crop);
    }
    $db->free();
    $input_tseasons_all_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate organization dropdown
function inputOrganizations ($name, $sel = '', $crop = 25) {
  global $db;
  global $frm;
  global $input_organizations_cache;
  if (isset($input_organizations_cache)) {
    // read from cache
    $sopt = $input_organizations_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('organizations', 'ORGANIZATION_ID, TITLE', '', 'TITLE');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['ORGANIZATION_ID']] = truncateString($row['TITLE'], $crop);
    }
    $db->free();
    $input_organizations_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate organization type dropdown
function inputOrgtypes ($name, $sel = '', $crop = 25) {
  global $db;
  global $frm;
  global $input_orgtypes_cache;
  if (isset($input_orgtypes_cache)) {
    // read from cache
    $sopt = $input_orgtypes_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('orgtypes', 'ORGTYPE_ID, ORGTYPE_TITLE', '', 'ORGTYPE_TITLE');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['ORGTYPE_ID']] = truncateString($row['ORGTYPE_TITLE'], $crop);
    }
    $db->free();
    $input_orgtypes_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate games dropdown
function inputManagerSeasons ($name, $sel = '', $crop = 80, $empty = false) {
  global $db;
  global $frm;
  global $input_manager_seasons;
  global $_SESSION;

  if (isset($input_manager_seasons)) {
    // read from cache
    $sopt = $input_manager_seasons;
  }
  else {
    $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MSS.END_DATE > NOW( ) ACTIVE
           FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
           WHERE MSS.START_DATE < NOW( ) 
		AND MSS.PUBLISH='Y'
        ORDER BY MSS.START_DATE ASC";
    $db->query($sql);
    if ($empty)
      $sopt = array('[E]' => ' '); 
    while ($row = $db->nextRow()) {
      $sopt[$row['SEASON_ID']]['value'] = truncateString($row['SEASON_TITLE'], $crop);
      if ($row['ACTIVE'] == 0)
        $sopt[$row['SEASON_ID']]['style'] = "style='text-decoration: line-through;'";
    }
    $db->free();
    $input_manager_seasons = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate games dropdown
function inputRvsManagerSeasons ($name, $sel = '', $crop = 80, $empty = false) {
  global $db;
  global $frm;
  global $input_manager_seasons;
  global $_SESSION;

  $sopt = '';
  if (isset($input_manager_seasons)) {
    // read from cache
    $sopt = $input_manager_seasons;
  }
  else {
    $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MSS.END_DATE > NOW( ) ACTIVE
           FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
           WHERE MSS.START_DATE < NOW( ) 
		AND MSS.PUBLISH='Y'
		AND MSS.ALLOW_RVS_LEAGUES='Y'
        ORDER BY MSS.START_DATE ASC";
    $db->query($sql);
    if ($empty)
      $sopt = array('[E]' => ' '); 
    while ($row = $db->nextRow()) {
      $sopt[$row['SEASON_ID']]['value'] = truncateString($row['SEASON_TITLE'], $crop);
      if ($row['ACTIVE'] == 0)
        $sopt[$row['SEASON_ID']]['style'] = "style='text-decoration: line-through;'";
    }
    $db->free();
    $input_manager_seasons = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate games dropdown
function inputSoloManagerSeasons ($name, $sel = '', $crop = 80, $empty = false) {
  global $db;
  global $frm;
  global $input_manager_seasons;
  global $_SESSION;

  $sopt = '';
  if (isset($input_manager_seasons)) {
    // read from cache
    $sopt = $input_manager_seasons;
  }
  else {
    $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MSS.END_DATE > NOW( ) ACTIVE
           FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
           WHERE MSS.START_DATE < NOW( ) 
		AND MSS.PUBLISH='Y'
		AND MSS.ALLOW_SOLO='Y'
        ORDER BY MSS.START_DATE ASC";
    $db->query($sql);
    if ($empty)
      $sopt = array('[E]' => ' '); 
    while ($row = $db->nextRow()) {
      $sopt[$row['SEASON_ID']]['value'] = truncateString($row['SEASON_TITLE'], $crop);
      if ($row['ACTIVE'] == 0)
        $sopt[$row['SEASON_ID']]['style'] = "style='text-decoration: line-through;'";
    }
    $db->free();
    $input_manager_seasons = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate games dropdown
function inputBracketSeasons ($name, $sel = '', $crop = 80, $empty = false) {
  global $db;
  global $frm;
  global $input_bracket_seasons;
  global $_SESSION;

  if (isset($input_bracket_seasons)) {
    // read from cache
    $sopt = $input_bracket_seasons;
  }
  else {
    $sql="SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE, MSS.END_DATE > NOW( ) ACTIVE
           FROM bracket_seasons MSS
		left JOIN bracket_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
           WHERE MSS.START_DATE < NOW( ) 
		AND MSS.PUBLISH='Y'
        ORDER BY MSS.START_DATE ASC";
    $db->query($sql);
    if ($empty)
      $sopt = array('[E]' => ' '); 
    while ($row = $db->nextRow()) {
      $sopt[$row['SEASON_ID']]['value'] = truncateString($row['TSEASON_TITLE'], $crop);
      if ($row['ACTIVE'] == 0)
        $sopt[$row['SEASON_ID']]['style'] = "style='text-decoration: line-through;'";
    }
    $db->free();
    $input_bracket_seasons = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate games dropdown
function inputBracketRaces ($name, $season_id, $type=0, $sel = '', $crop = 80, $empty = false) {
  global $db;
  global $frm;
  global $_SESSION;

  $filter = "";
  $order = "";
  if ($type== 0) { // past
    $filter = " AND BT.START_DATE < NOW( ) ";
    $order = " DESC";
  } else {
    $filter = " AND BT.START_DATE > NOW( ) ";
    $order = " ASC";
  }
  $sql="SELECT G.GAME_ID, G.TITLE 
		FROM bracket_subseasons BS, bracket_tours BT, seasons S, games_races G 
		WHERE BT.season_id=".$season_id." 
			and G.season_id=S.SEASON_ID 
			and G.PUBLISH='Y' 
			and BT.START_DATE < G.start_DATE 
			and BT.END_DATE > G.START_DATE 
			AND BS.WSEASON_ID=BT.SEASON_ID 
			AND BS.SEASON_ID=S.SEASON_ID 
			".$filter."
	ORDER BY BT.START_DATE ".$order;
  $db->query($sql);
  if ($empty)
    $sopt = array('[E]' => ' '); 
  while ($row = $db->nextRow()) {
     $sopt[$row['GAME_ID']]['value'] = truncateString($row['TITLE'], $crop);
  }
  if (!isset($sopt))
    $sopt = array('[E]' => ' '); 

  $spara['options'] = $sopt;
  $spara['class'] = 'input';

  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate games dropdown
function inputWagerSeasons ($name, $sel = '', $crop = 80, $empty = false) {
  global $db;
  global $frm;
  global $input_wager_seasons;
  global $_SESSION;

  if (isset($input_wager_seasons)) {
    // read from cache
    $sopt = $input_wager_seasons;
  }
  else {
    $sql="SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE, MSS.END_DATE > NOW( ) ACTIVE
           FROM wager_seasons MSS
		left JOIN wager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
           WHERE MSS.START_DATE < NOW( ) 
        ORDER BY MSS.START_DATE ASC";

    $db->query($sql);
    if ($empty)
      $sopt = array('[E]' => ' '); 
    while ($row = $db->nextRow()) {
      $sopt[$row['SEASON_ID']]['value'] = truncateString($row['TSEASON_TITLE'], $crop);
      if ($row['ACTIVE'] == 0)
        $sopt[$row['SEASON_ID']]['style'] = "style='text-decoration: line-through;'";
    }
    $db->free();
    $input_wager_seasons = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate games dropdown
function inputManagerTeams ($manager_season, $name, $sel = '', $crop = 80, $empty = false) {
  global $db;
  global $frm;
  global $input_manager_teams;
  global $_SESSION;

  if (isset($input_manager_teams)) {
    // read from cache
    $sopt = $input_manager_teams;
  }
  else {
    $sql="SELECT TS.TEAM_ID, IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME
  	  FROM manager_subseasons MS, team_seasons TS 
		LEFT JOIN teams T ON TS.TEAM_ID = T.TEAM_ID
		LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
	  WHERE MS.MSEASON_ID=".$manager_season."
		AND MS.SEASON_ID=TS.SEASON_ID
	  ORDER BY TEAM_NAME";

    $db->query($sql);
    if ($empty)
      $sopt = array('[E]' => ' '); 
    while ($row = $db->nextRow()) {
      $sopt[$row['TEAM_ID']] = truncateString($row['TEAM_NAME'], $crop);
    }
    $db->free();
    $input_manager_teams = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate games dropdown
function inputManagerTournamentSeasons ($name, $sel = '', $crop = 80, $empty = false) {
  global $db;
  global $frm;
  global $input_manager_tournament_seasons;
  global $_SESSION;

  if (isset($input_manager_tournament_seasons)) {
    // read from cache
    $sopt = $input_manager_tournament_seasons;
  }
  else {
    $sql="SELECT MSS.MT_ID, MSD.SEASON_TITLE
           FROM manager_tournament MSS
		left JOIN manager_tournament_details MSD ON MSS.MT_ID = MSD.MT_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
           WHERE MSS.START_DATE < NOW( ) 
        ORDER BY MSS.START_DATE ASC";
    $db->query($sql);
    if ($empty)
      $sopt = array('[E]' => ' '); 
    while ($row = $db->nextRow()) {
      $sopt[$row['MT_ID']] = truncateString($row['SEASON_TITLE'], $crop);
    }
    $db->free();
    $input_manager_tournament_seasons = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate games dropdown
function inputUpcomingGames ($name, $sel = '', $crop = 80) {
  global $db;
  global $frm;
  global $input_games_cache;
  if (isset($input_games_cache)) {
    // read from cache
    $sopt = $input_games_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    
    $sql = 'SELECT G.GAME_ID, G.PUBLISH, 
          SUBSTRING(G.START_DATE, 1, 16) START_DATE,
          T1.TEAM_NAME2 TEAM_NAME1, 
          T2.TEAM_NAME2 TEAM_NAME2 
        FROM games G
            left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
            left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
          WHERE G.START_DATE > NOW()
                AND G.GAME_ID NOT IN (SELECT GAME_ID FROM totalizators)
        ORDER BY G.START_DATE';

    $db->query($sql);
    $db->setPage();
    while ($row = $db->nextRow()) {
      $line = $row['START_DATE'].'&nbsp;&nbsp;'.
              $row['TEAM_NAME1'].' - '.$row['TEAM_NAME2'];
      $sopt[$row['GAME_ID']] = truncateString($line, $crop);
    }
    $db->free();
    $input_games_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['onchange'] = 'formtag()';
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// online
function inputUpcomingGames2 ($name, $sel = '', $crop = 80) {
  global $db;
  global $frm;
  global $input_games_cache;
  if (isset($input_games_cache)) {
    // read from cache
    $sopt = $input_games_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    
    $sql = "SELECT G.GAME_ID, G.PUBLISH, 
          SUBSTRING(G.START_DATE, 1, 16) START_DATE,
          T1.TEAM_NAME2 TEAM_NAME1, 
          T2.TEAM_NAME2 TEAM_NAME2 
        FROM games G
            left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
            left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
          WHERE G.START_DATE > DATE_ADD(NOW(), INTERVAL -3 HOUR)
                AND G.ONLINE='N' 
        ORDER BY G.START_DATE";

    $db->query($sql);
    $db->setPage();
    while ($row = $db->nextRow()) {
      $line = $row['START_DATE'].'&nbsp;&nbsp;'.
              $row['TEAM_NAME1'].' - '.$row['TEAM_NAME2'];
      $sopt[$row['GAME_ID']] = truncateString($line, $crop);
    }
    $db->free();
    $input_games_cache = $sopt;
  }
  $spara['options'] = $sopt;
//  $spara['onchange'] = 'formtag()';
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// online
function inputVoting ($name, $sel = '', $crop = 80) {
  global $db;
  global $frm;
  global $input_voting_cache;
  if (isset($input_voting_cache)) {
    // read from cache
    $sopt = $input_voting_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    
    $sql = "SELECT V.VOTING_ID, V.QUESTION
        FROM voting V
          WHERE V.PUBLISH='Y' AND V.GLOBAL='N'
        ORDER BY V.START_DATE DESC";

    $db->query($sql);
    $db->setPage();
    while ($row = $db->nextRow()) {
      $line = $row['QUESTION'];
      $sopt[$row['VOTING_ID']] = truncateString($line, $crop);
    }
    $db->free();
    $input_voting_cache = $sopt;
  }
  $spara['options'] = $sopt;
//  $spara['onchange'] = 'formtag()';
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate games dropdown
function inputGame ($name, $sel = '', $crop = 80) {
  global $db;
  global $frm;
  global $input_game;
  if (isset($input_game)) {
    // read from cache
    $sopt = $input_game;
  }
  else {
    // read from db
    $sql = 'SELECT G.GAME_ID, G.PUBLISH, 
          SUBSTRING(G.START_DATE, 1, 16) START_DATE,
          T1.TEAM_NAME2 TEAM_NAME1, 
          T2.TEAM_NAME2 TEAM_NAME2 
        FROM games G
            left join teams T1 on T1.TEAM_ID=G.TEAM_ID1 
            left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
        WHERE G.GAME_ID='.$sel.'
        ORDER BY G.START_DATE';
    $db->query($sql);
    $db->setPage();
    while ($row = $db->nextRow()) {
      $line = $row['START_DATE'].'&nbsp;&nbsp;'.
              $row['TEAM_NAME1'].' - '.$row['TEAM_NAME2'];
      $sopt[$row['GAME_ID']] = truncateString($line, $crop);
    }
    $db->free();
    $input_game = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// communities
// generate community category dropdown
function inputComCats ($name, $sel = '') {
  global $db;
  global $frm;
  global $input_com_cats_cache;
  if (isset($input_com_cats_cache)) {
    // read from cache
    $sopt = $input_com_cats_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('COM_CATS', '*', '', 'CAT_NAME');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['CAT_ID']] = $row['CAT_NAME'];
    }
    $input_com_cats_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}


// generate organization dropdown
function inputAdmins ($name, $sel = '', $crop = 25) {
  global $db;
  global $frm;
  global $input_admins_cache;
  if (isset($input_admins_cache)) {
    // read from cache
    $sopt = $input_admins_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('users', 'USER_ID, USER_NAME', 'ADMIN=\'Y\'', 'USER_NAME');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['USER_ID']] = truncateString($row['USER_NAME'], $crop);
    }
    $db->free();
    $input_admins_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}


// generate organization dropdown
function inputAdminGroups ($name, $sel = '', $crop = 25) {
  global $db;
  global $frm;
  global $input_admin_groups_cache;
  if (isset($input_admin_groups_cache)) {
    // read from cache
    $sopt = $input_admin_groups_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('admin_groups', 'ADMIN_GROUP_ID, GROUP_NAME', '', 'GROUP_NAME');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['ADMIN_GROUP_ID']] = truncateString($row['GROUP_NAME'], $crop);
    }
    $db->free();
    $input_admins_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate organization dropdown
function inputUsersModeratedGroups ($name, $sel = '', $crop = 25) {
  global $db;
  global $frm;
  global $input_uf_groups_cache;
  global $_SESSION;
  global $auth;

  if (isset($input_uf_groups_cache)) {
    // read from cache
    $sopt = $input_uf_groups_cache;
  }
  else {
    // read from db
    $sopt = array();
     $sql='SELECT UA.GROUP_ID, AD.GROUP_NAME
             FROM forum_groups_members UA, forum_groups A
		LEFT JOIN forum_groups_details AD ON
			AD.GROUP_ID=A.GROUP_ID and AD.LANG_ID='.$_SESSION['lang_id'].'
             WHERE A.GROUP_ID=UA.GROUP_ID
                  AND UA.USER_ID='.$auth->getUserId().'
		  AND UA.LEVEL IN (1,3)
            ORDER BY AD.GROUP_NAME ASC';
    $db->query($sql);
    while ($row = $db->nextRow()) {
      $sopt[$row['GROUP_ID'].";".$row['GROUP_NAME']] = $row['GROUP_NAME'];
    }
    $db->free();
    $input_uf_groups_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  $spara['multiple'] = 'multiple';
  $spara['size'] = '4';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputForums ($name, $sel = '', $crop = 25) {
  global $db;
  global $frm;
  global $input_uf_cache;
  if (isset($input_uf_cache)) {
    // read from cache
    $sopt = $input_uf_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('phpbb3_forums', 'FORUM_ID, FORUM_NAME', '', 'FORUM_NAME');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['FORUM_ID']] = truncateString($row['FORUM_NAME'], $crop);
    }
    $db->free();
    $input_uf_cache = $sopt;
  }
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate subsites dropdown
function inputSubsites ($name, $sel = '', $crop = 25) {
  global $frm;
  global $subsites;
  $spara['options'] = truncateString($subsites, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate subsites dropdown
function inputWAPProviders ($name, $sel = '', $crop = 25) {
  global $frm;
  global $wap_providers;
  $spara['options'] = truncateString($wap_providers, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}


// generate community category dropdown
function inputSMSJobKeywords ($name, $sel = '') {
  global $db;
  global $frm;
  global $input_sms_jobs_keys_cache;
  if (isset($input_sms_jobs_keys_cache)) {
    // read from cache
    $sopt = $input_sms_jobs_keys_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('sms_subscribe_keywords', '*', '', 'KEYWORD');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['KEYWORD_ID']] = $row['KEYWORD'];
    }
    $input_sms_jobs_keys_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}


// generate community category dropdown
function inputSMSServices ($name, $sel = '') {
  global $db;
  global $frm;
  global $input_sms_jobs_keys_cache;
  if (isset($input_sms_services_cache)) {
    // read from cache
    $sopt = $input_sms_services_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('sms_services', '*', '', 'TITLE');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['SERVICE_ID']] = $row['TITLE'];
    }
    $input_sms_services_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}


// generate genre type dropdown
function inputLanguages ($name, $sel = '', $crop = 25) {
  global $db;
  global $frm;
  global $input_languages_cache;
  if (isset($input_languages_cache)) {
    // read from cache
    $sopt = $input_languages_cache;
  }
  else {
    // read from db
    $db->select('languages', '*', '', 'LATIN_NAME');
    $db->setPage();
    while ($row = $db->nextRow()) {
	$sopt[$row['SHORT_CODE']] = $row['ORIGINAL'];
    }
    $input_languages_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}


// communities
// generate community category dropdown
function inputAwards ($name, $sel = '') {
  global $db;
  global $frm;
  global $input_awards_cache;
  if (isset($input_awards_cache)) {
    // read from cache
    $sopt = $input_awards_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('awards', '*', '', 'TITLE');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['AWARD_ID']] = $row['TITLE'];
    }
    $input_awards_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}


// generate community category dropdown
function inputCountries($name, $sel = '') {
  global $db;
  global $frm;
  global $input_countries_cache;
  if (isset($input_countries_cache)) {
    // read from cache
    $sopt = $input_countries_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('countries', '*', '', 'LATIN_NAME');
    $db->setPage();
    while ($row = $db->nextRow()) {
      if ($row['LATIN_NAME'] != $row['ORIGINAL'])
        $sopt[$row['ID']] = $row['LATIN_NAME']. " (".$row['ORIGINAL'].")";
      else $sopt[$row['ID']] = $row['LATIN_NAME'];
    }
    $input_countries_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate genre type dropdown
function inputEffectTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $effect_types;
  $spara['options'] = truncateString($effect_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate genre type dropdown
function inputEquipPoints ($name, $sel = '', $crop = 25) {
  global $frm;
  global $equip_points;
  $spara['options'] = truncateString($equip_points, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputProperties ($name, $sel = '', $crop = 25) {
  global $frm;
  global $properties_l;
  global $langs;

  $spara['options'] = truncateStringML($properties_l, $langs, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}


// generate community category dropdown
function inputItemTypes($name, $sel = '') {
  global $db;
  global $frm;
  global $input_item_type_cache;
  global $_SESSION;
  if (isset($input_item_type_cache)) {
    // read from cache
    $sopt = $input_item_type_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $sql ="SELECT C.ITEM_TYPE_ID, CD.ITEM_TYPE_NAME
           FROM ss_item_types  C 
		left JOIN ss_item_types_details CD ON C.ITEM_TYPE_ID = CD.ITEM_TYPE_ID  AND CD.LANG_ID=".$_SESSION['lang_id']."
   	   ORDER BY CD.ITEM_TYPE_NAME";
    $db->query($sql);
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['ITEM_TYPE_ID']] = $row['ITEM_TYPE_NAME'];
    }
    $input_item_type_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate genre type dropdown
function inputSportTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $sports;
  $spara['options'] = truncateString($sports, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate genre type dropdown
function inputManagerSportTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $msports;
  $spara['options'] = truncateString($msports, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputLeagueInviteTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $league_invite_types;
  $spara['options'] = truncateString($league_invite_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputWagerLeaguePointTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $wager_league_point_types;
  $spara['options'] = truncateString($wager_league_point_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputLeagueDraftTypes ($name, $sel = '', $crop = 25) {
  global $frm;
  global $draft_types;
  $spara['options'] = truncateString($draft_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputLeagueDraftPickOrderTypes ($name, $sel = '', $crop = 60) {
  global $frm;
  global $draft_pick_order_types;
  $spara['options'] = truncateString($draft_pick_order_types, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputLeagueDraftIntervals ($name, $sel = '', $crop = 25) {
  global $frm;
  global $draft_intervals;
  $spara['options'] = truncateString($draft_intervals, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputLeagueFormat ($name, $sel = '', $crop = 40) {
  global $frm;
  global $fl_format;
  $spara['options'] = truncateString($fl_format, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputManagerNewsletters($name, $sel = '') {
  global $db;
  global $frm;
  global $input_mannews_cache;
  if (isset($input_mannews_cache)) {
    // read from cache
    $sopt = $input_mannews_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('newsletter', '*', 'TYPE=1', 'NAME');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['ID']] = $row['NAME'];
    }
    $input_mannews_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputWagerNewsletters($name, $sel = '') {
  global $db;
  global $frm;
  global $input_wannews_cache;
  if (isset($input_wannews_cache)) {
    // read from cache
    $sopt = $input_wannews_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('newsletter', '*', 'TYPE=2', 'NAME');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['ID']] = $row['NAME'];
    }
    $input_wannews_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

function inputBracketNewsletters($name, $sel = '') {
  global $db;
  global $frm;
  global $input_branews_cache;
  if (isset($input_branews_cache)) {
    // read from cache
    $sopt = $input_branews_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $db->select('newsletter', '*', 'TYPE=3', 'NAME');
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['ID']] = $row['NAME'];
    }
    $input_branews_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}

// generate member type dropdown
function inputGroupMemberLevels ($name, $sel = '', $crop = 50) {
  global $frm;
  global $group_member_level;
  $spara['options'] = truncateString($group_member_level, $crop);
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }
}


function inputRatings($name, $sel = '') {
  global $db;
  global $frm;
  global $input_ratings_cache;
  global $msports;
  global $langs;

  if (isset($input_ratings_cache)) {
    // read from cache
    $sopt = $input_ratings_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $sql = "SELECT DISTINCT MR.SPORT_ID, MR.TOURNAMENT_ID, CD.TNAME
		FROM manager_ratings MR
        		left join tournaments T ON T.TOURNAMENT_ID = MR.TOURNAMENT_ID
			left JOIN tournaments_details CD ON T.TOURNAMENT_ID = CD.TOURNAMENT_ID  AND CD.LANG_ID=".$_SESSION['lang_id']."
	   ORDER BY MR.SPORT_ID, MR.TOURNAMENT_ID";
//echo $sql;	    
    $db->query($sql);
    $db->setPage();
    while ($row = $db->nextRow()) {
      if ($row['TOURNAMENT_ID'] > 0)
        $sopt['0_'.$row['TOURNAMENT_ID']] = $row['TNAME'];
      else if ($row['SPORT_ID'] > 0)
        $sopt[$row['SPORT_ID']."_0"] = $msports[$row['SPORT_ID']];
      else $sopt["0_0"] = $langs['LANG_COMMON_RATING_U'];
    }
    $input_ratings_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }

}

function inputShopStockAttrValue($name, $sel = '') {
  global $db;
  global $frm;
  global $input_shop_attr_value_cache;
  global $msports;
  global $langs;

  if (isset($input_shop_attr_value_cache)) {
    // read from cache
    $sopt = $input_shop_attr_value_cache;
  }
  else {
    // read from db
    $sopt = array('[E]' => ' ');
    $sql = "SELECT DISTINCT SA.ATTRIBUTE_ID, SAV.VALUE_ID, SAD.ITEM_NAME AS ATTR_NAME, SAVD.ITEM_NAME as VALUE
		FROM shop_attributes SA
			left JOIN shop_attributes_details SAD ON SA.attribute_ID = SAD.attribute_ID  AND SAD.LANG_ID=".$_SESSION['lang_id'].",
		     shop_attributes_values SAV
			left JOIN shop_attributes_values_details SAVD ON SAV.VALUE_ID = SAVD.VALUE_ID AND SAVD.LANG_ID=".$_SESSION['lang_id']."
	   WHERE SAV.ATTRIBUTE_ID=SA.ATTRIBUTE_ID
	   ORDER BY SAD.ITEM_NAME, SAVD.ITEM_NAME";
echo $sql;	    
    $db->query($sql);
    $db->setPage();
    while ($row = $db->nextRow()) {
      $sopt[$row['ATTRIBUTE_ID']."_".$row['VALUE_ID']] = $row['ATTR_NAME']." - ".$row['VALUE'];
    }
    $input_shop_attr_value_cache = $sopt;
  }
  $db->free();
  $spara['options'] = $sopt;
  $spara['class'] = 'input';
  if (empty($sel)) {
    return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
  }
  else {
    return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
  }

}


?>