<?php
/*
===============================================================================
res_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit game records
  - edit keywords
  - create new game record

TABLES USED:
  - BASKET.GAMES
  - BASKET.RESULTS
  - BASKET.MEMBERS
  - BASKET.USERS
  - BASKET.TEAMS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] SMS result sending
===============================================================================
*/

// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/adm_menu.php');
include('../class/update.inc.php');

// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/language.class.php');
include('../class/form.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

// security layer
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_SESSION["_admin"][MENU_GAMES_RESULTS]) || strcmp($_SESSION["_admin"][MENU_GAMES_RESULTS], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_GAMES_RESULTS], 'RO') == 0)
  $ro = TRUE;

if (empty($_SESSION["_admin"][MENU_GAMES_RESULTS]) || strcmp($_SESSION["_admin"][MENU_GAMES_RESULTS], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

$match_length=$_POST['match_length'];

function getPage($url, $start, $end) {
  $handle = fopen($url, "rb");
  echo $handle;
  $fd = '';
  do {
    $data = fread($handle, 100000);
    if (strlen($data) == 0) {
       break;
    }
    $fd .= $data;
  } while (true);
echo $data;
  if ($fd) {
    $pradzia = strpos($fd, $start);
    $pabaiga = strpos($fd, $end);
    $length = $pabaiga - $pradzia;
 //   echo $length;
    $code = substr($fd, $pradzia, $length);
    $code = trim($code);
    return $code;
  }
}

$encoding = "utf-8";
if ($_POST['radiobutton'] == 'soccernet') // || $_POST['radiobutton'] == 'espnfc')
  $encoding = "windows-1252";

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $encoding?>">
</head>

<script>

<?php

 $players = '';
 $playersnums = '';
 $playerspositions = '';
 $playersnames = '';
 $playersnames2 = '';
 if (!empty($_GET['game_id']))
   {
     $sql = "SELECT G.TEAM_ID1, T1.TEAM_NAME as TEAM_NAME1, C1.SHORT_CODE as SHORT_CODE1,
		    G.TEAM_ID2, T2.TEAM_NAME as TEAM_NAME2, C2.SHORT_CODE as SHORT_CODE2
			FROM games G
				left join teams T1 on G.TEAM_ID1 = T1.TEAM_ID
				left join countries C1 on T1.COUNTRY = C1.ID
				left join teams T2 on G.TEAM_ID2 = T2.TEAM_ID
				left join countries C2 on T2.COUNTRY = C2.ID
			WHERE GAME_ID=".$_GET['game_id'];
//echo $sql;
     $db->query($sql);

///     $db->select("games", "TEAM_ID1, TEAM_ID2", "GAME_ID=".$_GET['game_id']);
     if (!$row = $db->nextRow()) {
     // ERROR! No such record. redirect to list
      $db->close();
     }
     else {
      // populate $PRESET_VARS with data so form class can use their values

      $teams = $row['TEAM_ID1'].",".$row['TEAM_ID2'];
      $teams_names = '"'.$row['TEAM_NAME1'].'","'.$row['TEAM_NAME2'].'"';
      $teams_short_names = '"'.$row['SHORT_CODE1'].'","'.$row['SHORT_CODE2'].'"';
      $team1 = $row['TEAM_ID1'];
      $team2 = $row['TEAM_ID2'];
      $sql = 'SELECT M.ID, M.USER_ID M_USER_ID, M.TEAM_ID M_TEAM_ID, M.NUM, M.POSITION_ID1,
               LOWER(U.FIRST_NAME) FIRST_NAME, LOWER(U.LAST_NAME) LAST_NAME, T.TEAM_NAME
        FROM members M, teams T, games G, busers U
        WHERE M.TEAM_ID IN ('.$team1.','.$team2.')
       	  AND G.GAME_ID='.$_GET['game_id'].'
          AND M.USER_ID=U.USER_ID
          AND M.TEAM_ID=T.TEAM_ID
          AND M.DATE_STARTED <= G.START_DATE
          AND (M.DATE_EXPIRED >= DATE_ADD(G.START_DATE, INTERVAL -1 WEEK) OR M.DATE_EXPIRED IS NULL)
        ORDER BY M.TEAM_ID, M.NUM+0';

      $db->query($sql);
      $c = 0;
      $prev = 0;
      $prevrow = 0;
      while ($row = $db->nextRow()) {
       if($prev!=$row['M_USER_ID'])
       {
        if ($prevrow == $row['M_TEAM_ID'])
         {
          $players[$row['M_TEAM_ID']] .= ",";
          $playersnames[$row['M_TEAM_ID']] .= ",";
          $playersnames2[$row['M_TEAM_ID']] .= ",";
          $playersnums[$row['M_TEAM_ID']] .= ",";
          $playerspositions[$row['M_TEAM_ID']] .= ",";
         }
        if (!isset($players[$row['M_TEAM_ID']]))
          $players[$row['M_TEAM_ID']] = $row['M_USER_ID'];
        else $players[$row['M_TEAM_ID']] .= $row['M_USER_ID'];
        if (!isset($playersnums[$row['M_TEAM_ID']])) {
          if (!empty($row['NUM']))
            $playersnums[$row['M_TEAM_ID']] = $row['NUM'];
          else $playersnums[$row['M_TEAM_ID']] = "0";
        } else {
          if (!empty($row['NUM']))
            $playersnums[$row['M_TEAM_ID']] .= $row['NUM'];
          else $playersnums[$row['M_TEAM_ID']] .= "0";
        }
        if (!isset($playerspositions[$row['M_TEAM_ID']]))
	  $playerspositions[$row['M_TEAM_ID']] = $row['POSITION_ID1'];
	else $playerspositions[$row['M_TEAM_ID']] .= $row['POSITION_ID1'];

        $name = trim($row['FIRST_NAME'])." ".trim($row['LAST_NAME']);
	$name = str_replace(".", "", $name);
	$name = str_replace("'", "", $name);
	$name = str_replace(" ", "_", trim($name));
        if (!isset($playersnames[$row['M_TEAM_ID']]))
          $playersnames[$row['M_TEAM_ID']] = "\"".$row['LAST_NAME']."\"";
        else $playersnames[$row['M_TEAM_ID']] .= "\"".$row['LAST_NAME']."\"";
        if (!isset($playersnames2[$row['M_TEAM_ID']]))
          $playersnames2[$row['M_TEAM_ID']] = "\"".$name."\"";
	else $playersnames2[$row['M_TEAM_ID']] .= "\"".$name."\"";
        $prev = $row['M_USER_ID'];
        $prevrow = $row['M_TEAM_ID'];
       }
       else
       {
        if ($prevrow == $row['M_TEAM_ID'])
         {
          $players[$row['M_TEAM_ID']] .= ",";
          $playersnames[$row['M_TEAM_ID']] .= ",";
          $playersnums[$row['M_TEAM_ID']] .= ",";
         }
        $players[$row['M_TEAM_ID']] .= $row['M_USER_ID'];
        if (!empty($row['NUM']))
          $playersnums[$row['M_TEAM_ID']] .= $row['NUM'];
        else $playersnums[$row['M_TEAM_ID']] .= "0";
        $name = trim($row['FIRST_NAME'])." ".trim($row['LAST_NAME']);
	$name = str_replace(".", "", $name);
	$name = str_replace("'", "", $name);
	$name = str_replace(" ", "_", trim($name));
        $playersnames[$row['M_TEAM_ID']] .= "\"".$row['LAST_NAME']."\"";
        $playersnames2[$row['M_TEAM_ID']] .= "\"".$name."\"";

        $prev = $row['M_USER_ID'];
        $prevrow = $row['M_TEAM_ID'];
       }
      }

      if (!empty($players[$team1]))
        $players[$team1] .= ",0";
      if (!empty($players[$team2]))
        $players[$team2] .= ",0";
      if (!empty($playersnames[$team1]))
        $playersnames[$team1] .= ",\"0\"";
      if (!empty($playersnames[$team2]))
        $playersnames[$team2] .= ",\"0\"";
      if (!empty($playersnames2[$team1]))
        $playersnames2[$team1] .= ",\"0\"";
      if (!empty($playersnames2[$team2]))
        $playersnames2[$team2] .= ",\"0\"";
      if (!empty($playersnums[$team1]))
        $playersnums[$team1] .= ",0";
      if (!empty($playersnums[$team2]))
        $playersnums[$team2] .= ",0";
      if (!empty($playerspositions[$team1]))
        $playerspositions[$team1] .= ",0";
      if (!empty($playerspositions[$team2]))
        $playerspositions[$team2] .= ",0";

     }
  $db->free();

   }
?>
var teams = new Array(<?php echo $teams?>);
var team_names = new Array(<?php echo $teams_names?>);
var team_short_names = new Array(<?php echo $teams_short_names?>);
var teamname = new Array(2);
var players = new Array(2);
 players[0] = new Array(<?php echo $players[$team1]?>);
 players[1] = new Array(<?php echo isset($players[$team2]) ? $players[$team2] : '' ?>);
var playersnames = new Array(2);
 playersnames[0] = new Array(<?php echo $playersnames[$team1]?>);
 playersnames[1] = new Array(<?php echo isset($playersnames[$team2]) ? $playersnames[$team2] : '' ?>);
var playersnames2 = new Array(2);
 playersnames2[0] = new Array(<?php echo $playersnames2[$team1]?>);
 playersnames2[1] = new Array(<?php echo isset($playersnames2[$team2]) ? $playersnames2[$team2] : '' ?>);
var playersnums = new Array(2);
 playersnums[0] = new Array(<?php echo $playersnums[$team1]?>);
 playersnums[1] = new Array(<?php echo isset($playersnums[$team2]) ? $playersnums[$team2] : '' ?>);
var teamobjects = new Array(2);
 teamobjects[0] = new playerobject(0);
 teamobjects[1] = new playerobject(1);
var playerobjects = new Array(2);
 playerobjects[0] = [];
 playerobjects[1] = [];
var playerspositions = new Array(2);
 playerspositions[0] = new Array(<?php echo $playerspositions[$team1]?>);
 playerspositions[1] = new Array(<?php echo isset($playerspositions[$team2]) ? $playerspositions[$team2] : '' ?>);
var goals = new Array(2);
 goals[0] = [];
 goals[1] = [];

function playerobject(index) {
  this.index=index
  this.team=-1
  this.position=0
  this.name=""
  this.namematch=0
  this.number=0
  this.goals=0
  this.goals_conceded=0
  this.own_goals=0
  this.goals_plus=0
  this.goals_minus=0
  this.minutes_played =0
  this.minutes_played_from = 0
  this.minutes_played_till = 0
  this.shots = 0
  this.shots_on_goal = 0
  this.penalty_shots = 0
  this.penalty_goals = 0
  this.yellow = 0
  this.red = 0
  this.fauls = 0
  this.unfauls = 0
  this.koeff = 0
  this.saves = 0
  this.penalty_saved = 0
  this.assists = 0
}

function goalobject(min, team_id, player_id, goal_type) {
  this.min=min
  this.team_id=team_id
  this.player_id=player_id
  // goal_type: 0 - goal, 1 - own goal
  this.goal_type=goal_type
}

function findPlayerObject(index, team_id, number) {
  for (var i=0; i < playerobjects[team_id].length; i++) {
   if (playerobjects[team_id][i].index == index) {
     return playerobjects[team_id][i];
   }
  }
  if (index > 0)
    playerobjects[team_id].push(new playerobject(index));
  else playerobjects[team_id].push(new playerobject(0 - number));
  return playerobjects[team_id][playerobjects[team_id].length - 1];
}

function findPlayer(lastname, index)
{
  for (var i=0; i<players[index].length; i++)
  {
    if (lastname.toLowerCase() == playersnames[index][i])
     {
      return i;
     }
  }
  return -1;
}

function findPlayer2(lastname, index)
{
  lastname = lastname.replace("-", " ");
  lastname = lastname.replace("'", "");
  lastname = lastname.replace(/ /g, "_");
  for (var i=0; i<players[index].length; i++)
  {
//alert(lastname.toLowerCase() + " " + playersnames2[index][i]);
    if (lastname.toLowerCase() == playersnames2[index][i])
     {
      return i;
     }
  }
  alert("cant find " + lastname);
  return -1;
}

function findPlayer(lastname, index, number)
{
  for (var i=0; i<players[index].length; i++)
  {
    if (number == playersnums[index][i] && number > 0)
     {
      return i;
     }
  }
  for (var i=0; i<players[index].length; i++)
  {
    var comp = playersnames[index][i].replace(" ", "");
    var ln = lastname.replace(" ", "");
    if ((lastname.toLowerCase() == comp
         || ln.toLowerCase() == comp)
        && number == playersnums[index][i])
     {
      return i;
     }
  }
  for (var i=0; i<players[index].length; i++)
  {
    var comp = playersnames[index][i].replace(" ", "");
    var ln = lastname.replace(" ", "");
    if (lastname.toLowerCase() == comp || ln.toLowerCase() == comp)
     {
      return i;
     }
  }
  return -1;
}

function fillField(teamindex, playerindex, value, key)
{
   var tempcell = window.opener.document.getElementById("" + key + teams[teamindex]+"-"+playerindex);
   if (tempcell != null) {
     tempcell.value = value;
   }
}

function calculateData() {
  for(i=0; i < 2; i++) {
   for (var j=0; j < playerobjects[i].length; j++)
     if (playerobjects[i][j].index > 0) {
       playerobjects[i][j].goals_plus = getGoals(playerobjects[i][j], i);
       playerobjects[i][j].goals_minus = getGoals(playerobjects[i][j], (i + 1) % 2);

       playerobjects[i][j].minutes_played = Math.abs(playerobjects[i][j].minutes_played_till - playerobjects[i][j].minutes_played_from);
       playerobjects[i][j].koeff = 2 + Math.floor((playerobjects[i][j].minutes_played_till - playerobjects[i][j].minutes_played_from)/30)
				+ playerobjects[i][j].goals_plus
				+ (playerobjects[i][j].goals - playerobjects[i][j].penalty_goals) * getGoalKoeff(playerobjects[i][j])
				+ 1 // participation
                                + getCleanSheetBonus(playerobjects[i][j])
				+ Math.floor(playerobjects[i][j].unfauls / 3)
				+ playerobjects[i][j].penalty_goals * 2
				+ playerobjects[i][j].assists * 3
				- playerobjects[i][j].goals_minus
				- playerobjects[i][j].goals_conceded
				- playerobjects[i][j].own_goals * 2
				- playerobjects[i][j].yellow
				- playerobjects[i][j].red * 3
				- Math.floor(playerobjects[i][j].fauls / 3)
				- (playerobjects[i][j].penalty_shots - playerobjects[i][j].penalty_goals) * 4;


       teamobjects[i].minutes_played += playerobjects[i][j].minutes_played;
       teamobjects[i].minutes_played_from = 0;
       teamobjects[i].minutes_played_till = <?php echo $match_length?>;
       teamobjects[i].goals += Number(playerobjects[i][j].goals);
       teamobjects[i].goals_conceded+= Number(playerobjects[i][j].goals_conceded);
       teamobjects[i].own_goals+= Number(playerobjects[i][j].own_goals);
       teamobjects[i].shots+= Number(playerobjects[i][j].shots);
       teamobjects[i].shots_on_goal+= Number(playerobjects[i][j].shots_on_goal);
       teamobjects[i].penalty_shots+= Number(playerobjects[i][j].penalty_shots);
       teamobjects[i].penalty_goals+= Number(playerobjects[i][j].penalty_goals);
       teamobjects[i].yellow+= Number(playerobjects[i][j].yellow);
       teamobjects[i].red+= Number(playerobjects[i][j].red);
       teamobjects[i].fauls+= Number(playerobjects[i][j].fauls);
       teamobjects[i].unfauls+= Number(playerobjects[i][j].unfauls);
       teamobjects[i].assists+= Number(playerobjects[i][j].assists);
       teamobjects[i].koeff+= Number(playerobjects[i][j].koeff);
     }
  }

  for(i=0; i < 2; i++) {
    teamobjects[(i + 1) % 2].goals = teamobjects[(i + 1) % 2].goals + teamobjects[i].own_goals;
    teamobjects[i].goals_conceded = teamobjects[(i + 1) % 2].goals;
  }

  for(i=0; i < 2; i++) {
   for (var j=0; j < playerobjects[i].length; j++)
     if (playerobjects[i][j].index > 0
	 && playerobjects[i][j].position == 35) {
        if (playerobjects[i][j].saves == 0)
   	  playerobjects[i][j].saves = teamobjects[(i + 1) % 2].shots_on_goal - teamobjects[(i + 1) % 2].goals;

        if (playerobjects[i][j].saves < 0)
	  playerobjects[i][j].saves = 0;
        if (playerobjects[i][j].goals_conceded == 0) {
  	  playerobjects[i][j].goals_conceded = teamobjects[i].goals_conceded;
	  playerobjects[i][j].koeff -= playerobjects[i][j].goals_conceded;
        }

//alert(playerobjects[i][j].goals_conceded);
	playerobjects[i][j].penalty_saved = teamobjects[(i + 1) % 2].penalty_shots - teamobjects[(i + 1) % 2].penalty_goals;
	playerobjects[i][j].koeff += Math.floor(playerobjects[i][j].saves / 3)
					+ playerobjects[i][j].penalty_saved * 3;

        teamobjects[i].saves+= Number(playerobjects[i][j].saves);
        teamobjects[i].koeff+= Number(playerobjects[i][j].penalty_saved);
     }
  }
}

function fillFields()
{
  for(i=0; i < 2; i++) {
    for (var j=0; j < playerobjects[i].length; j++)
     if (playerobjects[i][j].index > 0) {
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].minutes_played_from, "played_from_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].minutes_played_till, "played_till_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].minutes_played, "played_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].goals, "score_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].goals_conceded, "conceded_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].goals_plus, "pt3_thrown_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].goals_minus, "pt3_scored_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].own_goals, "own_goals_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].shots, "pt2_thrown_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].shots_on_goal, "pt2_scored_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].penalty_shots, "pt1_thrown_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].penalty_goals, "pt1_scored_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].yellow, "yellow_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].red, "red_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].fauls, "fauls_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].unfauls, "unfauls_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].koeff, "koeff_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].saves, "blocks_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].assists, "assists_")
       fillField(i, playerobjects[i][j].index, playerobjects[i][j].penalty_saved, "steals_")

     }

     fillField(i, "", teamobjects[i].minutes_played_from, "played_from_")
     fillField(i, "", teamobjects[i].minutes_played_till, "played_till_")
     fillField(i, "", teamobjects[i].minutes_played, "played_")
     fillField(i, "", teamobjects[i].goals, "score_")
     fillField(i, "", teamobjects[i].goals_conceded, "conceded_")
     fillField(i, "", teamobjects[i].goals_plus, "pt3_thrown_")
     fillField(i, "", teamobjects[i].goals_minus, "pt3_scored_")
     fillField(i, "", teamobjects[i].own_goals, "own_goals_")
     fillField(i, "", teamobjects[i].shots, "pt2_thrown_")
     fillField(i, "", teamobjects[i].shots_on_goal, "pt2_scored_")
     fillField(i, "", teamobjects[i].penalty_shots, "pt1_thrown_")
     fillField(i, "", teamobjects[i].penalty_goals, "pt1_scored_")
     fillField(i, "", teamobjects[i].yellow, "yellow_")
     fillField(i, "", teamobjects[i].red, "red_")
     fillField(i, "", teamobjects[i].fauls, "fauls_")
     fillField(i, "", teamobjects[i].unfauls, "unfauls_")
     fillField(i, "", teamobjects[i].koeff, "koeff_")
     fillField(i, "", teamobjects[i].saves, "blocks_")
     fillField(i, "", teamobjects[i].assists, "assists_")
     fillField(i, "", teamobjects[i].penalty_saved, "steals_")

   }
   fillGoalsField();
}

