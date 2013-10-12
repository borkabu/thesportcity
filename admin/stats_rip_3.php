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
if ($_POST['radiobutton'] == 'soccernet')
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
 $playersnames = '';
 $playersnames2 = '';
 if (!empty($_GET['game_id']))
   {
      // populate $PRESET_VARS with data so form class can use their values
      
      $sql = "SELECT DISTINCT M.ID, M.USER_ID M_USER_ID, M.TEAM_ID M_TEAM_ID, M.NUM, 
               U.FIRST_NAME, U.LAST_NAME, T.TEAM_NAME
	        FROM busers U, team_seasons TS, teams T, games_races G, members M
	        WHERE TS.SEASON_ID=G.SEASON_ID
		  AND M.TEAM_ID =TS.TEAM_ID
        	  AND G.GAME_ID=".$_GET['game_id']."
        	  AND M.USER_ID=U.USER_ID
	          AND M.TEAM_ID=T.TEAM_ID
        	  AND M.DATE_STARTED <= G.START_DATE 
	          AND (M.DATE_EXPIRED >= DATE_ADD(G.START_DATE, INTERVAL -1 WEEK) OR M.DATE_EXPIRED IS NULL)
	        ORDER BY M.TEAM_ID, M.NUM+0";
      $db->query($sql);
      $c = 0;
      $prev = 0; 
      $prevrow = 0;   
      $pre = '';  
      while ($row = $db->nextRow()) {
        if (!isset($players))
          $players = $pre.$row['M_USER_ID'];
        else $players .= $pre.$row['M_USER_ID'];
        if (!isset($playersnums)) {
          if (!empty($row['NUM']))
            $playersnums = $pre.$row['NUM'];
          else $playersnums = $pre."0";
        } else {
          if (!empty($row['NUM']))
            $playersnums .= $pre.$row['NUM'];
          else $playersnums .= $pre."0";
        }

        $name = trim($row['FIRST_NAME'])." ".trim($row['LAST_NAME']);
	$name = str_replace(".", "", $name);
	$name = str_replace("'", "", $name);
	$name = str_replace(" ", "_", trim($name));
        if (!isset($playersnames))
          $playersnames = $pre."\"".$row['LAST_NAME']."\"";
        else $playersnames .= $pre."\"".$row['LAST_NAME']."\"";
        if (!isset($playersnames2))
          $playersnames2 = $pre."\"".$name."\"";
	else $playersnames2 .= $pre."\"".$name."\"";
        $pre = ",";
      }

      if (!empty($players))
        $players .= ",0";
      if (!empty($playersnames))
        $playersnames .= ",\"0\"";
      if (!empty($playersnames2))
        $playersnames2 .= ",\"0\"";
      if (!empty($playersnums))
        $playersnums .= ",0";
      if (!empty($playerspositions))
        $playerspositions .= ",0";
     
   }

?>
var players = new Array(<?php echo $players?>);
var playersnames = new Array(<?php echo $playersnames?>);
var playersnames2 = new Array(<?php echo $playersnames2?>);
var playersnums = new Array(<?php echo $playersnums?>);

function findPlayer(lastname, number)
{
  for (var i=0; i<players.length; i++)
  {
    if (number == playersnums[i] && number > 0)
     {
      return i;
     }
  }
  for (var i=0; i<players.length; i++)
  {
    var comp = playersnames[i].replace(" ", "");
    var ln = lastname.replace(" ", "");
    if ((lastname.toLowerCase() == comp 
         || ln.toLowerCase() == comp)
        && number == playersnums[i])
     {
      return i;
     }
  }
  for (var i=0; i<players.length; i++)
  {
    var comp = playersnames[i].replace(" ", "");
    var ln = lastname.replace(" ", "");
    if (lastname.toLowerCase() == comp || ln.toLowerCase() == comp)
     {
      return i;
     }
  }
  return -1;
} 
   
function fillField(playerindex, value, key)
{
   var tempcell = window.opener.document.getElementById("" + key + players[playerindex]);
   if (tempcell != null) {
     tempcell.value = value;
   }
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
          if (j%2 == 1) 
            tabletext[i] += '<tr bgcolor="#eaeaea">';
          else tabletext[i] += '<tr bgcolor="#dadada">';
        }
        else tabletext[i] += '<tr bgcolor="#ff0000">';
  
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

function getText(obj) {
   if(document.all){
      return obj.innerText;
   } else{
      return obj.textContent;
   } 
}

function parse()
{
  var type='<?php echo $_POST['radiobutton'] ?>';
  if (type == 'f1')
    parseF1();

}

function parseF1() {
  var tables = document.body.getElementsByTagName("TABLE");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");
  var divteam = new Array(2);
  var divsubsteam = new Array(2);
  var divstatsteam = new Array(2);
  var tempcell;

//  alert(tables[0].rows.length);  

  for (j=1; j < tables[0].rows.length; j++) {
     var player = getText(tables[0].rows[j].cells[2]);
     var playernumber = getText(tables[0].rows[j].cells[1]);
//alert(player + playernumber);  
     var playerindex = findPlayer(player.replace(" ", "_").toLowerCase(), playernumber);
     fillField(playerindex, j, "place_");
     fillField(playerindex, getText(tables[0].rows[j].cells[7]), "score_");
     fillField(playerindex, getText(tables[0].rows[j].cells[0]), "note_");
     var note = getText(tables[0].rows[j].cells[0]);
     if (note == "Ret")
       fillField(playerindex, (26 - j)/2, "koeff_");
     else if (note == "DNS")
       fillField(playerindex, 0, "koeff_");
     else fillField(playerindex, 26 - j, "koeff_");

  }
//  fillFields();
  divas.innerHTML = drawTables();; 
//  divassource.innerHTML='<table><tr><td><textarea id="tablecode" cols="50" rows="30">' + tabletext[0] + '</textarea></td><td><textarea id="tablecode2" cols="50" rows="30">' + tabletext[1] + '</textarea></td></tr></table>';

}

</script>

<body>
<form action="" method="post" name="form1">
1) URL 
  <input type="text" name="url" size="90">
  <input type="text" name="match_length" size="2" value="90">
  <input type="submit" name="Submit" value="Submit"><br>
  Formula1.com<input name="radiobutton" type="radio" value="f1" checked>
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
if ($fetch_domain == "www.formula1.com" && $button == "f1")
{ 
  $pradzia = '<table class="raceResults" cellpadding="0" cellspacing="0" summary="">'; 
  $pabaiga = "</table>"; 
  $code .= getPage($_POST['url'], $pradzia, $pabaiga);
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