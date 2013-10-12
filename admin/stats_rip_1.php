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
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>

<script>

<?php

 $players = '';
 $playersnums = '';
 $playersnames = '';
 $playersnames2 = '';
 if (!empty($_GET['game_id']))
   {
     $db->select("games", "TEAM_ID1, TEAM_ID2", "GAME_ID=".$_GET['game_id']);
     if (!$row = $db->nextRow()) {
     // ERROR! No such record. redirect to list
      $db->close();
     }
     else {
      // populate $PRESET_VARS with data so form class can use their values
      
      $teams = $row['TEAM_ID1'].",".$row['TEAM_ID2'];
      $team1 = $row['TEAM_ID1'];
      $team2 = $row['TEAM_ID2'];
      $sql = 'SELECT M.ID, M.USER_ID M_USER_ID, M.TEAM_ID M_TEAM_ID, M.NUM, 
               LOWER(U.FIRST_NAME) FIRST_NAME, LOWER(U.LAST_NAME) LAST_NAME, T.TEAM_NAME
        FROM members M, teams T, busers U
        WHERE M.TEAM_ID IN ('.$team1.','.$team2.') 
          AND M.USER_ID=U.USER_ID
          AND M.TEAM_ID=T.TEAM_ID
          AND M.DATE_STARTED <= NOW()
          AND (M.DATE_EXPIRED >= NOW() OR M.DATE_EXPIRED IS NULL)
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

     }
  $db->free();

   }
?>
var teams = new Array(<?php echo $teams?>);
var players = new Array(2);
players[0] = new Array(<?php echo $players[$team1]?>);
players[1] = new Array(<?php echo $players[$team2]?>);
var playersnames = new Array(2);
playersnames[0] = new Array(<?php echo $playersnames[$team1]?>);
playersnames[1] = new Array(<?php echo $playersnames[$team2]?>);
var playersnames2 = new Array(2);
playersnames2[0] = new Array(<?php echo $playersnames2[$team1]?>);
playersnames2[1] = new Array(<?php echo $playersnames2[$team2]?>);
var playersnums = new Array(2);
playersnums[0] = new Array(<?php echo $playersnums[$team1]?>);
playersnums[1] = new Array(<?php echo $playersnums[$team2]?>);

function findPlayer(lastname, index)
{
//alert(lastname);
  for (var i=0; i<players[index].length; i++)
  {
//alert(lastname.toLowerCase());
//alert(playersnames[index][i]);
    if (lastname.toLowerCase() == playersnames[index][i])
     {
      return players[index][i];
     }
  }
  return 0;
} 

function findPlayer2(lastname, index)
{
//alert(lastname);
  for (var i=0; i<players[index].length; i++)
  {
//alert(lastname.toLowerCase());
//alert(playersnames[index][i]);
    if (lastname.toLowerCase() == playersnames2[index][i])
     {
      return players[index][i];
     }
  }
  return 0;
} 

function findPlayer(lastname, index, number)
{


  for (var i=0; i<players[index].length; i++)
  {
//alert(lastname + number + playersnums[index][i]);
    if (number == playersnums[index][i] && number > 0)
     {
      return players[index][i];
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
//alert(playersnums[index][i]);
      return players[index][i];
     }
  }
  for (var i=0; i<players[index].length; i++)
  {
//alert(lastname.toLowerCase());
//alert(playersnames[index][i]);
    var comp = playersnames[index][i].replace(" ", "");
    var ln = lastname.replace(" ", "");
    if (lastname.toLowerCase() == comp || ln.toLowerCase() == comp)
     {
      return players[index][i];
     }
  }

  return 0;
} 

function fillField(teamindex, playerindex, value, key)
{
   var tempcell = window.opener.document.getElementById("" + key + teams[teamindex]+"-"+playerindex);
   if (tempcell != null) {
     if (value != 0) {
//       alert(value);    
       value  = value.toString().replace(/^\s+|\s+$/g, '') ;
     }
//alert(value);    
     if (value == '')
       value = "0";
     tempcell.value = value;
   }
}


function getText(obj) {
   var value = "";
   if(document.all){
      value = obj.innerText.replace(/^\s+|\s+$/g, '') ;
      return value;
   } else{
      value = obj.textContent.replace(/^\s+|\s+$/g, '') ;
      return value;
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
  if (type == 'nba.com')
    parseNBA();
  else if (type == 'euroleague.net')  
         parseEuroleague() ;
  else if (type == 'lkl.lt')  
         parseLKL() ;
  else if (type == 'athens2004.com')  
         parseAthens() ;
  else if (type == 'eurobasket2005.com')  
         parseEurobasket2005() ;
  else if (type == 'bbl.net')  
         parseBBL() ;
  else if (type == 'fiba.com')  
         parseFiba() ;
  else if (type == 'eurobasket2007.org')  
         parseEurobasket2007() ;
  else if (type == 'eurobasket2011.com' )  
         parseEurobasket2011() ;


}

function parseFiba() {
  var tables = document.body.getElementsByTagName("TABLE");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");
  var tabletext = new Array(2);
  var teamname = new Array(2);
  var tableteam = new Array(2);
  var tempcell;
//  alert(tables.length);  

// team 1 - table 14, team 2 - table 16
  teamname[0] = tables[0].rows[0].cells[0].innerText;
  teamname[1] = tables[1].rows[0].cells[0].innerText;

  tableteam[0] = tables[0];
  tableteam[1] = tables[1];
  var i = 0;
  for(i=0; i < 2; i++)
  {
    /// parse table 1
  //   alert(tables[i].rows.length);
    tabletext[i] = '';
    // get team name
    tabletext[i] += '<table width="100%" border="0" cellspacing="2" cellpadding="5" bgcolor="#cacaca">';

    tabletext[i] += '<tr bgcolor="#aaaaaa"><th>' + teamname[i] + '</th><th align="center">TÐ</th><th align="center">MN</th><th align="center">2T</th><th align="center">%2</th><th align="center">3T</th><th align="center">%3</th><th align="center">B</th><th align="center">%B</th><th align="center">AK</th><th align="center">RP</th><th align="center">BM</th><th align="center">PK</th><th align="center">KL</th></tr>';
   // get players stats
    for(j=2; j < tableteam[i].rows.length - 2; j++)
     {
      if (true)
      {
      // change names to Capital
//alert(getText(tableteam[i].rows[j].cells[0]));

       var playername = getText(tableteam[i].rows[j].cells[1]);
//alert(playername);

       var playernum = getText(tableteam[i].rows[j].cells[0]);
       var playerindex = findPlayer(playername, i, playernum);
//alert(playernum);
//alert(playerindex);
//alert(playername);
       if (playerindex > 0) {
         if (j%2 == 1) 
           tabletext[i] += '<tr bgcolor="#eaeaea">';
         else tabletext[i] += '<tr bgcolor="#dadada">';
       }
       else tabletext[i] += '<tr bgcolor="#ff0000">';

       tabletext[i] += "<td>" + playername + "</td>";

       // points 
      if (getText(tableteam[i].rows[j].cells[2]) != "0" 
          && getText(tableteam[i].rows[j].cells[2]) != "Did not play" ) {
        var points = getText(tableteam[i].rows[j].cells[19]);
        fillField(i, playerindex, points, "score_");
        var played = getText(tableteam[i].rows[j].cells[2]);
        fillField(i, playerindex, played, "played_");

        var koeff = 0;
        koeff = koeff + parseInt(points);

        tabletext[i] += '<td align="center">' + points + "</td>";
        tabletext[i] += '<td align="center">' + played + "</td>";
        var scored = 0;
        var attempted = 0;
        var twopts = getText(tableteam[i].rows[j].cells[5]);
        scored = twopts.substring(0, twopts.indexOf("/"));
        attempted = twopts.substring(twopts.indexOf("/")+1, twopts.length);
        fillField(i, playerindex, scored, "pt2_scored_");
        tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
        fillField(i, playerindex, attempted, "pt2_thrown_");
        if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
        else tabletext[i] += '<td align="center">-</td>';
        koeff = koeff - (parseInt(attempted) - parseInt(scored));

	var threepts = getText(tableteam[i].rows[j].cells[7]);
        scored = threepts.substring(0, threepts.indexOf("/"));
        attempted = threepts.substring(threepts.indexOf("/")+1, threepts.length);
        fillField(i, playerindex, scored, "pt3_scored_");
        tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
        fillField(i, playerindex, attempted, "pt3_thrown_");
        if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
        else tabletext[i] += '<td align="center">-</td>';
        koeff = koeff - (parseInt(attempted) - parseInt(scored));

	var onepts = getText(tableteam[i].rows[j].cells[9]);
        scored = onepts.substring(0, onepts.indexOf("/"));
        attempted = onepts.substring(onepts.indexOf("/")+1, onepts.length);
        fillField(i, playerindex, scored, "pt1_scored_");
        tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
        fillField(i, playerindex, attempted, "pt1_thrown_");
        if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
        else tabletext[i] += '<td align="center">-</td>';
        koeff = koeff - (parseInt(attempted) - parseInt(scored));

        fillField(i, playerindex, getText(tableteam[i].rows[j].cells[13]), "rebounds_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[13]));
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[14]), "assists_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[14]));
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[17]), "steals_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[17]));
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[18]), "blocks_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[18]));
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[16]), "mistakes_");       
       koeff = koeff - parseInt(getText(tableteam[i].rows[j].cells[16]));
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[15]), "fauls_");       
       koeff = koeff - parseInt(getText(tableteam[i].rows[j].cells[15]));
       fillField(i, playerindex, "0", "unfauls_");       
       fillField(i, playerindex, koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[13]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[14]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[17]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[18]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[16]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[15]) + "</td>";
       tabletext[i] += '<td align="center">' + koeff + '</td>';

       tabletext[i] += "</tr>";
       }
      }
     }
    // total
       var koeff = 0;
    j = j+1;
  
       tabletext[i] += '<tr bgcolor="#bababa">';
       tabletext[i] += "<td>KOMANDA</td>";

       fillField(i, "", getText(tableteam[i].rows[j].cells[18]), "score_");
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[18]) + "</td>";
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[18]));
       tempcell = window.opener.document.getElementsByName("played_"+teams[i]+"-");
       if (tempcell != null && tempcell.length > 0) 
         tempcell[0].value = getText(tableteam[i].rows[j].cells[1]);
       fillField(i, "", getText(tableteam[i].rows[j].cells[1]), "played_");
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[1]) + "</td>";
       var scored = 0;
       var attempted = 0;
       var twopts = getText(tableteam[i].rows[j].cells[4]);
       scored = twopts.substring(0, twopts.indexOf("/"));
       attempted = twopts.substring(twopts.indexOf("/")+1, twopts.length);

       fillField(i, "", scored, "pt2_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt2_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       var threepts = getText(tableteam[i].rows[j].cells[6]);
       scored = threepts.substring(0, threepts.indexOf("/"));
       attempted = threepts.substring(threepts.indexOf("/")+1, threepts.length);
       fillField(i, "", scored, "pt3_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt3_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       var onepts = getText(tableteam[i].rows[j].cells[8]);
       scored = onepts.substring(0, onepts.indexOf("/"));
       attempted = onepts.substring(onepts.indexOf("/")+1, onepts.length);
       fillField(i, "", scored, "pt1_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt1_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       fillField(i, "", getText(tableteam[i].rows[j].cells[12]), "rebounds_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[12]));
       fillField(i, "", getText(tableteam[i].rows[j].cells[13]), "assists_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[13]));
       fillField(i, "", getText(tableteam[i].rows[j].cells[16]), "steals_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[16]));
       fillField(i, "", getText(tableteam[i].rows[j].cells[17]), "blocks_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[17]));
       fillField(i, "", getText(tableteam[i].rows[j].cells[15]), "mistakes_");       
       koeff = koeff - parseInt(getText(tableteam[i].rows[j].cells[15]));
       fillField(i, "", getText(tableteam[i].rows[j].cells[14]), "fauls_");       
       koeff = koeff - parseInt(getText(tableteam[i].rows[j].cells[14]));
       fillField(i, "", "0", "unfauls_");       
       fillField(i, "", koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[12]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[13]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[16]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[17]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[15]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[14]) + "</td>";
       tabletext[i] += '<td align="center">' + koeff + '</td>';
       tabletext[i] += "</tr>";
  
    
    tabletext[i] += "</table>";
  }
  divas.innerHTML = tabletext[0]+"<br>"+tabletext[1]; 
  divassource.innerHTML='<table><tr><td><textarea id="tablecode" cols="50" rows="30">' + tabletext[0] + '</textarea></td><td><textarea id="tablecode2" cols="50" rows="30">' + tabletext[1] + '</textarea></td></tr></table>';

}

function parseLKL() {
  var tables = document.body.getElementsByTagName("TABLE");
  var divs = document.body.getElementsByTagName("div");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");
  var tabletext = new Array(2);
  var teamname = new Array(2);
  var tableteam = new Array(2);
  var tempcell;
//  alert(divs.length);  

// team 1 - table 14, team 2 - table 16
  teamname[0] = divs[23].innerText;
  teamname[1] = divs[35].innerText;

  tableteam[0] = tables[3];
  tableteam[1] = tables[5];
  var i = 0;
  for(i=0; i < 2; i++)
  {
    /// parse table 1
//     alert(tables[i].rows.length);
    tabletext[i] = '';
    // get team name
    tabletext[i] += '<table width="100%" border="0" cellspacing="2" cellpadding="5" bgcolor="#cacaca">';

    tabletext[i] += '<tr bgcolor="#aaaaaa"><th>' + teamname[i] + '</th><th align="center">TÐ</th><th align="center">MN</th><th align="center">2T</th><th align="center">%2</th><th align="center">3T</th><th align="center">%3</th><th align="center">B</th><th align="center">%B</th><th align="center">AK</th><th align="center">RP</th><th align="center">BM</th><th align="center">PK</th><th align="center">KL</th><th align="center">UNF</th><th align="center">Koef</th></tr>';
   // get players stats
    for(j=1; j < tableteam[i].rows.length - 2; j++)
     {
      if (tableteam[i].rows[j].cells[1].innerText != ' ')
      {
      // change names to Capital
       var playername = tableteam[i].rows[j].cells[1].innerText.substring(tableteam[i].rows[j].cells[1].innerText.indexOf(". ") + 2, tableteam[i].rows[j].cells[1].innerText.length); 
       var playernum = tableteam[i].rows[j].cells[0].innerText;
//alert(playername);
//       playername = trim(playername);
       var playerindex = findPlayer(playername, i, playernum);
//alert(playerindex);

       if (playerindex > 0) {
         if (j%2 == 1) 
           tabletext[i] += '<tr bgcolor="#eaeaea">';
         else tabletext[i] += '<tr bgcolor="#dadada">';
       }
       else tabletext[i] += '<tr bgcolor="#ff0000">';

       tabletext[i] += "<td>" + playername + "</td>";

       // points 
      if (tableteam[i].rows[j].cells[2].innerText != "00:00" && tableteam[i].rows[j].cells[2].innerText != "DNP") 
       {
       fillField(i, playerindex, tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-1].innerText, "score_");
       if (tableteam[i].rows[j].cells[3].innerText != "")
         played = tableteam[i].rows[j].cells[2].innerText.substring(0, tableteam[i].rows[j].cells[2].innerText.indexOf(":"));
       fillField(i, playerindex, played, "played_");

       var koeff = 0;
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-1].innerText);

       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-1].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[2].innerText + "</td>";
       var scored = 0;
       var attempted = 0;
       scored = tableteam[i].rows[j].cells[3].innerText.substring(0, tableteam[i].rows[j].cells[3].innerText.indexOf("/"));
       attempted = tableteam[i].rows[j].cells[3].innerText.substring(tableteam[i].rows[j].cells[3].innerText.indexOf("/")+1, tableteam[i].rows[j].cells[3].innerText.length);
       fillField(i, playerindex, scored, "pt2_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, playerindex, attempted, "pt2_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       scored = tableteam[i].rows[j].cells[5].innerText.substring(0, tableteam[i].rows[j].cells[5].innerText.indexOf("/"));
       attempted = tableteam[i].rows[j].cells[5].innerText.substring(tableteam[i].rows[j].cells[5].innerText.indexOf("/")+1, tableteam[i].rows[j].cells[5].innerText.length);
       fillField(i, playerindex, scored, "pt3_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, playerindex, attempted, "pt3_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       scored = tableteam[i].rows[j].cells[7].innerText.substring(0, tableteam[i].rows[j].cells[7].innerText.indexOf("/"));
       attempted = tableteam[i].rows[j].cells[7].innerText.substring(tableteam[i].rows[j].cells[7].innerText.indexOf("/")+1, tableteam[i].rows[j].cells[7].innerText.length);
       fillField(i, playerindex, scored, "pt1_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, playerindex, attempted, "pt1_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       fillField(i, playerindex, tableteam[i].rows[j].cells[11].innerText, "rebounds_");
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[11].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[12].innerText, "assists_");
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[12].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[14].innerText, "unfauls_");       
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[14].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[16].innerText, "steals_");
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[16].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[17].innerText, "blocks_");
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[17].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[18].innerText, "unblocks_");
       koeff = koeff - parseInt(tableteam[i].rows[j].cells[18].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[15].innerText, "mistakes_");       
       koeff = koeff - parseInt(tableteam[i].rows[j].cells[15].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[13].innerText, "fauls_");       
       koeff = koeff - parseInt(tableteam[i].rows[j].cells[13].innerText);


//       var lklkoeff = tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-2].innerText;
       fillField(i, playerindex, koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[11].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[12].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[16].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[17].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[18].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[15].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[13].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[14].innerText + "</td>";
       tabletext[i] += '<td align="center">' + koeff + '</td>';

       tabletext[i] += "</tr>";
       }
      }
     }
    // total
       var koeff = 0;

       tabletext[i] += '<tr bgcolor="#bababa">';
       tabletext[i] += "<td>KOMANDA</td>";
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 2].cells[tableteam[i].rows[tableteam[i].rows.length - 2].cells.length-1].innerText, "score_");
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 2].cells[tableteam[i].rows[tableteam[i].rows.length - 2].cells.length-1].innerText + "</td>";
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 2].cells[tableteam[i].rows[tableteam[i].rows.length - 2].cells.length-1].innerText);
       tempcell = window.opener.document.getElementsByName("played_"+teams[i]+"-");
       if (tempcell != null && tempcell.length > 0) 
         tempcell[0].value = tableteam[i].rows[tableteam[i].rows.length - 2].cells[1].innerText;
       if (tableteam[i].rows[tableteam[i].rows.length - 2].cells[1].innerText != "")
         played = tableteam[i].rows[tableteam[i].rows.length - 2].cells[1].innerText.substring(0, tableteam[i].rows[tableteam[i].rows.length - 2].cells[1].innerText.indexOf(":"));
       fillField(i, "", played, "played_");

       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 2].cells[1].innerText + "</td>";
       var scored = 0;
       var attempted = 0;
       scored = tableteam[i].rows[tableteam[i].rows.length - 2].cells[2].innerText.substring(0, tableteam[i].rows[tableteam[i].rows.length - 2].cells[2].innerText.indexOf("/"));;
       attempted = tableteam[i].rows[tableteam[i].rows.length - 2].cells[2].innerText.substring(tableteam[i].rows[tableteam[i].rows.length - 2].cells[2].innerText.indexOf("/")+1, tableteam[i].rows[tableteam[i].rows.length - 2].cells[2].innerText.length);;

       fillField(i, "", scored, "pt2_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt2_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       scored = tableteam[i].rows[tableteam[i].rows.length - 2].cells[4].innerText.substring(0, tableteam[i].rows[tableteam[i].rows.length - 2].cells[4].innerText.indexOf("/"));;
       attempted = tableteam[i].rows[tableteam[i].rows.length - 2].cells[4].innerText.substring(tableteam[i].rows[tableteam[i].rows.length - 2].cells[4].innerText.indexOf("/")+1, tableteam[i].rows[tableteam[i].rows.length - 2].cells[4].innerText.length);;
       fillField(i, "", scored, "pt3_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt3_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       scored = tableteam[i].rows[tableteam[i].rows.length - 2].cells[6].innerText.substring(0, tableteam[i].rows[tableteam[i].rows.length - 2].cells[6].innerText.indexOf("/"));;
       attempted = tableteam[i].rows[tableteam[i].rows.length - 2].cells[6].innerText.substring(tableteam[i].rows[tableteam[i].rows.length - 2].cells[6].innerText.indexOf("/")+1, tableteam[i].rows[tableteam[i].rows.length - 2].cells[6].innerText.length);;
       fillField(i, "", scored, "pt1_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt1_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 2].cells[10].innerText, "rebounds_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 2].cells[10].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 2].cells[11].innerText, "assists_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 2].cells[11].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 2].cells[15].innerText, "steals_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 2].cells[15].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 2].cells[16].innerText, "blocks_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 2].cells[16].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 2].cells[17].innerText, "unblocks_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 2].cells[17].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 2].cells[13].innerText, "unfauls_");       
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 2].cells[13].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 2].cells[14].innerText, "mistakes_");       
       koeff = koeff - parseInt(tableteam[i].rows[tableteam[i].rows.length - 2].cells[14].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 2].cells[12].innerText, "fauls_");       
       koeff = koeff - parseInt(tableteam[i].rows[tableteam[i].rows.length - 2].cells[12].innerText);