function drawTables() {
  var tabletext = new Array(2);
  for(i=0; i < 2; i++) {
    tabletext[i] = '';
    tabletext[i] += '<table width="100%" border="0" cellspacing="2" cellpadding="5" bgcolor="#cacaca">';
    tabletext[i] += '<tr bgcolor="#aaaaaa"><th>' + teamname[i] + '</th><th align="center">MNF</th><th align="center">MNT</th><th align="center">MN</th><th align="center">%2</th><th align="center">3T</th><th align="center">%3</th><th align="center">B</th><th align="center">%B</th><th align="center">AK</th><th align="center">RP</th><th align="center">BM</th><th align="center">PK</th><th align="center">KL</th></tr>';
    for (var j=0; j < playerobjects[i].length; j++) {
      // get team name
      if (playerobjects[i][j].team == i) {
        if (playerobjects[i][j].index > 0) {
          if (playerobjects[i][j].namematch == 0)
            tabletext[i] += '<tr bgcolor="#00ffff">';
          else if (j%2 == 1)
            tabletext[i] += '<tr bgcolor="#eaeaea">';
          else tabletext[i] += '<tr bgcolor="#dadada">';
        }
        else {
            tabletext[i] += '<tr bgcolor="#ff0000">';
        }

        tabletext[i] += "<td>"  + playerobjects[i][j].name + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].goals + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].goals_conceded + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].own_goals + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].goals_plus + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].goals_minus + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].minutes_played_from + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].minutes_played_till + "&nbsp;</td>";
        tabletext[i] += "<td>"  + (playerobjects[i][j].minutes_played_till - playerobjects[i][j].minutes_played_from) + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].shots + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].shots_on_goal + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].penalty_shots + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].penalty_goals + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].penalty_saved + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].saves + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].yellow + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].red + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].fauls + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].unfauls + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].assists + "&nbsp;</td>";
        tabletext[i] += "<td>"  + playerobjects[i][j].koeff + "&nbsp;</td>";

        tabletext[i] += "</tr>";
      }
    }
    tabletext[i] += "</table>";
  }
  return tabletext[0]+"<br>"+tabletext[1];
}

