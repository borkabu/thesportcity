<?php

include('class/ss_utils.inc.php');
 $utils = new SS_Utils();
include('class/ss_battle_protocol.inc.php');
include('class/ss_battlesbox.inc.php');
 $battlebox = new SS_BattleBox($langs, $_SESSION['_lang']);
include('class/ss_battle.inc.php');
?>