//       var lklkoeff = tableteam[i].rows[tableteam[i].rows.length - 2].cells[tableteam[i].rows[tableteam[i].rows.length - 2].cells.length-2].innerText;
       fillField(i, "", koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 2].cells[10].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 2].cells[11].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 2].cells[14].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 2].cells[15].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 2].cells[13].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 2].cells[12].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 2].cells[13].innerText + "</td>";
       tabletext[i] += '<td align="center">' + koeff + '</td>';
       tabletext[i] += "</tr>";
  
    
    tabletext[i] += "</table>";
  }
  divas.innerHTML = tabletext[0]+"<br>"+tabletext[1]; 
  divassource.innerHTML='<table><tr><td><textarea id="tablecode" cols="50" rows="30">' + tabletext[0] + '</textarea></td><td><textarea id="tablecode2" cols="50" rows="30">' + tabletext[1] + '</textarea></td></tr></table>';

}

function parseEuroleague() {
  var tables = document.body.getElementsByTagName("TABLE");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");

  var tableteam = new Array(2);
  var tabletext = new Array(2);
  for(i=0; i < tables.length; i++)
  {
    /// parse table 1
    tableteam[i] = tables[i];
//     alert(tables[i].rows.length);
    tabletext[i] = '';
    // get team name
    tabletext[i] += '<table width="100%" border="0" cellspacing="2" cellpadding="5" bgcolor="#cacaca">';
    var teamname = getText(tables[i].rows[0].cells[0]);
    tabletext[i] += '<tr bgcolor="#aaaaaa"><th>' + teamname + '</th><th align="center">TÐ</th><th align="center">MN</th><th align="center">2T</th><th align="center">%2</th><th align="center">3T</th><th align="center">%3</th><th align="center">B</th><th align="center">%B</th><th align="center">AK</th><th align="center">RP</th><th align="center">BM</th><th align="center">GB</th><th align="center">PK</th><th align="center">KL</th></tr>';
   // get players stats
    for(j=3; j < tables[i].rows.length - 2; j++)
     {
       if (getText(tables[i].rows[j].cells[2]) != 'DNP')
       {

       var playername = getText(tables[i].rows[j].cells[1]); 
       playername = playername.substring(0, playername.indexOf(","));
       var playernum = getText(tableteam[i].rows[j].cells[0]);
       var playerindex = findPlayer(playername, i, playernum);

      // change names to Capital
       if (playerindex > 0) {
         if (j%2 == 1) 
           tabletext[i] += '<tr bgcolor="#eaeaea">';
         else tabletext[i] += '<tr bgcolor="#dadada">';
       }
       else tabletext[i] += '<tr bgcolor="#ff0000">';


       tabletext[i] += "<td>" + playername + "</td>";

       // points 
       var scored = 0;
       if (getText(tables[i].rows[j].cells[3]) != " ")
         scored = getText(tables[i].rows[j].cells[3]);
       fillField(i, playerindex, scored, "score_");

       var played = "";
       if (getText(tables[i].rows[j].cells[3]) != "-")
         played = getText(tables[i].rows[j].cells[2]).substring(0, getText(tables[i].rows[j].cells[2]).indexOf(":"));
       fillField(i, playerindex, played, "played_");

       tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[3]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[2]) + "</td>";
       scored = 0;
       var attempted = 0;
       if (getText(tables[i].rows[j].cells[4]).substring(0, getText(tables[i].rows[j].cells[4]).indexOf("/")) != "")
         scored = getText(tables[i].rows[j].cells[4]).substring(0, getText(tables[i].rows[j].cells[4]).indexOf("/"));
       if (getText(tables[i].rows[j].cells[4]).substring(getText(tables[i].rows[j].cells[4]).indexOf("/")+1, getText(tables[i].rows[j].cells[4]).length) != " ")
         attempted = getText(tables[i].rows[j].cells[4]).substring(getText(tables[i].rows[j].cells[4]).indexOf("/")+1, getText(tables[i].rows[j].cells[4]).length);
       fillField(i, playerindex, scored, "pt2_scored_");
       fillField(i, playerindex, attempted, "pt2_thrown_");
       if (getText(tables[i].rows[j].cells[4]) != ' ')
         tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       else tabletext[i] += '<td align="center">-</td>';
       if (attempted != 0 && getText(tables[i].rows[j].cells[4]) != ' ')
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';

       scored = 0;
       attempted = 0;
       if (getText(tables[i].rows[j].cells[5]).substring(0, getText(tables[i].rows[j].cells[5]).indexOf("/")) != "")
         scored = getText(tables[i].rows[j].cells[5]).substring(0, getText(tables[i].rows[j].cells[5]).indexOf("/"));
       if (getText(tables[i].rows[j].cells[5]).substring(getText(tables[i].rows[j].cells[5]).indexOf("/")+1, getText(tables[i].rows[j].cells[5]).length) != " ")
         attempted = getText(tables[i].rows[j].cells[5]).substring(getText(tables[i].rows[j].cells[5]).indexOf("/")+1, getText(tables[i].rows[j].cells[5]).length);
       fillField(i, playerindex, scored, "pt3_scored_");
       fillField(i, playerindex, attempted, "pt3_thrown_");
       if (getText(tables[i].rows[j].cells[5]) != ' ')
         tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       else tabletext[i] += '<td align="center">-</td>'; 
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       scored = 0;
       attempted = 0;
       if (getText(tables[i].rows[j].cells[6]).substring(0, getText(tables[i].rows[j].cells[6]).indexOf("/")) != "")
         scored = getText(tables[i].rows[j].cells[6]).substring(0, getText(tables[i].rows[j].cells[6]).indexOf("/"));
       if (getText(tables[i].rows[j].cells[6]).substring(getText(tables[i].rows[j].cells[6]).indexOf("/")+1, getText(tables[i].rows[j].cells[6]).length) != " ")
         attempted = getText(tables[i].rows[j].cells[6]).substring(getText(tables[i].rows[j].cells[6]).indexOf("/")+1, getText(tables[i].rows[j].cells[6]).length);
       fillField(i, playerindex, scored, "pt1_scored_");
       fillField(i, playerindex, attempted, "pt1_thrown_");
       if (getText(tables[i].rows[j].cells[6]) != ' ')
         tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       else tabletext[i] += '<td align="center">-</td>';
       if (attempted != 0 && getText(tables[i].rows[j].cells[7]) != ' ')
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';

       var rebounds = 0;
       var assists = "0";  
       var steals = 0;
       var blocks = 0;
       var unblocks = 0;
       var mistakes = 0;
       var fauls = 0;
       var unfauls = 0;
       var koeff = 0;
       if (getText(tables[i].rows[j].cells[9]) !=" ")
         rebounds = getText(tables[i].rows[j].cells[9]);
       if (getText(tables[i].rows[j].cells[10]) !="")
         assists = getText(tables[i].rows[j].cells[10]);
       if (getText(tables[i].rows[j].cells[11]) !=" ")
         steals = getText(tables[i].rows[j].cells[11]);
       if (getText(tables[i].rows[j].cells[13]) !=" ")
         blocks = getText(tables[i].rows[j].cells[13]);
       if (getText(tables[i].rows[j].cells[14]) !=" ")
         unblocks = getText(tables[i].rows[j].cells[14]);
       if (getText(tables[i].rows[j].cells[12]) !=" ")
         mistakes = getText(tables[i].rows[j].cells[12]);
       if (getText(tables[i].rows[j].cells[15]) !=" ")
         fauls = getText(tables[i].rows[j].cells[15]);
       if (getText(tables[i].rows[j].cells[16]) !=" ")
         unfauls = getText(tables[i].rows[j].cells[16]);
       if (getText(tables[i].rows[j].cells[17]) !=" ")
         koeff = getText(tables[i].rows[j].cells[17]);

       fillField(i, playerindex, rebounds, "rebounds_");
       fillField(i, playerindex, assists, "assists_");
       fillField(i, playerindex, steals, "steals_");
       fillField(i, playerindex, blocks, "blocks_");
       fillField(i, playerindex, unblocks, "unblocks_");
       fillField(i, playerindex, mistakes, "mistakes_");       
       fillField(i, playerindex, fauls, "fauls_");       
       fillField(i, playerindex, unfauls, "unfauls_");       
       fillField(i, playerindex, koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[9]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[10]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[11]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[13]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[12]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[14]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[15]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[16]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[17]) + "</td>";
//          }
       tabletext[i] += "</tr>";
       }
     }
    // total
       tabletext[i] += '<tr bgcolor="#bababa">';
       tabletext[i] += "<td>KOMANDA</td>";

       fillField(i, "", getText(tables[i].rows[tables[i].rows.length - 2].cells[3]), "score_");
       if (getText(tables[i].rows[tables[i].rows.length - 2].cells[2]) != "")
         played = getText(tables[i].rows[tables[i].rows.length - 2].cells[2]).substring(0, getText(tables[i].rows[tables[i].rows.length - 2].cells[2]).indexOf(":"));

       fillField(i, "", played, "played_");

       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[3]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[2]) + "</td>";
       var scored = 0;
       var attempted = 0;
       scored = getText(tables[i].rows[tables[i].rows.length - 2].cells[4]).substring(0, getText(tables[i].rows[tables[i].rows.length - 2].cells[4]).indexOf("/"));
       attempted = getText(tables[i].rows[tables[i].rows.length - 2].cells[4]).substring(getText(tables[i].rows[tables[i].rows.length - 2].cells[4]).indexOf("/")+1, getText(tables[i].rows[tables[i].rows.length - 2].cells[4]).length);

       fillField(i, "", scored, "pt2_scored_");
       fillField(i, "", attempted, "pt2_thrown_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       scored = getText(tables[i].rows[tables[i].rows.length - 2].cells[5]).substring(0, getText(tables[i].rows[tables[i].rows.length - 2].cells[5]).indexOf("/"));
       attempted = getText(tables[i].rows[tables[i].rows.length - 2].cells[5]).substring(getText(tables[i].rows[tables[i].rows.length - 2].cells[5]).indexOf("/")+1, getText(tables[i].rows[tables[i].rows.length - 2].cells[5]).length);
       fillField(i, "", scored, "pt3_scored_");
       fillField(i, "", attempted, "pt3_thrown_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       scored = getText(tables[i].rows[tables[i].rows.length - 2].cells[6]).substring(0, getText(tables[i].rows[tables[i].rows.length - 2].cells[6]).indexOf("/"));
       attempted = getText(tables[i].rows[tables[i].rows.length - 2].cells[6]).substring(getText(tables[i].rows[tables[i].rows.length - 2].cells[6]).indexOf("/")+1, getText(tables[i].rows[tables[i].rows.length - 2].cells[6]).length);
       fillField(i, "", scored, "pt1_scored_");
       fillField(i, "", attempted, "pt1_thrown_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';

       var rebounds = 0;
       var assists = 0;  
       var steals = 0;
       var blocks = 0;
       var unblocks = 0;
       var mistakes = 0;
       var fauls = 0;
       var unfauls = 0;
       var koeff = 0;
       if (getText(tables[i].rows[tables[i].rows.length - 2].cells[9]) !=" ")
         rebounds = getText(tables[i].rows[tables[i].rows.length - 2].cells[9]);
       if (getText(tables[i].rows[tables[i].rows.length - 2].cells[10]) !=" ")
         assists = getText(tables[i].rows[tables[i].rows.length - 2].cells[10]);
       if (getText(tables[i].rows[tables[i].rows.length - 2].cells[11]) !=" ")
         steals = getText(tables[i].rows[tables[i].rows.length - 2].cells[11]);
       if (getText(tables[i].rows[tables[i].rows.length - 2].cells[13]) !=" ")
         blocks = getText(tables[i].rows[tables[i].rows.length - 2].cells[13]);
       if (getText(tables[i].rows[tables[i].rows.length - 2].cells[14]) !=" ")
         unblocks = getText(tables[i].rows[tables[i].rows.length - 2].cells[14]);
       if (getText(tables[i].rows[tables[i].rows.length - 2].cells[12]) !=" ")
         mistakes = getText(tables[i].rows[tables[i].rows.length - 2].cells[12]);
       if (getText(tables[i].rows[tables[i].rows.length - 2].cells[15]) !=" ")
         fauls = getText(tables[i].rows[tables[i].rows.length - 2].cells[15]);
       if (getText(tables[i].rows[tables[i].rows.length - 2].cells[16]) !=" ")
         unfauls = getText(tables[i].rows[tables[i].rows.length - 2].cells[16]);
       if (getText(tables[i].rows[tables[i].rows.length - 2].cells[17]) !=" ")
         koeff = getText(tables[i].rows[tables[i].rows.length - 2].cells[17]);

       fillField(i, "", rebounds, "rebounds_");
       fillField(i, "", assists, "assists_");
       fillField(i, "", steals, "steals_");
       fillField(i, "", blocks, "blocks_");
       fillField(i, "", unblocks, "unblocks_");
       fillField(i, "", mistakes, "mistakes_");       
       fillField(i, "", fauls, "fauls_");       
       fillField(i, "", unfauls, "unfauls_");       
       fillField(i, "", koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[9])+ "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[10]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[11]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[13]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[12]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[14]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[15]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[16]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[17]) + "</td>";
       tabletext[i] += "</tr>";
    
    tabletext[i] += "</table>";
  }
  divas.innerHTML = tabletext[0]+"<br>"+tabletext[1]; 
  divassource.innerHTML='<table><tr><td><textarea id="tablecode" cols="50" rows="30">' + tabletext[0] + '</textarea></td><td><textarea id="tablecode2" cols="50" rows="30">' + tabletext[1] + '</textarea></td></tr></table>';

}

function parseAthens() {
  var tables = document.body.getElementsByTagName("TABLE");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");
  var tabletext = new Array(2);
//  alert(parent.opener.document);
//  alert(tables.length);  
    tabletext[0] = '';
    // get team name
    tabletext[0] += '<table width="100%" border="0" cellspacing="2" cellpadding="5" bgcolor="#cacaca">';
    var teamname = tables[0].rows[0].cells[0].childNodes[0].title;
    tabletext[0] += '<tr bgcolor="#aaaaaa"><th>' + teamname + '</th><th align="center">TÐ</th><th align="center">MN</th><th align="center">2T</th><th align="center">%2</th><th align="center">3T</th><th align="center">%3</th><th align="center">B</th><th align="center">%B</th><th align="center">AK</th><th align="center">RP</th><th align="center">BM</th><th align="center">PK</th><th align="center">KL</th></tr>';

  var total0;
  var total1;
  var go=true;
  var pl = 3;
  while (go) 
  {  
    if (tables[0].rows[pl].cells[0].innerText == "Team/Coach")   // number
      go = false;
    if (go) 
     { 
      var playername = tables[0].rows[pl].cells[1].innerText;
      playername = playername.toLowerCase();
      playername = playername.charAt(0).toUpperCase() + playername.substring(1, playername.length);
//      alert (playername);
      var playerindex = findPlayer(playername.substring(0, playername.indexOf(" ")).toUpperCase(), 0);
      playername = playername.substring(0, playername.indexOf(" ")) + playername.substring(playername.indexOf(" "), playername.indexOf(" ") + 2).toUpperCase() + playername.substring(playername.indexOf(" ")+2, playername.length);

      var points = 0;
      var played = 0;
      var scored = 0;
      var attempted = 0;
      tabletext[0] += "<td>" + playername + "</td>";

      if (tables[0].rows[pl].cells[20].innerText != "")
        points = tables[0].rows[pl].cells[20].innerText;
      if (tables[0].rows[pl].cells[2].innerText != "")
        played = tables[0].rows[pl].cells[2].innerText.substring(0, tables[0].rows[pl].cells[2].innerText.indexOf(":"));

      tabletext[0] += '<td align="center">' + points + "</td>";
      tabletext[0] += '<td align="center">' + played + "</td>";
      fillField(0, playerindex, points, "score_");
      fillField(0, playerindex, played, "played_");

      if (tables[0].rows[pl].cells[5].innerText != "")
       {
        scored = tables[0].rows[pl].cells[5].innerText.substring(0, tables[0].rows[pl].cells[5].innerText.indexOf("/"));
        attempted = tables[0].rows[pl].cells[5].innerText.substring(tables[0].rows[pl].cells[5].innerText.indexOf("/")+1, tables[0].rows[pl].cells[5].innerText.length);
       }
      fillField(0, playerindex, scored, "pt2_scored_");
      fillField(0, playerindex, attempted, "pt2_thrown_");
      tabletext[0] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
      tabletext[0] += '<td align="center">' + tables[0].rows[pl].cells[6].innerText + "</td>";
      scored = 0;
      attempted = 0;
      if (tables[0].rows[pl].cells[7].innerText != "")
       {
        scored = tables[0].rows[pl].cells[7].innerText.substring(0, tables[0].rows[pl].cells[7].innerText.indexOf("/"));
        attempted = tables[0].rows[pl].cells[7].innerText.substring(tables[0].rows[pl].cells[7].innerText.indexOf("/")+1, tables[0].rows[pl].cells[7].innerText.length);
       }
      fillField(0, playerindex, scored, "pt3_scored_");
      fillField(0, playerindex, attempted, "pt3_thrown_");
      tabletext[0] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
      tabletext[0] += '<td align="center">' + tables[0].rows[pl].cells[8].innerText + "</td>";
      scored = 0;
      attempted = 0;
      if (tables[0].rows[pl].cells[9].innerText != "")
       {
        scored = tables[0].rows[pl].cells[9].innerText.substring(0, tables[0].rows[pl].cells[9].innerText.indexOf("/"));
        attempted = tables[0].rows[pl].cells[9].innerText.substring(tables[0].rows[pl].cells[9].innerText.indexOf("/")+1, tables[0].rows[pl].cells[9].innerText.length);
       }
      fillField(0, playerindex, scored, "pt1_scored_");
      fillField(0, playerindex, attempted, "pt1_thrown_");

      tabletext[0] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
      tabletext[0] += '<td align="center">' + tables[0].rows[pl].cells[10].innerText + "</td>";

      var rebound = 0;
      var assist = 0;  
      var steals = 0;
      var blocks = 0;
      var mistakes = 0;
      if (tables[0].rows[pl].cells[13].innerText != "")
        rebound = tables[0].rows[pl].cells[13].innerText;
      if (tables[0].rows[pl].cells[14].innerText != "")
        assist = tables[0].rows[pl].cells[14].innerText;
      if (tables[0].rows[pl].cells[17].innerText != "")
        blocks = tables[0].rows[pl].cells[17].innerText;
      if (tables[0].rows[pl].cells[16].innerText != "")
        steals = tables[0].rows[pl].cells[16].innerText;
      if (tables[0].rows[pl].cells[15].innerText != "")
        mistakes = tables[0].rows[pl].cells[15].innerText;

      tabletext[0] += '<td align="center">' + rebound + "</td>";
      tabletext[0] += '<td align="center">' + assist + "</td>";
      tabletext[0] += '<td align="center">' + steals + "</td>";
      tabletext[0] += '<td align="center">' + blocks + "</td>";
      tabletext[0] += '<td align="center">' + mistakes + "</td>";

       fillField(0, playerindex, rebound, "rebounds_");
       fillField(0, playerindex, assist, "assists_");
       fillField(0, playerindex, steals, "steals_");
       fillField(0, playerindex, blocks, "blocks_");
       fillField(0, playerindex, mistakes, "mistakes_");       

      tabletext[0] += "</tr>";
     } 
    pl = pl+1;
  }
 
//  pl = pl + 2;
  // ============ TOTALS
  tabletext[0] += '<tr bgcolor="#bababa">';
  tabletext[0] += "<td>KOMANDA</td>";

   tabletext[0] += '<td align="center">' + tables[0].rows[pl].cells[18].innerText + "</td>";
   fillField(0, "", tables[0].rows[pl].cells[18].innerText, "score_");

   tabletext[0] += '<td align="center"></td>';
 
     if (tables[0].rows[pl].cells[3].innerText != "")
       {
        scored = tables[0].rows[pl].cells[3].innerText.substring(0, tables[0].rows[pl].cells[3].innerText.indexOf("/"));
        attempted = tables[0].rows[pl].cells[3].innerText.substring(tables[0].rows[pl].cells[3].innerText.indexOf("/")+1, tables[0].rows[pl].cells[3].innerText.length);
       }
      tabletext[0] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
      tabletext[0] += '<td align="center">' + tables[0].rows[pl].cells[4].innerText + "</td>";

      fillField(0, "", scored, "pt2_scored_");
      fillField(0, "", attempted, "pt2_thrown_");

      scored = 0;
      attempted = 0;
      if (tables[0].rows[pl].cells[5].innerText != "")
       {
        scored = tables[0].rows[pl].cells[5].innerText.substring(0, tables[0].rows[pl].cells[5].innerText.indexOf("/"));
        attempted = tables[0].rows[pl].cells[5].innerText.substring(tables[0].rows[pl].cells[5].innerText.indexOf("/")+1, tables[0].rows[pl].cells[5].innerText.length);
       }
      tabletext[0] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
      tabletext[0] += '<td align="center">' + tables[0].rows[pl].cells[6].innerText + "</td>";

      fillField(0, "", scored, "pt3_scored_");
      fillField(0, "", attempted, "pt3_thrown_");

      scored = 0;
      attempted = 0;
      if (tables[0].rows[pl].cells[7].innerText != "")
       {
        scored = tables[0].rows[pl].cells[7].innerText.substring(0, tables[0].rows[pl].cells[7].innerText.indexOf("/"));
        attempted = tables[0].rows[pl].cells[7].innerText.substring(tables[0].rows[pl].cells[7].innerText.indexOf("/")+1, tables[0].rows[pl].cells[7].innerText.length);
       }
      tabletext[0] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
      tabletext[0] += '<td align="center">' + tables[0].rows[pl].cells[8].innerText + "</td>";
      fillField(0, "", scored, "pt1_scored_");
      fillField(0, "", attempted, "pt1_thrown_");


      assist = tables[0].rows[pl].cells[12].innerText;
//alert(assits);
      tabletext[0] += '<td align="center">' + tables[0].rows[pl].cells[11].innerText + "</td>";
      tabletext[0] += '<td align="center">' + assist + "</td>";
      tabletext[0] += '<td align="center">' + tables[0].rows[pl].cells[15].innerText + "</td>";
      tabletext[0] += '<td align="center">' + tables[0].rows[pl].cells[14].innerText + "</td>";
      tabletext[0] += '<td align="center">' + tables[0].rows[pl].cells[13].innerText + "</td>";
       fillField(0, "", tables[0].rows[pl].cells[11].innerText, "rebounds_");
       fillField(0, "", assist, "assists_");
       fillField(0, "", tables[0].rows[pl].cells[14].innerText, "steals_");
       fillField(0, "", tables[0].rows[pl].cells[15].innerText, "blocks_");
       fillField(0, "", tables[0].rows[pl].cells[13].innerText, "mistakes_");       

  tabletext[0] += "</tr>";

  pl = pl + 3; 
  tabletext[1] = '';
  // get team name
  tabletext[1] += '<table width="100%" border="0" cellspacing="2" cellpadding="5" bgcolor="#cacaca">';
  var teamname = tables[0].rows[pl].cells[0].childNodes[0].title;
  tabletext[1] += '<tr bgcolor="#aaaaaa"><th>' + teamname + '</th><th align="center">TÐ</th><th align="center">MN</th><th align="center">2T</th><th align="center">%2</th><th align="center">3T</th><th align="center">%3</th><th align="center">B</th><th align="center">%B</th><th align="center">AK</th><th align="center">RP</th><th align="center">BM</th><th align="center">PK</th><th align="center">KL</th></tr>';

  go=true;
  pl = pl + 3;
  while (go) 
  {  
    if (tables[0].rows[pl].cells[0].innerText == "Team/Coach")   // number
      go = false;
    if (go) 
     { 
      var playername = tables[0].rows[pl].cells[1].innerText;
      playername = playername.toLowerCase();
      playername = playername.charAt(0).toUpperCase() + playername.substring(1, playername.length);
//      alert (playername);
//      var playerindex = findPlayer(playername.substring(playername.indexOf(" ")+1, playername.indexOf(" ") + 2).toUpperCase() + playername.substring(playername.indexOf(" ")+2, playername.length), (1+1)%2);
      var playerindex = findPlayer(playername.substring(0, playername.indexOf(" ")).toUpperCase(), 1);
      playername = playername.substring(0, playername.indexOf(" ")) + playername.substring(playername.indexOf(" "), playername.indexOf(" ") + 2).toUpperCase() + playername.substring(playername.indexOf(" ")+2, playername.length);

      tabletext[1] += "<td>" + playername + "</td>";
      var points = 0;
      var played = 0;
      var scored = 0;
      var attempted = 0;
      if (tables[0].rows[pl].cells[20].innerText != "")
        points = tables[0].rows[pl].cells[20].innerText;
      if (tables[0].rows[pl].cells[2].innerText != "")
        played = tables[0].rows[pl].cells[2].innerText.substring(0, tables[0].rows[pl].cells[2].innerText.indexOf(":"));

      tabletext[1] += '<td align="center">' + points + "</td>";
      tabletext[1] += '<td align="center">' + played + "</td>";
      fillField(1, playerindex, points, "score_");
      fillField(1, playerindex, played, "played_");

      if (tables[0].rows[pl].cells[5].innerText != "")
       {
        scored = tables[0].rows[pl].cells[5].innerText.substring(0, tables[0].rows[pl].cells[5].innerText.indexOf("/"));
        attempted = tables[0].rows[pl].cells[5].innerText.substring(tables[0].rows[pl].cells[5].innerText.indexOf("/")+1, tables[0].rows[pl].cells[5].innerText.length);
       }
      fillField(1, playerindex, scored, "pt2_scored_");
      fillField(1, playerindex, attempted, "pt2_thrown_");
      tabletext[1] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
      tabletext[1] += '<td align="center">' + tables[0].rows[pl].cells[6].innerText + "</td>";
      scored = 0;
      attempted = 0;
      if (tables[0].rows[pl].cells[7].innerText != "")
       {
        scored = tables[0].rows[pl].cells[7].innerText.substring(0, tables[0].rows[pl].cells[7].innerText.indexOf("/"));
        attempted = tables[0].rows[pl].cells[7].innerText.substring(tables[0].rows[pl].cells[7].innerText.indexOf("/")+1, tables[0].rows[pl].cells[7].innerText.length);
       }
      fillField(1, playerindex, scored, "pt3_scored_");
      fillField(1, playerindex, attempted, "pt3_thrown_");
      tabletext[1] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
      tabletext[1] += '<td align="center">' + tables[0].rows[pl].cells[8].innerText + "</td>";
      scored = 0;
      attempted = 0;
      if (tables[0].rows[pl].cells[9].innerText != "")
       {
        scored = tables[0].rows[pl].cells[9].innerText.substring(0, tables[0].rows[pl].cells[9].innerText.indexOf("/"));
        attempted = tables[0].rows[pl].cells[9].innerText.substring(tables[0].rows[pl].cells[9].innerText.indexOf("/")+1, tables[0].rows[pl].cells[9].innerText.length);
       }
      fillField(1, playerindex, scored, "pt1_scored_");
      fillField(1, playerindex, attempted, "pt1_thrown_");
      tabletext[1] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
      tabletext[1] += '<td align="center">' + tables[0].rows[pl].cells[10].innerText + "</td>";

      var rebound = 0;
      var assist = 0;  
      var steals = 0;
      var blocks = 0;
      var mistakes = 0;
      if (tables[0].rows[pl].cells[13].innerText != "")
        rebound = tables[0].rows[pl].cells[13].innerText;
      if (tables[0].rows[pl].cells[14].innerText != "")
        assist = tables[0].rows[pl].cells[14].innerText;
      if (tables[0].rows[pl].cells[17].innerText != "")
        blocks = tables[0].rows[pl].cells[17].innerText;
      if (tables[0].rows[pl].cells[16].innerText != "")
        steals  = tables[0].rows[pl].cells[16].innerText;
      if (tables[0].rows[pl].cells[15].innerText != "")
        mistakes = tables[0].rows[pl].cells[15].innerText;

      tabletext[1] += '<td align="center">' + rebound + "</td>";
      tabletext[1] += '<td align="center">' + assist + "</td>";
      tabletext[1] += '<td align="center">' + steals + "</td>";
      tabletext[1] += '<td align="center">' + blocks + "</td>";
      tabletext[1] += '<td align="center">' + mistakes + "</td>";

       fillField(1, playerindex, rebound, "rebounds_");
       fillField(1, playerindex, assist, "assists_");
       fillField(1, playerindex, steals, "steals_");
       fillField(1, playerindex, blocks , "blocks_");
       fillField(1, playerindex, mistakes, "mistakes_");       

      tabletext[1] += "</tr>";
     } 
    pl = pl+1;
  }

  // ============ TOTALS
  tabletext[1] += '<tr bgcolor="#bababa">';
  tabletext[1] += "<td>KOMANDA</td>";

   tabletext[1] += '<td align="center">' + tables[0].rows[pl].cells[18].innerText + "</td>";
   fillField(1, "", tables[0].rows[pl].cells[18].innerText, "score_");
   tabletext[1] += '<td align="center"></td>';
 
     if (tables[0].rows[pl].cells[3].innerText != "")
       {
        scored = tables[0].rows[pl].cells[3].innerText.substring(0, tables[0].rows[pl].cells[3].innerText.indexOf("/"));
        attempted = tables[0].rows[pl].cells[3].innerText.substring(tables[0].rows[pl].cells[3].innerText.indexOf("/")+1, tables[0].rows[pl].cells[3].innerText.length);
       }
      tabletext[1] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
      tabletext[1] += '<td align="center">' + tables[0].rows[pl].cells[4].innerText + "</td>";
      fillField(1, "", scored, "pt2_scored_");
      fillField(1, "", attempted, "pt2_thrown_");

      scored = 0;
      attempted = 0;
      if (tables[0].rows[pl].cells[5].innerText != "")
       {
        scored = tables[0].rows[pl].cells[5].innerText.substring(0, tables[0].rows[pl].cells[5].innerText.indexOf("/"));
        attempted = tables[0].rows[pl].cells[5].innerText.substring(tables[0].rows[pl].cells[5].innerText.indexOf("/")+1, tables[0].rows[pl].cells[5].innerText.length);
       }
      tabletext[1] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
      tabletext[1] += '<td align="center">' + tables[0].rows[pl].cells[6].innerText + "</td>";
      fillField(1, "", scored, "pt3_scored_");
      fillField(1, "", attempted, "pt3_thrown_");

      scored = 0;
      attempted = 0;
      if (tables[0].rows[pl].cells[7].innerText != "")
       {
        scored = tables[0].rows[pl].cells[7].innerText.substring(0, tables[0].rows[pl].cells[7].innerText.indexOf("/"));
        attempted = tables[0].rows[pl].cells[7].innerText.substring(tables[0].rows[pl].cells[7].innerText.indexOf("/")+1, tables[0].rows[pl].cells[7].innerText.length);
       }
      tabletext[1] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
      tabletext[1] += '<td align="center">' + tables[0].rows[pl].cells[8].innerText + "</td>";
      fillField(1, "", scored, "pt1_scored_");
      fillField(1, "", attempted, "pt1_thrown_");

      assist = tables[0].rows[pl].cells[12].innerText;
//alert(assist);
    tabletext[1] += '<td align="center">' + tables[0].rows[pl].cells[11].innerText + "</td>";
    tabletext[1] += '<td align="center">' + assist + "</td>";
    tabletext[1] += '<td align="center">' + tables[0].rows[pl].cells[15].innerText + "</td>";
    tabletext[1] += '<td align="center">' + tables[0].rows[pl].cells[14].innerText + "</td>";
    tabletext[1] += '<td align="center">' + tables[0].rows[pl].cells[13].innerText + "</td>";

    fillField(1, "", tables[0].rows[pl].cells[11].innerText, "rebounds_");
    fillField(1, "", assist, "assists_");
    fillField(1, "", tables[0].rows[pl].cells[14].innerText, "steals_");
    fillField(1, "", tables[0].rows[pl].cells[15].innerText, "blocks_");
    fillField(1, "", tables[0].rows[pl].cells[13].innerText, "mistakes_");       
    tabletext[1] += "</tr>";


  // ========= TOTALS

  divas.innerHTML = tabletext[0]+"<br>"+tabletext[1]; 
  divassource.innerHTML='<table><tr><td><textarea id="tablecode" cols="50" rows="30">' + tabletext[0] + '</textarea></td><td><textarea id="tablecode2" cols="50" rows="30">' + tabletext[1] + '</textarea></td></tr></table>';

}

function parseNBA() {
  var tables = document.body.getElementsByTagName("TABLE");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");
  var tabletext = new Array(2);
//  alert(parent.opener.document);
//  alert(tables.length);  
  for(i=0; i < tables.length; i++)
  {
    /// parse table 1
//     alert(tables[i].rows.length);

    tabletext[i] = '';
    // get team name
    tabletext[i] += '<table width="100%" border="0" cellspacing="2" cellpadding="5" bgcolor="#cacaca">';
    var teamname = tables[i].rows[0].cells[0].innerText;
    tabletext[i] += '<tr bgcolor="#aaaaaa"><th>' + teamname + '</th><th align="center">TÐ</th><th align="center">MN</th><th align="center">2T</th><th align="center">%2</th><th align="center">3T</th><th align="center">%3</th><th align="center">B</th><th align="center">%B</th><th align="center">AK</th><th align="center">RP</th><th align="center">BM</th><th align="center">PK</th><th align="center">KL</th></tr>';
   // get players stats
    for(j=3; j < tables[i].rows.length - 2; j++)
     {
       if (tables[i].rows[j].cells.length > 3)
       {
       var playernamelink = getLink(tables[i].rows[j].cells[0].innerHTML); 
       playernamelink = playernamelink.replace("/playerfile/", "");
       playernamelink = playernamelink.replace("/index.html", "");
       playernamelink = playernamelink.replace("-", "_");
//       var playername = getText(tables[i].rows[j].cells[0]); 
       playername = playernamelink; // playername.toLowerCase();
       var playerindex = findPlayer2(playername, (i+1)%2);
       var played = getText(tables[i].rows[j].cells[2]);

//      alert(tables[i].rows[j].cells[0].innerText);
      // change names to Capital
       if (playerindex > 0) {
         if (j%2 == 1) 
           tabletext[i] += '<tr bgcolor="#eaeaea">';
         else tabletext[i] += '<tr bgcolor="#dadada">';
       }
       else tabletext[i] += '<tr bgcolor="#ff0000">';

       if (played != "00:00") {
         played = played.substring(0, played.indexOf(":"));

         tabletext[i] += "<td>" + playername + "</td>";
         var koeff = 0;
         koeff = koeff + parseInt(getText(tables[i].rows[j].cells[tables[i].rows[j].cells.length-1]));
  
         fillField((i+1)%2, playerindex, getText(tables[i].rows[j].cells[tables[i].rows[j].cells.length-1]), "score_");
         fillField((i+1)%2, playerindex, played, "played_");
         tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[tables[i].rows[j].cells.length-1]) + "</td>";
         tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[2]) + "</td>";
         var scored3 = 0;
         var attempted3 = 0;
         scored3 = getText(tables[i].rows[j].cells[4]).substring(0, getText(tables[i].rows[j].cells[4]).indexOf("-"));
         attempted3 = getText(tables[i].rows[j].cells[4]).substring(getText(tables[i].rows[j].cells[4]).indexOf("-")+1, getText(tables[i].rows[j].cells[4]).length);
         tempcell = window.opener.document.getElementsByName("pt3_scored_"+teams[i]+"-"+playerindex);
         if (tempcell != null && tempcell.length > 0) 
           tempcell[0].value = scored;
         fillField((i+1)%2, playerindex, scored3, "pt3_scored_");
         tabletext[i] += '<td align="center">' + scored3+ "/" + attempted3  + "</td>";
         fillField((i+1)%2, playerindex, attempted3, "pt3_thrown_");
         if (attempted3 != 0)
            tabletext[i] += '<td align="center">' + Math.round((scored3/attempted3)*100) + "%</td>";
         else tabletext[i] += '<td align="center">-</td>';
         koeff = koeff - (parseInt(attempted3) - parseInt(scored3));
         var scored = 0;
         var attempted = 0;
         scored = getText(tables[i].rows[j].cells[3]).substring(0, getText(tables[i].rows[j].cells[3]).indexOf("-")) - scored3;
         attempted = getText(tables[i].rows[j].cells[3]).substring(getText(tables[i].rows[j].cells[3]).indexOf("-")+1, getText(tables[i].rows[j].cells[3]).length) - attempted3;
         fillField((i+1)%2, playerindex, scored, "pt2_scored_");
         tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
         fillField((i+1)%2, playerindex, attempted, "pt2_thrown_");
         if (attempted != 0)
            tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
         else tabletext[i] += '<td align="center">-</td>';
         koeff = koeff - (parseInt(attempted) - parseInt(scored));
         scored = getText(tables[i].rows[j].cells[5]).substring(0, getText(tables[i].rows[j].cells[5]).indexOf("-"));
         attempted = getText(tables[i].rows[j].cells[5]).substring(getText(tables[i].rows[j].cells[5]).indexOf("-")+1, getText(tables[i].rows[j].cells[5]).length);
         fillField((i+1)%2, playerindex, scored, "pt1_scored_");
         tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
         fillField((i+1)%2, playerindex, attempted, "pt1_thrown_");
         if (attempted != 0)
            tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
         else tabletext[i] += '<td align="center">-</td>';
         koeff = koeff - (parseInt(attempted) - parseInt(scored));
  
         fillField((i+1)%2, playerindex, getText(tables[i].rows[j].cells[9]), "rebounds_");
         koeff = koeff + parseInt(getText(tables[i].rows[j].cells[9]));
         fillField((i+1)%2, playerindex, getText(tables[i].rows[j].cells[10]), "assists_");
         koeff = koeff + parseInt(getText(tables[i].rows[j].cells[10]));
         fillField((i+1)%2, playerindex, getText(tables[i].rows[j].cells[11]), "fauls_");
         koeff = koeff - parseInt(getText(tables[i].rows[j].cells[11]));
         fillField((i+1)%2, playerindex, getText(tables[i].rows[j].cells[12]), "steals_");
         koeff = koeff + parseInt(getText(tables[i].rows[j].cells[12]));
         fillField((i+1)%2, playerindex, getText(tables[i].rows[j].cells[13]), "mistakes_");       
         koeff = koeff - parseInt(getText(tables[i].rows[j].cells[13]));
         fillField((i+1)%2, playerindex, getText(tables[i].rows[j].cells[14]), "blocks_");
         koeff = koeff + parseInt(getText(tables[i].rows[j].cells[14]));
         fillField((i+1)%2, playerindex, getText(tables[i].rows[j].cells[15]), "unblocks_");
         koeff = koeff - parseInt(getText(tables[i].rows[j].cells[15]));
         fillField((i+1)%2, playerindex, koeff, "koeff_");       
  
         tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[9]) + "</td>";
         tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[10]) + "</td>";
         tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[11]) + "</td>";
         tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[12]) + "</td>";
         tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[13]) + "</td>";
         tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[14]) + "</td>";
         tabletext[i] += '<td align="center">' + getText(tables[i].rows[j].cells[15]) + "</td>";
                   
         tabletext[i] += "</tr>";
        }
       }
     }
    // total                   