function getGoals(playerobj, type) {
 var count = 0;
//alert(goals[type].length);
 for (g = 0; g < goals[type].length; g++ ) {
   if (parseInt(playerobj.minutes_played_from) <= parseInt(goals[type][g].min)
       && parseInt(playerobj.minutes_played_till) >= parseInt(goals[type][g].min))
     count++;
//   else
//alert(playerobj.name + " " + playerobj.minutes_played_from + " " + goals[type][g].min) + playerobj.minutes_played_till;
 }
//alert(playerobj.name + count);
 return count;
}

function fillGoalsField() {
 var tempcell = window.opener.document.getElementById("goals");
 tempcell.value = "";
 for (j = 0; j < goals.length; j++ ) {
   for (i = 0; i < goals[j].length; i++ ) {
     if (tempcell != null) {
       tempcell.value += goals[j][i].min + ", " + teams[goals[j][i].team_id]+ ", " + players[j][goals[j][i].player_id] + ", " + goals[j][i].goal_type + "\n";
     }
   }
 }

}


function getCleanSheetBonus(playerobj) {
  if (playerobj.goals_minus == 0 && playerobj.minutes_played >= 44
     && playerobj.red == 0 && playerobj.yellow < 2) {
    switch(playerobj.position) {
	 case 35:
		return 4;
	 case 40:
		return 3;
	 case 60:
		return 2;
	 case 50:
		return 1;
	 default:
		return 0;
    }
  }
  return 0;
}

function getGoalKoeff(playerobj) {
    switch(playerobj.position) {
	 case 35:
		return 7;
	 case 40:
		return 6;
	 case 60:
		return 5;
	 case 50:
		return 4;
	 default:
		return 0;
    }
}

function getText(obj) {
   if(document.all){
      return obj.innerText;
   } else{
      return obj.textContent;
   }
}

function getLink(myVariable) {
  if (myVariable.match(/["]([^"]+)["]/)) {
    theLink=RegExp.$1;
  }
  else {
    theLink=myVariable;
  }
  return theLink;
}

function parse()
{
  var type='<?php echo $_POST['radiobutton'] ?>';
  if (type == 'fifa')
    parseFIFA();
  else if (type == 'fifa2')
    parseFIFA2();
  else if (type == 'soccernet')
    parseSoccernet();
  else if (type == 'espnfc')
    parseEspnfc();


}

function parseFIFA() {
  var tables = document.body.getElementsByTagName("TABLE");
  var uls = document.body.getElementsByTagName("UL");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");
  var divteam = new Array(2);
  var divsubsteam = new Array(2);
  var divstatsteam = new Array(2);
  var tableteam = new Array(2);
  var tempcell;

  //alert(tables.length);

  // get scorers
  var ulDivs = uls[0].getElementsByTagName("div");
//  alert(getText(ulDivs[0]));
  var liNodes = uls[0].getElementsByTagName("li");
  for(i=0; i < liNodes.length; i++) {
//   alert(getText(liNodes[i]));
   var scorer = getText(liNodes[i]);
   var scorer_original = getText(liNodes[i]);
   var min = scorer.substring(scorer.indexOf(")")+2, scorer.indexOf("'")).trim();
   if (min >  <?php echo $match_length?>)
     min = <?php echo $match_length?>;
   var team_name = scorer.substring(scorer.indexOf("(")+1, scorer.indexOf(")")).trim();
   scorer = scorer.substring(0, scorer.indexOf("(")).trim();
//   alert(scorer);
   var index = 0;
   if (team_short_names[1] == team_name)
     index = 1;

   scorer = scorer.replace("  ", " ");
   var playerindex = findPlayer2(scorer.replace(" ", "_").toLowerCase(), index);

   if (playerindex > -1) {
     var playerobj = findPlayerObject(players[index][playerindex], index, playersnums[index][playerindex]);
     if (scorer_original.indexOf("Own goal") > 0) {
       playerobj.own_goals += 1;
       var goalobj = new goalobject(min, (index + 1) % 2, playerindex, 1);
       goals[(index + 1) % 2].push(goalobj);
     }
     else {
       var goalobj = new goalobject(min, index, playerindex, 0);
       goals[index].push(goalobj);
     }
   }

  }

    // red cards
    liNodes = uls[6].getElementsByTagName("li");
    for (i=0; i < liNodes.length; i++) {
      var scorer = getText(liNodes[i]);
      var min = scorer.substring(scorer.indexOf(")")+2, scorer.indexOf("'")).trim();
      var team_name = scorer.substring(scorer.indexOf("(")+1, scorer.indexOf(")")).trim();
      scorer = scorer.substring(0, scorer.indexOf("(")).trim();
//   alert(scorer);
      var index = 0;
      if (team_short_names[1] == team_name)
        index = 1;
      scorer = scorer.replace("  ", " ");
      var playerindex = findPlayer2(scorer.replace(" ", "_").toLowerCase(), index);
      if (playerindex > -1) {
        var playerobj = findPlayerObject(players[index][playerindex], index, playersnums[index][playerindex]);
        playerobj.minutes_played_till = min;
      }

    }

// team 1 - table 14, team 2 - table 16
  divteam[0] = uls[1];
  divsubsteam[0] = uls[2];
  divteam[1] = uls[3];
  divsubsteam[1] = uls[4];
  tableteam[0] = tables[4];
  tableteam[1] = tables[5];
  var i = 0;
  for(i=0; i < 2; i++)
  {
    /// parse table 1
    // alert(tables[i].rows.length);
    // get team name
   // get players stats
    var lis = divteam[i].getElementsByTagName("li");
    for(j=0; j < lis.length; j++) {
          var player = getText(lis[j]);
          var playernumber = player.substring(player.indexOf("[") + 1, player.indexOf("]")).trim();
	  var additional_stuff = "";
          if (player.indexOf("(") > 0)
            additional_stuff = player.substring(player.indexOf("(") + 1, player.length);
          var playername = "";
          if (player.indexOf("(") > 0)
	    player = player.substring(player.indexOf("]") + 1, player.indexOf("(")).trim();
	  else player = player.substring(player.indexOf("]") + 1, player.length).trim();
	  var playerindex = findPlayer(player.replace(" ", "_").toLowerCase(), i, playernumber);
          var playerobj = findPlayerObject(players[i][playerindex], i, playernumber);
	  playerobj.name = player;
          playerobj.team = i;
	  playerobj.position = playerspositions[i][playerindex];
          var start_time = "0";
	  playerobj.minutes_played_from = start_time;

          var end_time = "<?php echo $match_length?>";
	  if (additional_stuff != "") {
            if (additional_stuff.indexOf("-") > -1) {
	      end_time = additional_stuff.substring(additional_stuff.indexOf("-")+1, additional_stuff.indexOf("'"));
	    }
          }
          if (playerobj.minutes_played_till == 0)
  	    playerobj.minutes_played_till = end_time;
     }

    var lis = divsubsteam[i].getElementsByTagName("li");
    for(j=0; j < lis.length; j++) {
          var player = getText(lis[j]);
          var playernumber = player.substring(player.indexOf("[") + 1, player.indexOf("]")).trim();
	  var additional_stuff = "";
          if (player.indexOf("(") > 0)
            additional_stuff = player.substring(player.indexOf("(") + 1, player.length);
          var playername = "";
          if (player.indexOf("(") > 0)
	    player = player.substring(player.indexOf("]") + 1, player.indexOf("(")).trim();
	  else player = player.substring(player.indexOf("]") + 1, player.length).trim();
          if (additional_stuff.indexOf("+") > -1) {
  	    var playerindex = findPlayer(player.replace(" ", "_").toLowerCase(), i, playernumber);
            var playerobj = findPlayerObject(players[i][playerindex], i, playernumber);
   	    playerobj.name = player;
            playerobj.team = i;
	    playerobj.position = playerspositions[i][playerindex];
            var start_time = additional_stuff.substring(additional_stuff.indexOf("+")+1, additional_stuff.indexOf("'"));
	    playerobj.minutes_played_from = start_time;

            if (playerobj.minutes_played_till == 0)
	      playerobj.minutes_played_till = "<?php echo $match_length?>";
          }
    }

    for(j=2; j < tableteam[i].rows.length - 1; j++) {
      if (tableteam[i].rows[j].cells.length > 1) {
        var playernumber = getText(tableteam[i].rows[j].cells[0]);
        var player = getText(tableteam[i].rows[j].cells[1]);
        var playerindex = findPlayer(player.replace(" ", "_").toLowerCase(), i, playernumber);
        var playerobj = findPlayerObject(players[i][playerindex], i, playernumber);
        playerobj.name = player;
        playerobj.team = i;
        playerobj.position = playerspositions[i][playerindex];

        var value = "0";
        var value2 = getText(tableteam[i].rows[j].cells[4]).trim();
        if (value2 != "")
          value = value2;
	playerobj.goals = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[5]).trim();
        if (value2 != "")
          value = value2;
	playerobj.goals_conceded = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[6]).trim();
        value2 = value2.substring(0, value2.indexOf("/"));
        if (value2 != "")
          value = value2;
	playerobj.shots_on_goal = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[6]).trim();
        value2 = value2.substring(value2.indexOf("/") + 1, value2.length);
        if (value2 != "")
          value = value2;
	playerobj.shots = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[11]).trim();
        if (value2 != "") {
     	  playerobj.yellow = 2;
        }
        else playerobj.yellow = 0;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[10]).trim();
        if (value2 != "" && playerobj.yellow == 0)
     	  playerobj.yellow = 1;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[12]).trim();
        if (value2 != "")
          value = value2;
  	playerobj.red = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[8]).trim();
        if (value2 != "")
          value = value2;
  	playerobj.fauls = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[9]).trim();
        if (value2 != "")
          value = value2;
  	playerobj.unfauls = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[7]).trim();
        value2 = value2.substring(0, value2.indexOf("/"));
        if (value2 != "")
          value = value2;
	playerobj.penalty_goals = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[7]).trim();
        value2 = value2.substring(value2.indexOf("/") + 1, value2.length);
        if (value2 != "")
          value = value2;
	playerobj.penalty_shots = value;

        playerobj.goals_plus = getGoals(playerobj, i);
        playerobj.goals_minus = getGoals(playerobj, (i + 1) % 2);
      }
    }

    // total
       var koeff = 0;