//alert(tabletext[i]);
       tabletext[i] += '<tr bgcolor="#bababa">';
       tabletext[i] += "<td>KOMANDA</td>";

//alert(tables[i].rows[tables[i].rows.length - 2].cells[tables[i].rows[j].cells.length-1].innerText);
       fillField((i+1)%2, "", getText(tables[i].rows[tables[i].rows.length - 2].cells[tables[i].rows[j].cells.length-1]), "score_");
       fillField((i+1)%2, "", getText(tables[i].rows[tables[i].rows.length - 2].cells[2]), "played_");

       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[tables[i].rows[j].cells.length-1]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[2]) + "</td>";
       var scored3 = 0;
       var attempted3 = 0;
       scored3 = getText(tables[i].rows[tables[i].rows.length - 2].cells[4]).substring(0, getText(tables[i].rows[j].cells[4]).indexOf("-"));
       attempted3 = getText(tables[i].rows[tables[i].rows.length - 2].cells[4]).substring(getText(tables[i].rows[j].cells[4]).indexOf("-")+1, getText(tables[i].rows[j].cells[4]).length);
       fillField((i+1)%2, "", scored3, "pt3_scored_");
       tabletext[i] += '<td align="center">' + scored3+ "/" + attempted3  + "</td>";
       fillField((i+1)%2, "", attempted3, "pt3_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored3/attempted3)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       var scored = 0;
       var attempted = 0;
       scored = getText(tables[i].rows[tables[i].rows.length - 2].cells[3]).substring(0, getText(tables[i].rows[j].cells[3]).indexOf("-")) - scored3;
       attempted = getText(tables[i].rows[tables[i].rows.length - 2].cells[3]).substring(getText(tables[i].rows[j].cells[3]).indexOf("-")+1, getText(tables[i].rows[j].cells[3]).length) - attempted3;
       fillField((i+1)%2, "", scored, "pt2_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField((i+1)%2, "", attempted, "pt2_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       scored = getText(tables[i].rows[tables[i].rows.length - 2].cells[5]).substring(0, getText(tables[i].rows[j].cells[5]).indexOf("-"));
       attempted = getText(tables[i].rows[tables[i].rows.length - 2].cells[5]).substring(getText(tables[i].rows[j].cells[5]).indexOf("-")+1, getText(tables[i].rows[j].cells[5]).length);
       fillField((i+1)%2, "", scored, "pt1_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField((i+1)%2, "", attempted, "pt1_thrown_");

       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';


       fillField((i+1)%2, "", getText(tables[i].rows[tables[i].rows.length - 2].cells[9]), "rebounds_");
       fillField((i+1)%2, "", getText(tables[i].rows[tables[i].rows.length - 2].cells[10]), "assists_");
       fillField((i+1)%2, "", getText(tables[i].rows[tables[i].rows.length - 2].cells[11]), "fauls_");
       fillField((i+1)%2, "", getText(tables[i].rows[tables[i].rows.length - 2].cells[12]), "steals_");
       fillField((i+1)%2, "", getText(tables[i].rows[tables[i].rows.length - 2].cells[13]), "mistakes_");       
       fillField((i+1)%2, "", getText(tables[i].rows[tables[i].rows.length - 2].cells[14]), "blocks_");
       fillField((i+1)%2, "", getText(tables[i].rows[tables[i].rows.length - 2].cells[15]), "unblocks_");       

       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[9]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[10]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[11]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[12]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tables[i].rows[tables[i].rows.length - 2].cells[13]) + "</td>";
       tabletext[i] += "</tr>";

      
    tabletext[i] += "</table>";
  }
  
  divas.innerHTML = tabletext[0]+"<br>"+tabletext[1]; 
  divassource.innerHTML='<table><tr><td><textarea id="tablecode" cols="50" rows="30">' + tabletext[0] + '</textarea></td><td><textarea id="tablecode2" cols="50" rows="30">' + tabletext[1] + '</textarea></td></tr></table>';
}