//       tabletext[i] += '<tr bgcolor="#bababa">';
//       tabletext[i] += "<td>KOMANDA</td>";

  }
  calculateData();
  fillFields();
  divas.innerHTML = drawTables();;
//  divassource.innerHTML='<table><tr><td><textarea id="tablecode" cols="50" rows="30">' + tabletext[0] + '</textarea></td><td><textarea id="tablecode2" cols="50" rows="30">' + tabletext[1] + '</textarea></td></tr></table>';

}

function parseFIFA2() {
  var tables = document.body.getElementsByTagName("TABLE");
  var uls = document.body.getElementsByTagName("UL");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");
  var divteam = new Array(2);
  var divsubsteam = new Array(2);
  var divstatsteam = new Array(2);
  var tableteam = new Array(2);
  var tempcell;

  // get scorers
  var ulDivs = uls[0].getElementsByTagName("div");
//  alert(getText(ulDivs[0]));
  var liNodes = uls[0].getElementsByTagName("li");
  for(i=0; i < liNodes.length; i++) {
//   alert(getText(liNodes[i]));
   var scorer = getText(liNodes[i]);
   var min = scorer.substring(scorer.indexOf(")")+2, scorer.indexOf("'")).trim();
   if (min > <?php echo $match_length?>)
     min = <?php echo $match_length?>;
   var team_name = scorer.substring(scorer.indexOf("(")+1, scorer.indexOf(")")).trim();
   scorer = scorer.substring(0, scorer.indexOf("(")).trim();
//   alert(scorer);
   var index = 0;
   if (team_short_names[1] == team_name)
     index = 1;
   scorer = scorer.replace("  ", " ");
   var playerindex = findPlayer2(scorer.replace(" ", "_").toLowerCase(), index);

   if (playerindex > -1) {
     var playerobj = findPlayerObject(players[index][playerindex], index, playersnums[index][playerindex]);
//     playerobj.name = player;
     playerobj.team = index;
     playerobj.position = playerspositions[index][playerindex];

//alert(players[index][playerindex]);
     if (scorer.indexOf("Own goal") > 0) {
       playerobj.own_goals += 1;
       var goalobj = new goalobject(min, (index + 1) % 2, playerindex, 1);
       goals[(index + 1) % 2].push(goalobj);
     }
     else {
       playerobj.goals += 1;
       var goalobj = new goalobject(min, index, playerindex, 0);
       goals[index].push(goalobj);
     }
   }
  }

  //cards
//  alert(getText(ulDivs[0]));
  liNodes = uls[5].getElementsByTagName("li");
  for(i=0; i < liNodes.length; i++) {
//   alert(getText(liNodes[i]));
   var scorer = getText(liNodes[i]);
   scorer = scorer.replace(", ", "");
   var min = scorer.substring(scorer.indexOf(")")+2, scorer.indexOf("'")).trim();
   var team_name = scorer.substring(scorer.indexOf("(")+1, scorer.indexOf(")")).trim();
   alert(team_name);
   scorer = scorer.substring(0, scorer.indexOf("(")).trim();
   var index = 1;
   if (team_short_names[0] == team_name)
     index = 0;
   if (team_short_names[1] == team_name)
     index = 1;

   scorer = scorer.replace("  ", " ");
   var playerindex = findPlayer2(scorer.replace(" ", "_").toLowerCase(), index);

   if (playerindex > -1) {
     var playerobj = findPlayerObject(players[index][playerindex], index, playersnums[index][playerindex]);
//     playerobj.name = player;
     playerobj.team = index;
     playerobj.position = playerspositions[index][playerindex];
     playerobj.yellow += 1;
//alert(players[index][playerindex]);
   }
  }


  liNodes = uls[6].getElementsByTagName("li");
  for(i=0; i < liNodes.length; i++) {
//   alert(getText(liNodes[i]));
   var scorer = getText(liNodes[i]);
   scorer = scorer.replace(", ", "");
   var min = scorer.substring(scorer.indexOf(")")+2, scorer.indexOf("'")).trim();
   var team_name = scorer.substring(scorer.indexOf("(")+1, scorer.indexOf(")")).trim();
   alert(team_name);
   scorer = scorer.substring(0, scorer.indexOf("(")).trim();
   var index = 1;
   if (team_short_names[0] == team_name)
     index = 0;
   if (team_short_names[1] == team_name)
     index = 1;

   scorer = scorer.replace("  ", " ");
   var playerindex = findPlayer2(scorer.replace(" ", "_").toLowerCase(), index);

   if (playerindex > -1) {
     var playerobj = findPlayerObject(players[index][playerindex], index, playersnums[index][playerindex]);
//     playerobj.name = player;
     playerobj.team = index;
     playerobj.position = playerspositions[index][playerindex];
     playerobj.red += 1;
//alert(players[index][playerindex]);
   }
  }

// team 1 - table 14, team 2 - table 16
  divteam[0] = uls[1];
  divsubsteam[0] = uls[2];
  divteam[1] = uls[3];
  divsubsteam[1] = uls[4];
  tableteam[0] = tables[2];
  tableteam[1] = tables[3];
  var i = 0;
  for(i=0; i < 2; i++)
  {
    /// parse table 1
    // alert(tables[i].rows.length);
    // get team name
   // get players stats
    var lis = divteam[i].getElementsByTagName("li");
    for(j=0; j < lis.length; j++) {
          var player = getText(lis[j]);
          var playernumber = player.substring(player.indexOf("[") + 1, player.indexOf("]")).trim();
	  var additional_stuff = "";
          if (player.indexOf("(") > 0)
            additional_stuff = player.substring(player.indexOf("(") + 1, player.length);
          var playername = "";
          if (player.indexOf("(") > 0)
	    player = player.substring(player.indexOf("]") + 1, player.indexOf("(")).trim();
	  else player = player.substring(player.indexOf("]") + 1, player.length).trim();
	  var playerindex = findPlayer(player.replace(" ", "_").toLowerCase(), i, playernumber);
          var playerobj = findPlayerObject(players[i][playerindex], i, playernumber);
	  playerobj.name = player;
          playerobj.team = i;
	  playerobj.position = playerspositions[i][playerindex];
          var start_time = "0";
	  playerobj.minutes_played_from = start_time;

          var end_time = "<?php echo $match_length?>";
	  if (additional_stuff != "") {
            if (additional_stuff.indexOf("-") > -1) {
	      end_time = additional_stuff.substring(additional_stuff.indexOf("-")+1, additional_stuff.indexOf("'"));
	    }
          }
	  playerobj.minutes_played_till = end_time;
          playerobj.goals_plus = getGoals(playerobj, i);
          playerobj.goals_minus = getGoals(playerobj, (i + 1) % 2);

     }

    var lis = divsubsteam[i].getElementsByTagName("li");
    for(j=0; j < lis.length; j++) {
          var player = getText(lis[j]);
          var playernumber = player.substring(player.indexOf("[") + 1, player.indexOf("]")).trim();
	  var additional_stuff = "";
          if (player.indexOf("(") > 0)
            additional_stuff = player.substring(player.indexOf("(") + 1, player.length);
          var playername = "";
          if (player.indexOf("(") > 0)
	    player = player.substring(player.indexOf("]") + 1, player.indexOf("(")).trim();
	  else player = player.substring(player.indexOf("]") + 1, player.length).trim();
          if (additional_stuff.indexOf("+") > -1) {
  	    var playerindex = findPlayer(player.replace(" ", "_").toLowerCase(), i, playernumber);
            var playerobj = findPlayerObject(players[i][playerindex], i, playernumber);
   	    playerobj.name = player;
            playerobj.team = i;
	    playerobj.position = playerspositions[i][playerindex];
            var start_time = additional_stuff.substring(additional_stuff.indexOf("+")+1, additional_stuff.indexOf("'"));
	    playerobj.minutes_played_from = start_time;
	    playerobj.minutes_played_till = "<?php echo $match_length?>";
            playerobj.goals_plus = getGoals(playerobj, i);
            playerobj.goals_minus = getGoals(playerobj, (i + 1) % 2);
          }
    }

/*    for(j=2; j < tableteam[i].rows.length - 2; j++) {
      if (tableteam[i].rows[j].cells.length > 1) {
        var playernumber = getText(tableteam[i].rows[j].cells[0]);
        var player = getText(tableteam[i].rows[j].cells[1]);
        var playerindex = findPlayer(player.replace(" ", "_").toLowerCase(), i, playernumber);
        var playerobj = findPlayerObject(players[i][playerindex], i, playernumber);
        playerobj.name = player;
        playerobj.team = i;
        playerobj.position = playerspositions[i][playerindex];

        var value = "0";
        var value2 = getText(tableteam[i].rows[j].cells[4]).trim();
        if (value2 != "")
          value = value2;
	playerobj.goals = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[5]).trim();
        if (value2 != "")
          value = value2;
	playerobj.conceded_goals = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[6]).trim();
        value2 = value2.substring(0, value2.indexOf("/"));
        if (value2 != "")
          value = value2;
	playerobj.shots_on_goal = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[6]).trim();
        value2 = value2.substring(value2.indexOf("/") + 1, value2.length);
        if (value2 != "")
          value = value2;
	playerobj.shots = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[11]).trim();
        if (value2 != "")
     	  playerobj.yellow = 2;
        else playerobj.yellow = 0;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[10]).trim();
        if (value2 != "" && playerobj.yellow == 0)
     	  playerobj.yellow = 1;
        else playerobj.yellow = 0

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[12]).trim();
        if (value2 != "")
          value = value2;
  	playerobj.red = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[8]).trim();
        if (value2 != "")
          value = value2;
  	playerobj.fauls = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[9]).trim();
        if (value2 != "")
          value = value2;
  	playerobj.unfauls = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[7]).trim();
        value2 = value2.substring(0, value2.indexOf("/"));
        if (value2 != "")
          value = value2;
	playerobj.penalty_goals = value;

        value = "0";
        value2 = getText(tableteam[i].rows[j].cells[7]).trim();
        value2 = value2.substring(value2.indexOf("/") + 1, value2.length);
        if (value2 != "")
          value = value2;
	playerobj.penalty_shots = value;

      }
    }*/

    // total
       var koeff = 0;
//       tabletext[i] += '<tr bgcolor="#bababa">';
//       tabletext[i] += "<td>KOMANDA</td>";

  }
  calculateData();
  fillFields();
  divas.innerHTML = drawTables();;
//  divassource.innerHTML='<table><tr><td><textarea id="tablecode" cols="50" rows="30">' + tabletext[0] + '</textarea></td><td><textarea id="tablecode2" cols="50" rows="30">' + tabletext[1] + '</textarea></td></tr></table>';

}