function parseEurobasket2005() {
  var tables = document.body.getElementsByTagName("TABLE");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");
  var tabletext = new Array(2);
  var teamname = new Array(2);
  var tableteam = new Array(2);
  var tempcell;
//  alert(tables.length);  

// team 1 - table 1, team 2 - table 3
  teamname[0] = tables[0].rows[0].cells[0].innerText;
//alert(teamname[0]);
  teamname[1] = tables[2].rows[0].cells[0].innerText;
//alert(teamname[1]);

  tableteam[0] = tables[1];
  tableteam[1] = tables[3];
  var i = 0;
  for(i=0; i < 2; i++)
  {
    /// parse table 1
//     alert(tables[i].rows.length);
    tabletext[i] = '';
    // get team name
    tabletext[i] += '<table width="100%" border="0" cellspacing="2" cellpadding="5" bgcolor="#cacaca">';

    tabletext[i] += '<tr bgcolor="#aaaaaa"><th>' + teamname[i] + '</th><th align="center">TÐ</th><th align="center">MN</th><th align="center">2T</th><th align="center">%2</th><th align="center">3T</th><th align="center">%3</th><th align="center">B</th><th align="center">%B</th><th align="center">AK</th><th align="center">RP</th><th align="center">PK</th><th align="center">BM</th><th align="center">KL</th><th align="center">PF</th><th align="center">KF</th></tr>';
   // get players stats
    for(j=2; j < tableteam[i].rows.length - 2; j++)
     {
      
      if (tableteam[i].rows[j].cells[1].innerText != ' ')
      {
      // change names to Capital
       if (j%2 == 1) 
         tabletext[i] += '<tr bgcolor="#eaeaea">';
       else tabletext[i] += '<tr bgcolor="#dadada">';
       var playername = tableteam[i].rows[j].cells[1].innerText.substring(0, tableteam[i].rows[j].cells[1].innerText.length-5); 
       playername = playername.replace("*", "");
       playername = playername.replace(" ", "");
       var playerindex = findPlayer(playername, i);
       tabletext[i] += "<td>" + playername + "</td>";

       // points 
      if (tableteam[i].rows[j].cells[2].innerText != "0")  // check minutes
       {
       fillField(i, playerindex, tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-1].innerText, "score_");
       fillField(i, playerindex, tableteam[i].rows[j].cells[2].innerText, "played_");

       var koeff = 0;
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-1].innerText);
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-1].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[2].innerText + "</td>";
       var scored = 0;
       var attempted = 0;
       scored = tableteam[i].rows[j].cells[3].innerText.substring(0, tableteam[i].rows[j].cells[3].innerText.indexOf("/"));
       attempted = tableteam[i].rows[j].cells[3].innerText.substring(tableteam[i].rows[j].cells[3].innerText.indexOf("/")+1, tableteam[i].rows[j].cells[3].innerText.length);
       fillField(i, playerindex, scored, "pt2_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, playerindex, attempted, "pt2_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       scored = tableteam[i].rows[j].cells[5].innerText.substring(0, tableteam[i].rows[j].cells[5].innerText.indexOf("/"));
       attempted = tableteam[i].rows[j].cells[5].innerText.substring(tableteam[i].rows[j].cells[5].innerText.indexOf("/")+1, tableteam[i].rows[j].cells[5].innerText.length);
       fillField(i, playerindex, scored, "pt3_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, playerindex, attempted, "pt3_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       scored = tableteam[i].rows[j].cells[7].innerText.substring(0, tableteam[i].rows[j].cells[7].innerText.indexOf("/"));
       attempted = tableteam[i].rows[j].cells[7].innerText.substring(tableteam[i].rows[j].cells[7].innerText.indexOf("/")+1, tableteam[i].rows[j].cells[7].innerText.length);
       fillField(i, playerindex, scored, "pt1_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, playerindex, attempted, "pt1_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       fillField(i, playerindex, tableteam[i].rows[j].cells[11].innerText, "rebounds_");
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[11].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[12].innerText, "assists_");
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[12].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[15].innerText, "steals_");
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[15].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[16].innerText, "blocks_");
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[16].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[14].innerText, "mistakes_");       
       koeff = koeff - parseInt(tableteam[i].rows[j].cells[14].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[13].innerText, "fauls_");       
       koeff = koeff - parseInt(tableteam[i].rows[j].cells[13].innerText);
       fillField(i, playerindex, "0", "unfauls_");       
       fillField(i, playerindex, koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[11].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[12].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[15].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[16].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[14].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[13].innerText + "</td>";
       tabletext[i] += '<td align="center">' + koeff + '</td>';

       tabletext[i] += "</tr>";
       }
      }
     }
    // total
       var koeff = 0;

       tabletext[i] += '<tr bgcolor="#bababa">';
       tabletext[i] += "<td>KOMANDA</td>";
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[tableteam[i].rows[tableteam[i].rows.length - 1].cells.length-1].innerText, "score_");
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[tableteam[i].rows[tableteam[i].rows.length - 1].cells.length-1].innerText + "</td>";
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[tableteam[i].rows[tableteam[i].rows.length - 1].cells.length-1].innerText);
       tempcell = window.opener.document.getElementsByName("played_"+teams[i]+"-");
       if (tempcell != null && tempcell.length > 0) 
         tempcell[0].value = "0";
       fillField(i, "", "0", "played_");
       tabletext[i] += '<td align="center">0</td>';
       var scored = 0;
       var attempted = 0;
       scored = tableteam[i].rows[tableteam[i].rows.length - 1].cells[1].innerText.substring(0, tableteam[i].rows[tableteam[i].rows.length - 1].cells[1].innerText.indexOf("/"));;
       attempted = tableteam[i].rows[tableteam[i].rows.length - 1].cells[1].innerText.substring(tableteam[i].rows[tableteam[i].rows.length - 1].cells[1].innerText.indexOf("/")+1, tableteam[i].rows[tableteam[i].rows.length - 1].cells[1].innerText.length);;

       fillField(i, "", scored, "pt2_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt2_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff -(parseInt(attempted) - parseInt(scored));
       scored = tableteam[i].rows[tableteam[i].rows.length - 1].cells[3].innerText.substring(0, tableteam[i].rows[tableteam[i].rows.length - 1].cells[3].innerText.indexOf("/"));;
       attempted = tableteam[i].rows[tableteam[i].rows.length - 1].cells[3].innerText.substring(tableteam[i].rows[tableteam[i].rows.length - 1].cells[3].innerText.indexOf("/")+1, tableteam[i].rows[tableteam[i].rows.length - 1].cells[3].innerText.length);;
       fillField(i, "", scored, "pt3_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt3_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));                                                                                      
       scored = tableteam[i].rows[tableteam[i].rows.length - 1].cells[5].innerText.substring(0, tableteam[i].rows[tableteam[i].rows.length - 1].cells[5].innerText.indexOf("/"));;
       attempted = tableteam[i].rows[tableteam[i].rows.length - 1].cells[5].innerText.substring(tableteam[i].rows[tableteam[i].rows.length - 1].cells[5].innerText.indexOf("/")+1, tableteam[i].rows[tableteam[i].rows.length - 1].cells[5].innerText.length);;
       fillField(i, "", scored, "pt1_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt1_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[9].innerText, "rebounds_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[9].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[10].innerText, "assists_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[10].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[13].innerText, "steals_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[13].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[14].innerText, "blocks_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[14].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[12].innerText, "mistakes_");       
       koeff = koeff - parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[12].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[11].innerText, "fauls_");       
       koeff = koeff - parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[11].innerText);
       fillField(i, "", "0", "unfauls_");       
       fillField(i, "", koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[9].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[10].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[13].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[14].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[12].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[11].innerText + "</td>";
       tabletext[i] += '<td align="center">' + koeff + '</td>';
       tabletext[i] += "</tr>";
  
    
    tabletext[i] += "</table>";
  }
  divas.innerHTML = tabletext[0]+"<br>"+tabletext[1]; 
  divassource.innerHTML='<table><tr><td><textarea id="tablecode" cols="50" rows="30">' + tabletext[0] + '</textarea></td><td><textarea id="tablecode2" cols="50" rows="30">' + tabletext[1] + '</textarea></td></tr></table>';

}