String.prototype.startsWith = function(str)
{return (this.match("^"+str)==str)}

function parseSoccernet() {
  var tables = document.body.getElementsByTagName("TABLE");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");
  var divteam = new Array(2);
  var divsubsteam = new Array(2);
  var divstatsteam = new Array(2);
  var tableteam = new Array(2);
  var tempcell;

  //alert(tables.length);

  tableteam[0] = tables[0];
  tableteam[1] = tables[1];
  var i = 0;
  for(i=0; i < 2; i++) {
    /// parse table 1
    // alert(tables[i].rows.length);
    // get team name
    // get players stats
    for(j=1; j < 12; j++) {
          var player = getText(tableteam[i].rows[j].cells[1]);
          var playernumber = getText(tableteam[i].rows[j].cells[0]);
          var imgs = tableteam[i].rows[j].cells[1].getElementsByTagName("IMG");

	  var playerindex = findPlayer(player.replace(" ", "_").toLowerCase(), i, playernumber);
          var playerobj = findPlayerObject(players[i][playerindex], i, playernumber);
	  playerobj.name = player.trim();
          playerobj.team = i;
	  playerobj.position = playerspositions[i][playerindex];

	  var norm_player = player.replace("-", " ").toLowerCase().trim();
	  norm_player = norm_player.replace(",", "");
          norm_player = norm_player.replace(/ /g, "_");
//alert(norm_player + playersnames2[i][playerindex] + norm_player.startsWith(playersnames2[i][playerindex]));
          if (norm_player.startsWith(playersnames2[i][playerindex]))
            playerobj.namematch= 1;
          var start_time = "0";
	  playerobj.minutes_played_from = start_time;

          var end_time = "<?php echo $match_length?>";
	  playerobj.minutes_played_till = end_time;

          soccernetGetAttribs(playerobj, tableteam[i].rows[j], i);

          for(m=0; m < imgs.length; m++) {
            var event = "" + imgs[m].onmouseover;
            event = event.substring(event.indexOf("Tip(") + 4, event.indexOf(", BGCOLOR"));
            var event_type = event.substring(event.indexOf(">") + 1, event.indexOf("</"));
            var time = event.substring(event.indexOf("(") + 1, event.indexOf(")") - 1);
            var name = event.substring(event.indexOf("Off: ") + 5, event.length-1);
            switch(event_type) {
              case "Goal":
              case "Goal - Header":
              case "Goal - Free-kick":
                var goalobj = new goalobject(time, i, playerindex, 0);
                goals[i].push(goalobj);
                break;
              case "Penalty - Scored":
                var goalobj = new goalobject(time, i, playerindex, 0);
                goals[i].push(goalobj);
		playerobj.penalty_goals++;
		playerobj.penalty_shots++;
                break;
              case "Own Goal":
	        playerobj.own_goals += 1;
	        var goalobj = new goalobject(time, (i + 1) % 2, playerindex, 1);
	        goals[(i + 1) % 2].push(goalobj);
                break;
              case "Red Card":
	        playerobj.minutes_played_till = time;
                break;
            }
          }
    }


    for(j=13; j < tableteam[i].rows.length; j++) {
          var imgs = tableteam[i].rows[j].cells[1].getElementsByTagName("IMG");
          for(m=0; m < imgs.length; m++) {
            var player = getText(tableteam[i].rows[j].cells[1]);
            var playernumber = getText(tableteam[i].rows[j].cells[0]);
//alert(player);
  	    var playerindex = findPlayer(player.replace(" ", "_").toLowerCase(), i, playernumber);
            var playerobj = findPlayerObject(players[i][playerindex], i, playernumber);
	    playerobj.name = player;
            playerobj.team = i;
	    playerobj.position = playerspositions[i][playerindex];

  	    var norm_player = player.replace("-", " ").toLowerCase().trim();
	    norm_player = norm_player.replace(",", "");
            norm_player = norm_player.replace(/ /g, "_");
//alert(norm_player + playersnames2[i][playerindex] + norm_player.startsWith(playersnames2[i][playerindex]));
            if (norm_player.startsWith(playersnames2[i][playerindex]))
              playerobj.namematch= 1;

            var end_time = "<?php echo $match_length?>";
            if (playerobj.minutes_played_till == 0)
  	      playerobj.minutes_played_till = end_time;
            soccernetGetAttribs(playerobj, tableteam[i].rows[j], i);

            var event = "" + imgs[m].onmouseover;
            event = event.substring(event.indexOf("Tip(") + 4, event.indexOf(", BGCOLOR"));
            var event_type = event.substring(event.indexOf(">") + 1, event.indexOf("</"));
            var time = event.substring(event.indexOf("(") + 1, event.indexOf(")") - 1);
            var name = event.substring(event.indexOf("Off: ") + 5, event.length-1);
            switch(event_type) {
              case "Goal":
              case "Goal - Header":
              case "Goal - Free-kick":
                var goalobj = new goalobject(time, i, playerindex, 0);
                goals[i].push(goalobj);
                break;
              case "Own Goal":
	        playerobj.own_goals += 1;
	        var goalobj = new goalobject(time, (i + 1) % 2, playerindex, 1);
	        goals[(i + 1) % 2].push(goalobj);
                break;
              case "Penalty - Scored":
                var goalobj = new goalobject(time, i, playerindex, 0);
                goals[i].push(goalobj);
		playerobj.penalty_goals++;
		playerobj.penalty_shots++;
                break;
              case "Substitution":
		playerobj.minutes_played_from = time;
                name = name.replace("  ", " ");
	        var playerindex2 = findPlayer2(name.replace(" ", "_").toLowerCase(), i);
	        if (playerindex2 > -1) {
	          var subsplayerobj = findPlayerObject(players[i][playerindex2], i, playersnums[i][playerindex2]);
 	          subsplayerobj.minutes_played_till = time;
	        }
                break;
              case "Red Card":
	        playerobj.minutes_played_till = time;
                if (playerobj.minutes_played_from == 0)
  	          playerobj.minutes_played_from = time;
                break;
            }
          }
       }
  }

  calculateData();
  fillFields();
  divas.innerHTML = drawTables();
}

function soccernetGetAttribs(playerobj, row, i) {

        var value = "0";
        var value2 = getText(row.cells[4]).trim();
        if (value2 != "-")
          value = value2;
	playerobj.goals = value;

        value = "0";
        value2 = getText(row.cells[3]).trim();
        if (value2 != "-")
          value = value2;
	playerobj.shots_on_goal = value;

        value = "0";
        value2 = getText(row.cells[2]).trim();
        if (value2 != "-")
          value = value2;
	playerobj.shots = value;

        value = "0";
        value2 = getText(row.cells[10]).trim();
        if (value2 != "-")
          value = value2;
        playerobj.yellow = value;

        value = "0";
        value2 = getText(row.cells[11]).trim();
        if (value2 != "-")
          value = value2;
  	playerobj.red = value;

        value = "0";
        value2 = getText(row.cells[8]).trim();
        if (value2 != "-")
          value = value2;
  	playerobj.fauls = value;

        value = "0";
        value2 = getText(row.cells[7]).trim();
        if (value2 != "-")
          value = value2;
  	playerobj.unfauls = value;

        value = "0";
        value2 = getText(row.cells[9]).trim();
        if (value2 != "-")
          value = value2;
  	playerobj.saves = value;

        value = "0";
        value2 = getText(row.cells[5]).trim();
        if (value2 != "-")
          value = value2;
  	playerobj.assists = value;
}

function parseEspnfc() {
  var tables = document.body.getElementsByTagName("TABLE");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");
  var divteam = new Array(2);
  var divsubsteam = new Array(2);
  var divstatsteam = new Array(2);
  var tableteam = new Array(2);
  var tempcell;

//alert(tables.length);

  tableteam[0] = tables[0];
  tableteam[1] = tables[1];
  var i = 0;
  for(i=0; i < 2; i++) {
    /// parse table 1
    // alert(tables[i].rows.length);
    // get team name
    // get players stats
    for(j=2; j < 13; j++) {
          var player = getText(tableteam[i].rows[j].cells[2]);
          var playernumber = getText(tableteam[i].rows[j].cells[1]);
          var imgs = tableteam[i].rows[j].cells[2].getElementsByTagName("DIV");

          player = player.replace("  ", " ");
          player = player.trim();  
	  var playerindex = findPlayer(player.replace(" ", "_").toLowerCase(), i, playernumber);
          var playerobj = findPlayerObject(players[i][playerindex], i, playernumber);
	  playerobj.name = player.trim();
          playerobj.team = i;
	  playerobj.position = playerspositions[i][playerindex];

	  var norm_player = player.replace("-", " ").toLowerCase().trim();
	  norm_player = norm_player.replace(",", "");
          norm_player = norm_player.replace(/ /g, "_");
//alert(norm_player + playersnames2[i][playerindex] + norm_player.startsWith(playersnames2[i][playerindex]));
          if (norm_player.startsWith(playersnames2[i][playerindex]))
            playerobj.namematch= 1;
          var start_time = "0";
	  playerobj.minutes_played_from = start_time;

          var end_time = "<?php echo $match_length?>";
	  playerobj.minutes_played_till = end_time;

          espnfcGetAttribs(playerobj, tableteam[i].rows[j], i);

          for(m=0; m < imgs.length; m++) {
            var event = "" + imgs[m].onmouseover;
            event = event.substring(event.indexOf("Tip(") + 4, event.indexOf(");") + 2);
            var event_type = event.substring(event.indexOf(">") + 1, event.indexOf("</"));
            var subevent = event;
	    subevent = subevent.substring(subevent.indexOf("</") + 2, subevent.indexOf(");")+2);
            var time = subevent.substring(subevent.indexOf("-") + 2, subevent.indexOf("\'")-1);
            if (time.indexOf("+") > 0)
              time = time.substring(0, time.indexOf("+") - 1);
            var name = subevent.substring(subevent.indexOf("Off: ") + 5, subevent.length-3);
            switch(event_type) {
              case "Goal":
              case "Goal - Header":
              case "Goal - Free-kick":
                var goalobj = new goalobject(time, i, playerindex, 0);
                goals[i].push(goalobj);
                break;
              case "Penalty - Scored":
                var goalobj = new goalobject(time, i, playerindex, 0);
                goals[i].push(goalobj);
		playerobj.penalty_goals++;
		playerobj.penalty_shots++;
                break;
              case "Own Goal":
	        playerobj.own_goals += 1;
	        var goalobj = new goalobject(time, (i + 1) % 2, playerindex, 1);
	        goals[(i + 1) % 2].push(goalobj);
                break;
              case "Red Card":
	        playerobj.minutes_played_till = time;
                break;
            }
          }
    }


    for(j=15; j < tableteam[i].rows.length; j++) {
          var imgs = tableteam[i].rows[j].cells[2].getElementsByTagName("DIV");
          for(m=0; m < imgs.length; m++) {
            var player = getText(tableteam[i].rows[j].cells[2]);
            var playernumber = getText(tableteam[i].rows[j].cells[1]);
//alert(player);
  	    var playerindex = findPlayer(player.replace(" ", "_").toLowerCase(), i, playernumber);
            var playerobj = findPlayerObject(players[i][playerindex], i, playernumber);
	    playerobj.name = player;
            playerobj.team = i;
	    playerobj.position = playerspositions[i][playerindex];

  	    var norm_player = player.replace("-", " ").toLowerCase().trim();
	    norm_player = norm_player.replace(",", "");
            norm_player = norm_player.replace(/ /g, "_");
//alert(norm_player + playersnames2[i][playerindex] + norm_player.startsWith(playersnames2[i][playerindex]));
            if (norm_player.startsWith(playersnames2[i][playerindex]))
              playerobj.namematch= 1;

            var end_time = "<?php echo $match_length?>";

            if (playerobj.minutes_played_till == 0)
	      playerobj.minutes_played_till = end_time;
            espnfcGetAttribs(playerobj, tableteam[i].rows[j], i);

            var event = "" + imgs[m].onmouseover;
            event = event.substring(event.indexOf("Tip(") + 4, event.indexOf(");")+2);
            var event_type = event.substring(event.indexOf(">") + 1, event.indexOf("</"));
            var subevent = event;
	    subevent = subevent.substring(subevent.indexOf("</") + 2, subevent.indexOf(");")+2);
            var time = subevent.substring(subevent.indexOf("-") + 2, subevent.indexOf("\'")-1);
            if (time.indexOf("+") > 0)
              time = time.substring(0, time.indexOf("+") - 1);
            var name = subevent.substring(subevent.indexOf("Off: ") + 5, subevent.length-3);
            switch(event_type) {
              case "Goal":
              case "Goal - Header":
              case "Goal - Free-kick":
                var goalobj = new goalobject(time, i, playerindex, 0);
                goals[i].push(goalobj);
                break;
              case "Own Goal":
	        playerobj.own_goals += 1;
	        var goalobj = new goalobject(time, (i + 1) % 2, playerindex, 1);
	        goals[(i + 1) % 2].push(goalobj);
                break;
              case "Penalty - Scored":
                var goalobj = new goalobject(time, i, playerindex, 0);
                goals[i].push(goalobj);
		playerobj.penalty_goals++;
		playerobj.penalty_shots++;
                break;
              case "Substitution":
		playerobj.minutes_played_from = time;
                name = name.replace("  ", " ");
                name = name.trim();
	        var playerindex2 = findPlayer2(name.replace(" ", "_").toLowerCase(), i);
	        if (playerindex2 > -1) {
	          var subsplayerobj = findPlayerObject(players[i][playerindex2], i, playersnums[i][playerindex2]);
 	          subsplayerobj.minutes_played_till = time;
	        }
                break;
              case "Red Card":
	        playerobj.minutes_played_till = time;
                if (playerobj.minutes_played_from == 0)
  	          playerobj.minutes_played_from = time;
                break;
            }
          }
       }
  }

  calculateData();
  fillFields();
  divas.innerHTML = drawTables();
}