function parseBBL() {
  var tables = document.body.getElementsByTagName("TABLE");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");

  var tabletext = new Array(2);
  for(i=0; i < 2; i++)
  {
    /// parse table 1
//     alert(tables[i].rows.length);
    tabletext[i] = '';
    // get team name
    tabletext[i] += '<table width="100%" border="0" cellspacing="2" cellpadding="5" bgcolor="#cacaca">';
    var teamname = tables[i].rows[0].cells[0].innerText;
    tabletext[i] += '<tr bgcolor="#aaaaaa"><th>' + teamname + '</th><th align="center">TÐ</th><th align="center">MN</th><th align="center">2T</th><th align="center">%2</th><th align="center">3T</th><th align="center">%3</th><th align="center">B</th><th align="center">%B</th><th align="center">AK</th><th align="center">RP</th><th align="center">PK</th><th align="center">BM</th><th align="center">KL</th><th align="center">PZ</th><th align="center">PPZ</th><th align="center">KF</th></tr>';
   // get players stats
    for(j=2; j < tables[i].rows.length - 2; j++)
     {
       if (tables[i].rows[j].cells[3].innerText != '00:00')
       {

    //  alert(tables[i].rows[j].cells[3].innerText);
      // change names to Capital
       if (j%2 == 1) 
         tabletext[i] += '<tr bgcolor="#eaeaea">';
       else tabletext[i] += '<tr bgcolor="#dadada">';
       var playername = tables[i].rows[j].cells[1].innerText; 
       playername = playername.substring(playername.indexOf(" ") + 1, playername.length); 
       if (playername.indexOf(" ") != -1)
         playername = playername.substring(0, playername.indexOf(" ")); 
       var playerindex = findPlayer(playername, i);
       tabletext[i] += "<td>" + playername + "</td>";

       var koeff = 0;
       koeff = koeff + parseInt(tables[i].rows[j].cells[17].innerText);

       // points 
       fillField(i, playerindex, tables[i].rows[j].cells[17].innerText, "score_");

       if (tables[i].rows[j].cells[3].innerText != "00:00")
         played = tables[i].rows[j].cells[3].innerText.substring(0, tables[i].rows[j].cells[3].innerText.indexOf(":"));
       fillField(i, playerindex, played, "played_");

       tabletext[i] += '<td align="center">' + tables[i].rows[j].cells[17].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[j].cells[3].innerText + "</td>";
       var scored = 0;
       var attempted = 0;
       if (tables[i].rows[j].cells[4].innerText.substring(0, tables[i].rows[j].cells[4].innerText.indexOf("-")) != "")
         attempted = tables[i].rows[j].cells[4].innerText.substring(0, tables[i].rows[j].cells[4].innerText.indexOf("-"));
       if (tables[i].rows[j].cells[4].innerText.substring(tables[i].rows[j].cells[4].innerText.indexOf("-")+1, tables[i].rows[j].cells[4].innerText.length) != " ")
         scored = tables[i].rows[j].cells[4].innerText.substring(tables[i].rows[j].cells[4].innerText.indexOf("-")+1, tables[i].rows[j].cells[4].innerText.length);
       fillField(i, playerindex, scored, "pt2_scored_");
       fillField(i, playerindex, attempted, "pt2_thrown_");
       if (tables[i].rows[j].cells[4].innerText != '0/0')
         tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       else tabletext[i] += '<td align="center">-</td>';
       if (attempted != 0 && tables[i].rows[j].cells[4].innerText != '0/0')
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       scored = 0;
       attempted = 0;
       if (tables[i].rows[j].cells[6].innerText.substring(0, tables[i].rows[j].cells[6].innerText.indexOf("-")) != "")
         attempted = tables[i].rows[j].cells[6].innerText.substring(0, tables[i].rows[j].cells[6].innerText.indexOf("-"));
       if (tables[i].rows[j].cells[6].innerText.substring(tables[i].rows[j].cells[6].innerText.indexOf("-")+1, tables[i].rows[j].cells[6].innerText.length) != " ")
         scored = tables[i].rows[j].cells[6].innerText.substring(tables[i].rows[j].cells[6].innerText.indexOf("-")+1, tables[i].rows[j].cells[6].innerText.length);
       fillField(i, playerindex, scored, "pt3_scored_");
       fillField(i, playerindex, attempted, "pt3_thrown_");
       if (tables[i].rows[j].cells[6].innerText != '0/0')
         tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       else tabletext[i] += '<td align="center">-</td>'; 
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       scored = 0;
       attempted = 0;
       if (tables[i].rows[j].cells[7].innerText.substring(0, tables[i].rows[j].cells[7].innerText.indexOf("-")) != "")
         attempted = tables[i].rows[j].cells[7].innerText.substring(0, tables[i].rows[j].cells[7].innerText.indexOf("-"));
       if (tables[i].rows[j].cells[7].innerText.substring(tables[i].rows[j].cells[7].innerText.indexOf("-")+1, tables[i].rows[j].cells[7].innerText.length) != " ")
         scored = tables[i].rows[j].cells[7].innerText.substring(tables[i].rows[j].cells[7].innerText.indexOf("-")+1, tables[i].rows[j].cells[7].innerText.length);
       fillField(i, playerindex, scored, "pt1_scored_");
       fillField(i, playerindex, attempted, "pt1_thrown_");
       if (tables[i].rows[j].cells[7].innerText != ' ')
         tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       else tabletext[i] += '<td align="center">-</td>';
       if (attempted != 0 && tables[i].rows[j].cells[7].innerText != ' ')
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       var rebounds = 0;
       var assists = 0;  
       var steals = 0;
       var blocks = 0;
       var mistakes = 0;
       var fauls = 0;
       var unfauls = 0;

       if (tables[i].rows[j].cells[10].innerText !=" ")
         rebounds = tables[i].rows[j].cells[10].innerText;
       koeff = koeff + parseInt(tables[i].rows[j].cells[10].innerText);

       if (tables[i].rows[j].cells[11].innerText !=" ")
         assists = tables[i].rows[j].cells[11].innerText;
       koeff = koeff + parseInt(tables[i].rows[j].cells[11].innerText);

       if (tables[i].rows[j].cells[12].innerText !=" ")
         steals = tables[i].rows[j].cells[12].innerText;
       koeff = koeff + parseInt(tables[i].rows[j].cells[12].innerText);

       if (tables[i].rows[j].cells[13].innerText !=" ")
         blocks = tables[i].rows[j].cells[13].innerText;
       koeff = koeff + parseInt(tables[i].rows[j].cells[13].innerText);

       if (tables[i].rows[j].cells[14].innerText !=" ")
         mistakes = tables[i].rows[j].cells[14].innerText;
       koeff = koeff - parseInt(tables[i].rows[j].cells[14].innerText);

       if (tables[i].rows[j].cells[15].innerText !=" ")
         fauls = tables[i].rows[j].cells[15].innerText;
       koeff = koeff - parseInt(tables[i].rows[j].cells[15].innerText);

       if (tables[i].rows[j].cells[16].innerText !=" ")
         unfauls = tables[i].rows[j].cells[16].innerText;
       koeff = koeff + parseInt(tables[i].rows[j].cells[16].innerText);
    //   if (tables[i].rows[j].cells[19].innerText !=" ")
      //   koeff = tables[i].rows[j].cells[19].innerText;

       fillField(i, playerindex, rebounds, "rebounds_");
       fillField(i, playerindex, assists, "assists_");
       fillField(i, playerindex, steals, "steals_");
       fillField(i, playerindex, blocks, "blocks_");
       fillField(i, playerindex, mistakes, "mistakes_");       
       fillField(i, playerindex, fauls, "fauls_");       
       fillField(i, playerindex, unfauls, "unfauls_");       
       fillField(i, playerindex, koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + tables[i].rows[j].cells[10].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[j].cells[11].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[j].cells[12].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[j].cells[13].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[j].cells[14].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[j].cells[15].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[j].cells[16].innerText + "</td>";
       tabletext[i] += '<td align="center">' + koeff + "</td>";
//          }
       tabletext[i] += "</tr>";
       }
     }
    // total
       var koeff = 0;
       tabletext[i] += '<tr bgcolor="#bababa">';
       tabletext[i] += "<td>KOMANDA</td>";

       koeff = koeff + parseInt(tables[i].rows[tables[i].rows.length - 2].cells[16].innerText);
       fillField(i, "", tables[i].rows[tables[i].rows.length - 2].cells[16].innerText, "score_");
       played = tables[i].rows[tables[i].rows.length - 2].cells[2].innerText;

       fillField(i, "", played, "played_");

       tabletext[i] += '<td align="center">' + tables[i].rows[tables[i].rows.length - 2].cells[16].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[tables[i].rows.length - 2].cells[2].innerText + "</td>";
       var scored = 0;
       var attempted = 0;
       attempted = tables[i].rows[tables[i].rows.length - 2].cells[3].innerText.substring(0, tables[i].rows[tables[i].rows.length - 2].cells[3].innerText.indexOf("-"));
       scored = tables[i].rows[tables[i].rows.length - 2].cells[3].innerText.substring(tables[i].rows[tables[i].rows.length - 2].cells[3].innerText.indexOf("-")+1, tables[i].rows[tables[i].rows.length - 2].cells[3].innerText.length);
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       fillField(i, "", scored, "pt2_scored_");
       fillField(i, "", attempted, "pt2_thrown_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       attempted = tables[i].rows[tables[i].rows.length - 2].cells[5].innerText.substring(0, tables[i].rows[tables[i].rows.length - 2].cells[5].innerText.indexOf("-"));
       scored = tables[i].rows[tables[i].rows.length - 2].cells[5].innerText.substring(tables[i].rows[tables[i].rows.length - 2].cells[5].innerText.indexOf("-")+1, tables[i].rows[tables[i].rows.length - 2].cells[5].innerText.length);
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       
       fillField(i, "", scored, "pt3_scored_");
       fillField(i, "", attempted, "pt3_thrown_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       attempted = tables[i].rows[tables[i].rows.length - 2].cells[6].innerText.substring(0, tables[i].rows[tables[i].rows.length - 2].cells[6].innerText.indexOf("-"));
       scored = tables[i].rows[tables[i].rows.length - 2].cells[6].innerText.substring(tables[i].rows[tables[i].rows.length - 2].cells[6].innerText.indexOf("-")+1, tables[i].rows[tables[i].rows.length - 2].cells[6].innerText.length);
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       fillField(i, "", scored, "pt1_scored_");
       fillField(i, "", attempted, "pt1_thrown_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';

       var rebounds = 0;
       var assists = 0;  
       var steals = 0;
       var blocks = 0;
       var mistakes = 0;
       var fauls = 0;
       var unfauls = 0;
//       var koeff = 0;
       if (tables[i].rows[tables[i].rows.length - 2].cells[9].innerText !=" ")
         rebounds = tables[i].rows[tables[i].rows.length - 2].cells[9].innerText;
       koeff = koeff + parseInt(rebounds); 
       if (tables[i].rows[tables[i].rows.length - 2].cells[10].innerText !=" ")
         assists = tables[i].rows[tables[i].rows.length - 2].cells[10].innerText;
       koeff = koeff + parseInt(assists);
       if (tables[i].rows[tables[i].rows.length - 2].cells[11].innerText !=" ")
         steals = tables[i].rows[tables[i].rows.length - 2].cells[11].innerText;
       koeff = koeff + parseInt(steals);
       if (tables[i].rows[tables[i].rows.length - 2].cells[12].innerText !=" ")
         blocks = tables[i].rows[tables[i].rows.length - 2].cells[12].innerText;
       koeff = koeff + parseInt(blocks);
       if (tables[i].rows[tables[i].rows.length - 2].cells[13].innerText !=" ")
         mistakes = tables[i].rows[tables[i].rows.length - 2].cells[13].innerText;
       koeff = koeff - parseInt(mistakes);
       if (tables[i].rows[tables[i].rows.length - 2].cells[14].innerText !=" ")
         fauls = tables[i].rows[tables[i].rows.length - 2].cells[14].innerText;
       koeff = koeff - parseInt(fauls);
       if (tables[i].rows[tables[i].rows.length - 2].cells[15].innerText !=" ")
         unfauls = tables[i].rows[tables[i].rows.length - 2].cells[15].innerText;
       koeff = koeff + parseInt(unfauls);
//       if (tables[i].rows[tables[i].rows.length - 2].cells[19].innerText !=" ")
  //       koeff = tables[i].rows[tables[i].rows.length - 2].cells[19].innerText;

       fillField(i, "", rebounds, "rebounds_");
       fillField(i, "", assists, "assists_");
       fillField(i, "", steals, "steals_");
       fillField(i, "", blocks, "blocks_");
       fillField(i, "", mistakes, "mistakes_");       
       fillField(i, "", fauls, "fauls_");       
       fillField(i, "", unfauls, "unfauls_");       
       fillField(i, "", koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + tables[i].rows[tables[i].rows.length - 2].cells[9].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[tables[i].rows.length - 2].cells[10].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[tables[i].rows.length - 2].cells[11].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[tables[i].rows.length - 2].cells[12].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[tables[i].rows.length - 2].cells[13].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[tables[i].rows.length - 2].cells[14].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tables[i].rows[tables[i].rows.length - 2].cells[15].innerText + "</td>";
       tabletext[i] += '<td align="center">' + koeff + "</td>";
       tabletext[i] += "</tr>";
    
    tabletext[i] += "</table>";
  }
  divas.innerHTML = tabletext[0]+"<br>"+tabletext[1]; 
  divassource.innerHTML='<table><tr><td><textarea id="tablecode" cols="50" rows="30">' + tabletext[0] + '</textarea></td><td><textarea id="tablecode2" cols="50" rows="30">' + tabletext[1] + '</textarea></td></tr></table>';

}

function parseEurobasket2007() {
  var tables = document.body.getElementsByTagName("TABLE");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");
  var tabletext = new Array(2);
  var teamname = new Array(2);
  var tableteam = new Array(2);
  var tempcell;
//  alert(tables.length);  

// team 1 - table 1, team 2 - table 3
  teamname[0] = tables[9].rows[0].cells[0].innerText;
//alert(teamname[0]);
  teamname[1] = tables[14].rows[0].cells[0].innerText;
//alert(teamname[1]);

  tableteam[0] = tables[11];
  tableteam[1] = tables[16];
  var i = 0;
  for(i=0; i < 2; i++)
  {
    /// parse table 1
//     alert(tables[i].rows.length);
    tabletext[i] = '';
    // get team name
    tabletext[i] += '<table width="100%" border="0" cellspacing="2" cellpadding="5" bgcolor="#cacaca">';

    tabletext[i] += '<tr bgcolor="#aaaaaa"><th>' + teamname[i] + '</th><th align="center">TÐ</th><th align="center">MN</th><th align="center">2T</th><th align="center">%2</th><th align="center">3T</th><th align="center">%3</th><th align="center">B</th><th align="center">%B</th><th align="center">AK</th><th align="center">RP</th><th align="center">PK</th><th align="center">BM</th><th align="center">KL</th><th align="center">PF</th><th align="center">KF</th></tr>';
   // get players stats
    for(j=2; j < tableteam[i].rows.length - 2; j++)
     {
      
      if (tableteam[i].rows[j].cells[1].innerText != ' ')
      {
      // change names to Capital
       var playername = tableteam[i].rows[j].cells[1].innerText.substring(0, tableteam[i].rows[j].cells[1].innerText.length-5); 
       playername = playername.replace("*", "");
       playername = playername.replace(" ", "");
       var playernum = tableteam[i].rows[j].cells[0].innerText;
       var playerindex = findPlayer(playername, i, playernum);
       if (playerindex > 0) {
         if (j%2 == 1) 
           tabletext[i] += '<tr bgcolor="#eaeaea">';
         else tabletext[i] += '<tr bgcolor="#dadada">';
       }
       else tabletext[i] += '<tr bgcolor="#ff0000">';
       tabletext[i] += "<td>" + playername + "</td>";
//alert(playername);
       // points 
      if (tableteam[i].rows[j].cells[2].innerText != "0")  // check minutes
       {
       fillField(i, playerindex, tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-1].innerText, "score_");
       fillField(i, playerindex, tableteam[i].rows[j].cells[2].innerText, "played_");

       var koeff = 0;
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-1].innerText);
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-1].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[2].innerText + "</td>";
       var scored = 0;
       var attempted = 0;
       scored = tableteam[i].rows[j].cells[3].innerText.substring(0, tableteam[i].rows[j].cells[3].innerText.indexOf("/"));
       attempted = tableteam[i].rows[j].cells[3].innerText.substring(tableteam[i].rows[j].cells[3].innerText.indexOf("/")+1, tableteam[i].rows[j].cells[3].innerText.length);
       fillField(i, playerindex, scored, "pt2_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, playerindex, attempted, "pt2_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       scored = tableteam[i].rows[j].cells[5].innerText.substring(0, tableteam[i].rows[j].cells[5].innerText.indexOf("/"));
       attempted = tableteam[i].rows[j].cells[5].innerText.substring(tableteam[i].rows[j].cells[5].innerText.indexOf("/")+1, tableteam[i].rows[j].cells[5].innerText.length);
       fillField(i, playerindex, scored, "pt3_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, playerindex, attempted, "pt3_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       scored = tableteam[i].rows[j].cells[7].innerText.substring(0, tableteam[i].rows[j].cells[7].innerText.indexOf("/"));
       attempted = tableteam[i].rows[j].cells[7].innerText.substring(tableteam[i].rows[j].cells[7].innerText.indexOf("/")+1, tableteam[i].rows[j].cells[7].innerText.length);
       fillField(i, playerindex, scored, "pt1_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, playerindex, attempted, "pt1_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       fillField(i, playerindex, tableteam[i].rows[j].cells[11].innerText, "rebounds_");
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[11].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[12].innerText, "assists_");
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[12].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[15].innerText, "steals_");
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[15].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[16].innerText, "blocks_");
       koeff = koeff + parseInt(tableteam[i].rows[j].cells[16].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[14].innerText, "mistakes_");       
       koeff = koeff - parseInt(tableteam[i].rows[j].cells[14].innerText);
       fillField(i, playerindex, tableteam[i].rows[j].cells[13].innerText, "fauls_");       
       koeff = koeff - parseInt(tableteam[i].rows[j].cells[13].innerText);
       fillField(i, playerindex, "0", "unfauls_");       
       fillField(i, playerindex, koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[11].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[12].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[15].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[16].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[14].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[j].cells[13].innerText + "</td>";
       tabletext[i] += '<td align="center">' + koeff + '</td>';

       tabletext[i] += "</tr>";
       }
      }
     }
    // total
       var koeff = 0;

       tabletext[i] += '<tr bgcolor="#bababa">';
       tabletext[i] += "<td>KOMANDA</td>";
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[tableteam[i].rows[tableteam[i].rows.length - 1].cells.length-1].innerText, "score_");
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[tableteam[i].rows[tableteam[i].rows.length - 1].cells.length-1].innerText + "</td>";
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[tableteam[i].rows[tableteam[i].rows.length - 1].cells.length-1].innerText);
       tempcell = window.opener.document.getElementsByName("played_"+teams[i]+"-");
       if (tempcell != null && tempcell.length > 0) 
         tempcell[0].value = "0";
       fillField(i, "", "0", "played_");
       tabletext[i] += '<td align="center">0</td>';
       var scored = 0;
       var attempted = 0;
       scored = tableteam[i].rows[tableteam[i].rows.length - 1].cells[1].innerText.substring(0, tableteam[i].rows[tableteam[i].rows.length - 1].cells[1].innerText.indexOf("/"));;
       attempted = tableteam[i].rows[tableteam[i].rows.length - 1].cells[1].innerText.substring(tableteam[i].rows[tableteam[i].rows.length - 1].cells[1].innerText.indexOf("/")+1, tableteam[i].rows[tableteam[i].rows.length - 1].cells[1].innerText.length);;

       fillField(i, "", scored, "pt2_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt2_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff -(parseInt(attempted) - parseInt(scored));
       scored = tableteam[i].rows[tableteam[i].rows.length - 1].cells[3].innerText.substring(0, tableteam[i].rows[tableteam[i].rows.length - 1].cells[3].innerText.indexOf("/"));;
       attempted = tableteam[i].rows[tableteam[i].rows.length - 1].cells[3].innerText.substring(tableteam[i].rows[tableteam[i].rows.length - 1].cells[3].innerText.indexOf("/")+1, tableteam[i].rows[tableteam[i].rows.length - 1].cells[3].innerText.length);;
       fillField(i, "", scored, "pt3_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt3_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));                                                                                      
       scored = tableteam[i].rows[tableteam[i].rows.length - 1].cells[5].innerText.substring(0, tableteam[i].rows[tableteam[i].rows.length - 1].cells[5].innerText.indexOf("/"));;
       attempted = tableteam[i].rows[tableteam[i].rows.length - 1].cells[5].innerText.substring(tableteam[i].rows[tableteam[i].rows.length - 1].cells[5].innerText.indexOf("/")+1, tableteam[i].rows[tableteam[i].rows.length - 1].cells[5].innerText.length);;
       fillField(i, "", scored, "pt1_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt1_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[9].innerText, "rebounds_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[9].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[10].innerText, "assists_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[10].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[13].innerText, "steals_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[13].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[14].innerText, "blocks_");
       koeff = koeff + parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[14].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[12].innerText, "mistakes_");       
       koeff = koeff - parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[12].innerText);
       fillField(i, "", tableteam[i].rows[tableteam[i].rows.length - 1].cells[11].innerText, "fauls_");       
       koeff = koeff - parseInt(tableteam[i].rows[tableteam[i].rows.length - 1].cells[11].innerText);
       fillField(i, "", "0", "unfauls_");       
       fillField(i, "", koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[9].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[10].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[13].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[14].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[12].innerText + "</td>";
       tabletext[i] += '<td align="center">' + tableteam[i].rows[tableteam[i].rows.length - 1].cells[11].innerText + "</td>";
       tabletext[i] += '<td align="center">' + koeff + '</td>';
       tabletext[i] += "</tr>";
  
    
    tabletext[i] += "</table>";
  }
  divas.innerHTML = tabletext[0]+"<br>"+tabletext[1]; 
  divassource.innerHTML='<table><tr><td><textarea id="tablecode" cols="50" rows="30">' + tabletext[0] + '</textarea></td><td><textarea id="tablecode2" cols="50" rows="30">' + tabletext[1] + '</textarea></td></tr></table>';

}


function parseEurobasket2011() {
  var tables = document.body.getElementsByTagName("TABLE");
  var divas = document.getElementById("newtable");
  var divassource = document.getElementById("newtablesource");
  var divs = document.body.getElementsByTagName("DIV");
  var tabletext = new Array(2);
  var teamname = new Array(2);
  var tableteam = new Array(2);
  var tempcell;
//  alert(tables.length);  

// team 1 - table 1, team 2 - table 3
  teamname[0] = getText(divs[3]);
  teamname[1] = getText(divs[5]);

  tableteam[0] = tables[2];
  tableteam[1] = tables[3];
  var i = 0;
  for(i=0; i < 2; i++)
  {
    /// parse table 1
    tabletext[i] = '';
    // get team name
    tabletext[i] += '<table width="100%" border="0" cellspacing="2" cellpadding="5" bgcolor="#cacaca">';

    tabletext[i] += '<tr bgcolor="#aaaaaa"><th>' + teamname[i] + '</th><th align="center">TÐ</th><th align="center">MN</th><th align="center">2T</th><th align="center">%2</th><th align="center">3T</th><th align="center">%3</th><th align="center">B</th><th align="center">%B</th><th align="center">AK</th><th align="center">RP</th><th align="center">PK</th><th align="center">BM</th><th align="center">KL</th><th align="center">PF</th><th align="center">KF</th></tr>';
   // get players stats
    for(j=2; j < tableteam[i].rows.length - 2; j++) {
      
      if (getText(tableteam[i].rows[j].cells[1]) != ' ')
      {
      // change names to Capital
       var playername = getText(tableteam[i].rows[j].cells[1]).substring(0, getText(tableteam[i].rows[j].cells[1]).length-4); 
       playername = playername.replace("*", "");
       playername = playername.replace(" ", "");
       var playernum = getText(tableteam[i].rows[j].cells[0]);
       playernum = playernum.replace("*", "");
       var playerindex = findPlayer(playername, i, playernum);
       if (playerindex > 0) {
         if (j%2 == 1) 
           tabletext[i] += '<tr bgcolor="#eaeaea">';
         else tabletext[i] += '<tr bgcolor="#dadada">';
       }
       else tabletext[i] += '<tr bgcolor="#ff0000">';
       tabletext[i] += "<td>" + playername + "</td>";
//alert(playername);
       // points 
      if (getText(tableteam[i].rows[j].cells[2]) != "DNP")  // check minutes
       {
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-1]), "score_");
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[2]), "played_");

       var koeff = 0;
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-1]));
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[tableteam[i].rows[j].cells.length-1]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[2]) + "</td>";
       var scored = 0;
       var attempted = 0;
       scored = getText(tableteam[i].rows[j].cells[9]).substring(0, getText(tableteam[i].rows[j].cells[9]).indexOf("/"));
       attempted = getText(tableteam[i].rows[j].cells[9]).substring(getText(tableteam[i].rows[j].cells[9]).indexOf("/")+1, getText(tableteam[i].rows[j].cells[9]).length);
       fillField(i, playerindex, scored, "pt1_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, playerindex, attempted, "pt1_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       scored = getText(tableteam[i].rows[j].cells[5]).substring(0, getText(tableteam[i].rows[j].cells[5]).indexOf("/"));
//////------------------------------
       attempted = getText(tableteam[i].rows[j].cells[5]).substring(getText(tableteam[i].rows[j].cells[5]).indexOf("/")+1, getText(tableteam[i].rows[j].cells[5]).length);
       fillField(i, playerindex, scored, "pt2_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, playerindex, attempted, "pt2_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));
       scored = getText(tableteam[i].rows[j].cells[7]).substring(0, getText(tableteam[i].rows[j].cells[7]).indexOf("/"));
       attempted = getText(tableteam[i].rows[j].cells[7]).substring(getText(tableteam[i].rows[j].cells[7]).indexOf("/")+1, getText(tableteam[i].rows[j].cells[7]).length);
       fillField(i, playerindex, scored, "pt3_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, playerindex, attempted, "pt3_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[13]), "rebounds_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[13]));
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[14]), "assists_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[14]));
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[16]), "steals_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[16]));
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[17]), "blocks_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[j].cells[17]));
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[15]), "mistakes_");       
       koeff = koeff - parseInt(getText(tableteam[i].rows[j].cells[15]));
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[18]), "fauls_");       
       koeff = koeff - parseInt(getText(tableteam[i].rows[j].cells[18]));
       fillField(i, playerindex, getText(tableteam[i].rows[j].cells[19]), "unfauls_");       
       koeff = koeff - parseInt(getText(tableteam[i].rows[j].cells[19]));

       fillField(i, playerindex, koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[13]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[14]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[16]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[17]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[15]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[18]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[j].cells[19]) + "</td>";
       tabletext[i] += '<td align="center">' + koeff + '</td>';

       tabletext[i] += "</tr>";
       }
      }
     }
    // total
       var koeff = 0;

       tabletext[i] += '<tr bgcolor="#bababa">';
       tabletext[i] += "<td>KOMANDA</td>";
       fillField(i, "", getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[tableteam[i].rows[tableteam[i].rows.length - 1].cells.length-1]), "score_");
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[tableteam[i].rows[tableteam[i].rows.length - 1].cells.length-1]) + "</td>";
       koeff = koeff + parseInt(getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[tableteam[i].rows[tableteam[i].rows.length - 1].cells.length-1]));
       tempcell = window.opener.document.getElementsByName("played_"+teams[i]+"-");
       if (tempcell != null && tempcell.length > 0) 
         tempcell[0].value = "0";
       fillField(i, "", "0", "played_");
       tabletext[i] += '<td align="center">0</td>';
       var scored = 0;
       var attempted = 0;
       scored = getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[7]).substring(0, getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[7]).indexOf("/"));;
       attempted = getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[7]).substring(getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[7]).indexOf("/")+1, getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[7]).length);;

       fillField(i, "", scored, "pt1_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt1_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff -(parseInt(attempted) - parseInt(scored));
       scored = getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[3]).substring(0, getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[3]).indexOf("/"));;
       attempted = getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[3]).substring(getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[3]).indexOf("/")+1, getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[3]).length);;
       fillField(i, "", scored, "pt2_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt2_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));                                                                                      
       scored = getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[5]).substring(0, getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[5]).indexOf("/"));;
       attempted = getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[5]).substring(getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[5]).indexOf("/")+1, getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[5]).length);;
       fillField(i, "", scored, "pt3_scored_");
       tabletext[i] += '<td align="center">' + scored+ "/" + attempted  + "</td>";
       fillField(i, "", attempted, "pt3_thrown_");
       if (attempted != 0)
          tabletext[i] += '<td align="center">' + Math.round((scored/attempted)*100) + "%</td>";
       else tabletext[i] += '<td align="center">-</td>';
       koeff = koeff - (parseInt(attempted) - parseInt(scored));

       fillField(i, "", getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[11]), "rebounds_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[11]));
       fillField(i, "", getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[12]), "assists_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[12]));
       fillField(i, "", getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[14]), "steals_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[14]));
       fillField(i, "", getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[15]), "blocks_");
       koeff = koeff + parseInt(getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[15]));
       fillField(i, "", getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[13]), "mistakes_");       
       koeff = koeff - parseInt(getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[13]));
       fillField(i, "", getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[16]), "fauls_");       
       koeff = koeff - parseInt(getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[16]));
       fillField(i, "", getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[17]), "unfauls_");       
       koeff = koeff - parseInt(getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[17]));

       fillField(i, "", koeff, "koeff_");       

       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[9]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[10]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[13]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[14]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[12]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[11]) + "</td>";
       tabletext[i] += '<td align="center">' + getText(tableteam[i].rows[tableteam[i].rows.length - 1].cells[17]) + "</td>";
       tabletext[i] += '<td align="center">' + koeff + '</td>';
       tabletext[i] += "</tr>";
  
    
    tabletext[i] += "</table>";
  }
  divas.innerHTML = tabletext[0]+"<br>"+tabletext[1]; 
  divassource.innerHTML='<table><tr><td><textarea id="tablecode" cols="50" rows="30">' + tabletext[0] + '</textarea></td><td><textarea id="tablecode2" cols="50" rows="30">' + tabletext[1] + '</textarea></td></tr></table>';

}