function espnfcGetAttribs(playerobj, row, i) {

        var value = "0";
        var value2 = getText(row.cells[5]).trim();
        if (value2 != "-")
          value = value2;
	playerobj.goals = value;

        value = "0";
        value2 = getText(row.cells[4]).trim();
        if (value2 != "-")
          value = value2;
	playerobj.shots_on_goal = value;

        value = "0";
        value2 = getText(row.cells[3]).trim();
        if (value2 != "-")
          value = value2;
	playerobj.shots = value;

        value = "0";
        value2 = getText(row.cells[11]).trim();
        if (value2 != "-")
          value = value2;
        playerobj.yellow = value;

        value = "0";
        value2 = getText(row.cells[12]).trim();
        if (value2 != "-")
          value = value2;
  	playerobj.red = value;

        value = "0";
        value2 = getText(row.cells[9]).trim();
        if (value2 != "-")
          value = value2;
  	playerobj.fauls = value;

        value = "0";
        value2 = getText(row.cells[8]).trim();
        if (value2 != "-")
          value = value2;
  	playerobj.unfauls = value;

        value = "0";
        value2 = getText(row.cells[10]).trim();
        if (value2 != "-")
          value = value2;
  	playerobj.saves = value;

        value = "0";
        value2 = getText(row.cells[6]).trim();
        if (value2 != "-")
          value = value2;
  	playerobj.assists = value;
}


</script>

<body>
<form action="" method="post" name="form1">
1) URL
  <input type="text" name="url" size="90">
  <input type="text" name="match_length" size="2" value="90">
  <input type="submit" name="Submit" value="Submit"><br>
  FIFA<input name="radiobutton" type="radio" value="fifa" checked>
  FIFA2<input name="radiobutton" type="radio" value="fifa2">
  Soccernet<input name="radiobutton" type="radio" value="soccernet">
  Espnfc<input name="radiobutton" type="radio" value="espnfc">
</form>
2) <input name="parsetable" type="button" onClick="parse()" value="Paimti lenteles">

<?php
flush ();
if (!empty($_POST['url']))
{
$fetch_domain = parse_url($_POST['url']);
$fetch_domain = $fetch_domain['host'];
$button = $_POST['radiobutton'];

//$socket_handle = fsockopen($fetch_domain, 80, $error_nr, $error_txt,30);
echo $fetch_domain;
$times = 1;
$code = '';
if ($fetch_domain == "www.fifa.com" && $button == "fifa")
{
  $pradzia = "<ul><div class=\"bold medium\">Goals scored</div>";
  $pabaiga = "<table summary=\"xslext:Translate('legend')\">";
  $code .= getPage($_POST['url'], $pradzia, $pabaiga);
}
else if ($fetch_domain == "www.fifa.com" && $button == "fifa2")
{
  $pradzia = "<ul><div class=\"bold medium\">Goals scored</div>";
  $pabaiga = "<table summary=\"xslext:Translate('legend')\">";
  $code .= getPage($_POST['url'], $pradzia, $pabaiga);
}
else if ($button == "soccernet")
{
  $pradzia = "<div id=\"homeTeamPlayerStats\" class=\"ui-tabs-panel\">";
  $pabaiga = "<div style=\"border: 1px solid #C6C6C6; padding: 3px 7px 3px 7px; margin-top: 7px; font-size: 11px;\">";
  $url = $_POST['url'];
  $code .= getPage($url, $pradzia, $pabaiga);
/*  $url = str_replace("stats", "overview", $url);
  $pradzia = "<!-- begin goal scorers -->";
  $pabaiga = "<!-- end soccer goal scorers -->";
  $code .= getPage($url, $pradzia, $pabaiga);*/

  $code = str_replace("none", "block", $code);

  $code = strtr($code,
       "\xA1\xAA\xBA\xBF\xC0\xC1\xC2\xC3\xC5\xC7
       \xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1
       \xD2\xD3\xD4\xD5\xD6\xD8\xD9\xDA\xDB\xDD\xE0
       \xE1\xE2\xE3\xE5\xE7\xE8\xE9\xEA\xEB\xEC
       \xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF8
       \xF9\xFA\xFB\xFC\xFD\xFF",
       "!ao?AAAAAC
       EEEEIIIIDN
       OOOOOOUUUYa
       aaaaceeeei
       iiidnoooooo
       uuuuyy");
  $code = str_replace("\xDF", "ss", $code);
}
else if ($button == "espnfc")
{
  $pradzia = "<h1 id=\"home-team\" class=\"heading alt\">";
  $pabaiga = "<div class=\"span-6 column last\">";
  $url = $_POST['url'];
  $code .= getPage($url, $pradzia, $pabaiga);
/*  $url = str_replace("stats", "overview", $url);
  $pradzia = "<!-- begin goal scorers -->";
  $pabaiga = "<!-- end soccer goal scorers -->";
  $code .= getPage($url, $pradzia, $pabaiga);*/

  $code = str_replace("none", "block", $code);
//echo $code;
$code = iconv("UTF-8", "ISO-8859-1", $code);
//echo $code;
  $code = strtr($code,
       "\xA1\xAA\xBA\xBF\xC0\xC1\xC2\xC3\xC5\xC7
       \xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1
       \xD2\xD3\xD4\xD5\xD6\xD8\xD9\xDA\xDB\xDD\xE0
       \xE1\xE2\xE3\xE5\xE7\xE8\xE9\xEA\xEB\xEC
       \xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF8
       \xF9\xFA\xFB\xFC\xFD\xFF",
       "!ao?AAAAAC
       EEEEIIIIDN
       OOOOOOUUUYa
       aaaaceeeei
       iiidnoooooo
       uuuuyy");
  $code = str_replace("\xDF", "ss", $code);
//echo $code."----";

}

/*if(!$socket_handle) {
echo "Negaliu prisijungti prie ".$fetch_domain;
exit;
} */

  print "<body><div id='newtable'></div><div id='newtablesource'></div>".$code."</body>";
}
?>
</body>
</html>