</script>

<body>
<form action="" method="post" name="form1">
1) URL 
  <input type="text" name="url" size="90">
  <input type="submit" name="Submit" value="Submit"><br>
  NBA<input name="radiobutton" type="radio" value="nba.com" checked>
  LKL<input type="radio" name="radiobutton" value="lkl.lt">
  Euroleague<input type="radio" name="radiobutton" value="euroleague.net">
  Atenai 2004<input type="radio" name="radiobutton" value="athens2004.com">
  Eurobasket 2005<input type="radio" name="radiobutton" value="eurobasket2005.com">
  BBL<input type="radio" name="radiobutton" value="bbl.net">
  Fiba<input type="radio" name="radiobutton" value="fiba.com">
<!--  Eurobasket 2007<input type="radio" name="radiobutton" value="eurobasket2007.org"> -->
  Eurobasket 2011<input type="radio" name="radiobutton" value="eurobasket2011.com">
</form>
2) <input name="parsetable" type="button" onClick="parse()" value="Paimti lenteles">

<?php
flush (); 
if (!empty($_POST['url']))
{
$fetch_domain = parse_url($_POST['url']); 
$fetch_domain = $fetch_domain['host']; 

//$socket_handle = fsockopen($fetch_domain, 80, $error_nr, $error_txt,30); 
echo $fetch_domain;
$times = 1;

if ($fetch_domain == "scores.nba.com")
{ 
  $pradzia = "<!-- START  Visitor player Stats team table -->"; 
  $pabaiga = "<!-- START GAME SUMMARY -->"; 
}
else if ($fetch_domain == "www.euroleague.net" || $fetch_domain == "217.13.120.170" || $fetch_domain == "www.eurocupbasketball.com")
    {
     $pradzia = '<div class="TeamStatsMainContainer">';
     $pabaiga = '<div id="sg-playbyplay" class="sg-panel';
    }
else if ($fetch_domain == "www.krepsinis.net" || $fetch_domain == "www.lkl.lt")
    {
//     $pradzia = "</table>\n\n<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\">";
     $pradzia = "<div class=\"big-block\">";
     $pabaiga = "<!-- /middle-col-left -->";
     $times = 2;
    }
else if ($fetch_domain == "lkl.selfip.com")
    {
     $pradzia = "</table>\n<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\">";
//     $pradzia = "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\">";
     $pabaiga = "</body>";
    }
else if ($fetch_domain == "www.athens2004.com")
    {
     $pradzia = '<table class="otenet_results_table" summary="Table containing statistics about each player">';
     $pabaiga = '<table class="otenet_results_table" summary="Table with the officials of the match">';
    }
else if ($fetch_domain == "www.eurobasket2005.com")
    {
     $pradzia = '<table cellspacing="0" cellpadding="3" border="0" width="100%">';
     $pabaiga = '</table><div align="right">';
    }
else if ($fetch_domain == "www.bbl.net")
    {
     $pradzia = '<td colspan="3">';
     $pabaiga = '</body>';
    }
else if ($fetch_domain == "www.fiba.com" || $fetch_domain == "london2012.fiba.com")
    {
     $pradzia = '<!--DIVstart-lib_63072-->';
     $pabaiga = '<!--DIVend-lib_63072-->';
    }
else if ($fetch_domain == "www.eurobasket2007.org")
    {
     $pradzia = '<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td class="content_yellowTable"><div class="caption_yellowTable">';
     $pabaiga = '<span class="lbl_hdr"><b>Legend</b></span>';
    }
else if ($fetch_domain == "www.eurobasket2011.com" || $fetch_domain == "www.eurobasket2013.org")
    {
     $pradzia = '<div id="TABBED_DIV_2" class="tabcontent"><table cellspacing="0" cellpadding="0" border="0" width="100%"><tr><td valign="top" width="67%">';
     $pabaiga = '<div class="button-left">Legend</div>';
    }

else if ($fetch_domain == "www.nba.com")
    {
     $pradzia = '<div id="nbaGIboxscore" class="nbaGameInfoContainer panel">';
     $pabaiga = '<div id="nbaGIStatus">';
    }

/*if(!$socket_handle) {
echo "Negaliu prisijungti prie ".$fetch_domain;
exit;
} */
$handle = fopen($_POST['url'], "rb");
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
    $pradzia = strpos($fd, $pradzia); 
echo $pradzia;
    $pabaiga = strpos($fd, $pabaiga); 
    $length = $pabaiga - $pradzia; 
 //   echo $length;
    $code = substr($fd, $pradzia, $length); 
    $code = trim($code); 
    print "<body><div id='newtable'></div><div id='newtablesource'></div>".$code."</body>";
   }
 }
?>
</body>
</html>