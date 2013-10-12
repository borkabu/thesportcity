function handleComment(post_id, action, actkey, div) {
  submitRequest('get', 'moderate_comment.php?action='+ action + '&post_id=' + post_id + '&actkey='+actkey, div);
}

function handleContent(item_id, action, actkey, div) {
  submitRequest('get', 'moderate_content.php?action='+ action + '&item_id=' + item_id + '&actkey='+actkey, div);
}

function voteCommentThumbUp(post_id, div) {
  submitRequest('get', 'comment_vote.php?action=thumbup&post_id=' + post_id, div);
}

function voteCommentThumbDown(post_id, div) {
  submitRequest('get', 'comment_vote.php?action=thumbdown&post_id=' + post_id, div);
}

function voteContentThumbUp(item_id, mode, div) {
  submitRequest('get', 'content_vote.php?action=thumbup&mode='+mode +'&item_id=' + item_id, div);
}

function voteContentThumbDown(item_id, mode, div) {
  submitRequest('get', 'content_vote.php?action=thumbdown&mode='+mode +'&item_id=' + item_id, div);
}

function voteLeagueThumbUp(item_id, league_type, mode, div) {
  submitRequest('get', 'league_vote.php?action=thumbup&mode='+mode +'&item_id=' + item_id + '&league_type=' + league_type, div);
}

function voteLeagueThumbDown(item_id, league_type, mode, div) {
  submitRequest('get', 'league_vote.php?action=thumbdown&mode='+mode +'&item_id=' + item_id + '&league_type=' + league_type, div);
}

function deletePost(post_id, topic_id, div) {
  submitRequest('get', 'delete_post.php?action=delete_post&post_id=' + post_id+ '&topic_id=' + topic_id, div);
}

function createFMTeam(div) {
  submitRequest('get', 'f_manager_init.php', div);
}

function createSoloTeam(div) {
  submitRequest('get', 'solo_manager_init.php', div);
}

function startFMBattle(div) {
  submitRequest('get', 'f_manager_battle_init.php', div);
}

function flipTeamStatus(season_id, param, div) {
  submitRequest('get', 'f_manager_team_status.php?flip=true&season_id=' + season_id + '&param=' + param, div);
}

function flipSoloTeamStatus(season_id, param, div) {
  submitRequest('get', 'solo_manager_team_status.php?flip=true&season_id=' + season_id + '&param=' + param, div);
}

function flipWagerAccountStatus(season_id, param, div) {
  submitRequest('get', 'wager_team_status.php?flip=true&season_id=' + season_id + '&param=' + param, div);
}

function flipArrangerAccountStatus(season_id, param, div) {
  submitRequest('get', 'bracket_team_status.php?flip=true&season_id=' + season_id + '&param=' + param, div);
}

function createFMLeague(div) {
  submitRequest('get', 'f_manager_league_init.php', div);
}

function createFLLeague(div) {
  submitRequest('get', 'rvs_manager_league_init.php', div);
}

function createFMTournament(div) {
  submitRequest('get', 'f_manager_tournament_init.php', div);
}

function createWagerAccount(div) {
  submitRequest('get', 'wager_init.php', div);
}

function createWagerLeague(div) {
  submitRequest('get', 'wager_league_init.php', div);
}

function createArrangerAccount(div) {
  submitRequest('get', 'bracket_init.php', div);
}

function createArrangerLeague(div) {
  submitRequest('get', 'bracket_league_init.php', div);
}

function getManagerPlayerInfo() {
  submitRequestSimple('get', 'f_manager_player_info.php');
}

function handleLeagueInvitation(league_id, action, div) {
  submitRequest('get', 'f_manager_league_summary.php?league_id='+league_id+'&action='+action, div);
}

function handleRvsLeagueInvitation(league_id, action, div) {
  submitRequest('get', 'rvs_manager_league_summary.php?league_id='+league_id+'&action='+action, div);
}

function handleWagerLeagueInvitation(league_id, action, div) {
  submitRequest('get', 'wager_league_summary.php?league_id='+league_id+'&action='+action, div);
}

function handleArrangerLeagueInvitation(league_id, action, div) {
  submitRequest('get', 'bracket_league_summary.php?league_id='+league_id+'&action='+action, div);
}

function handleTournamentInvitation(mt_id, action, div) {
  submitRequest('get', 'f_manager_tournament_summary.php?mt_id='+mt_id+'&action='+action, div);
}

function handleClanInvitation(clan_id, action, div) {
  submitRequest('get', 'clan_summary.php?clan_id='+clan_id+'&action='+action, div);
}

function convertCreditsTransactions(season_id, credits, div) {
  submitSingleRequestMultipleResponces('get', 'f_manager_convert_transactions.php?season_id=' + season_id + '&credits=' + credits, div);
}

function transferStockMoney (season_id, money, div) {
  submitSingleRequestMultipleResponces('get', 'f_manager_transfer_money.php?season_id=' + season_id + '&money=' + money, div);
}

function subscribeTopic(topic_id, div) {
  submitRequest('get', 'topic_subscribe.php?action=subscribe&topic_id=' + topic_id, div);
}

function unsubscribeTopic(topic_id, div) {
  submitRequest('get', 'topic_subscribe.php?action=unsubscribe&topic_id=' + topic_id, div);
}

function subscribeNewsletter(newsletter_id, div) {
  submitRequest('get', 'newsletter_subscribe.php?action=subscribe&mode=user&newsletter_id=' + newsletter_id, div);
}

function unsubscribeNewsletter(newsletter_id, div) {
  submitRequest('get', 'newsletter_subscribe.php?action=unsubscribe&mode=user&newsletter_id=' + newsletter_id, div);
}

function answerQuestion(question_id, form, div) {
  submitRequest('post', 'survey_answer_question.php', div, 'question_id=' +  question_id + '&' + parseForm(form));
}

function resetVote(question_id, div) {
  submitRequest('post', 'survey_answer_question.php', div, 'reset=y&question_id=' +  question_id);
}

function answerQuestion2(question_id, form, div) {
  submitRequest('post', 'survey_answer_question.php', div, 'box=true&question_id=' +  question_id + '&' + parseForm(form));
}

function setCaptain(entry_id,season_id) {
  submitSingleRequestMultipleResponces('get', 'f_manager_set_captain.php?entry_id='+entry_id+'&season_id='+season_id+'&time='+Number(new Date()), 'captain_' + entry_id);
}

function performDrafts(league_id, player_id, div, form) {
  submitRequest('get', 'rvs_manager_perform_drafts.php?league_id='+league_id, div, 'league_id=' + league_id + '&player_id=' + player_id + '&draft_pick=Y');
}

function suggestAnswer(question_id, form) {
  submitRequest('post', 'survey_answer_suggest.php', 'suggest2_' + question_id, 'question_id=' +  question_id + '&' + parseForm(form));
}

function suggestCategory(form) {
  submitRequest('post', 'category_suggest.php', 'suggest2_cat', parseForm(form));
}

function challengeUser(user_id, season_id, div, type, stake) {
  if (type== 1)
    submitRequest('get', 'f_manager_challenge_user.php?action=challenge_throw&user_id=' + user_id+'&season_id=' + season_id+'&stake=' + stake + '&type=' + type, div);
  else if (type ==2) 
	submitSingleRequestMultipleResponces('get', 'f_manager_challenge_user.php?action=challenge_throw&user_id=' + user_id+'&season_id=' + season_id+'&stake=' + stake + '&type=' + type, div);
}

function unchallengeUser(user_id, season_id, type, div) {
  if (type == 1)
    submitRequest('get', 'f_manager_challenge_user.php?action=remove_challenge&user_id=' + user_id+'&season_id=' + season_id + '&type=' + type, div);
  else if (type == 2) 
    submitSingleRequestMultipleResponces('get', 'f_manager_challenge_user.php?action=remove_challenge&user_id=' + user_id+'&season_id=' + season_id + '&type=' + type, div);
}

function handleChallengeInvitation(challenge_id, action, div) {
  submitSingleRequestMultipleResponces('get', 'f_manager_challenge_summary.php?challenge_id='+challenge_id+'&action='+action, div);
}

function submitWager(wager_id, form, div, credits) {
//alert(form);
  submitSingleRequestMultipleResponces('post', 'wager_bet.php', div, 'wager_id=' +  wager_id + '&credits=' + credits + '&' + parseForm(form));
}

function refillWagerAccount(season_id, div) {
  submitSingleRequestMultipleResponces('get', 'wager_convert_money.php?season_id=' + season_id, div);
}

function stockNotification(season_id, player_id, mode, div) {
  submitRequest('get', 'f_manager_stock_exchange_notification.php?mode='+mode+'&season_id=' + season_id + '&player_id=' + player_id, div);
}

function pexNotification(league_id, mode, div) {
  submitRequest('get', 'rvs_manager_pex_notification_switch.php?mode='+mode+'&league_id=' + league_id, div);
}

function reportPlayerState(player_id, season_id) {
  // insert new tr with div
  var check = document.getElementById("report_"+player_id);
//alert(check);
  if (check == null) { 
    var div ="report_"+player_id;
    var row = document.getElementById("market_tr_" + player_id);
    var table = row.parentNode.parentNode;
    var new_row = table.insertRow(row.rowIndex+1);
    var new_cell = new_row.insertCell(0);
    new_row.id = "market_reports_" + player_id;
    new_cell.colSpan = row.cells.length;
    new_cell.innerHTML="<div id='"+div+"'></div>";
    submitRequest('get', 'f_manager_player_reports.php?player_id=' + player_id+'&season_id='+season_id, div);
  }
}

function hideReports(player_id) {
    var row = document.getElementById("market_reports_" + player_id);
    if (row != null) {
      row.parentNode.removeChild(row);
    }
}

function playerStateUpdate(season_id, player_id, mode, div) {
  submitRequest('get', 'f_manager_player_state_update.php?mode='+mode+'&season_id=' + season_id + '&player_id=' + player_id, div);
}

function addUsersReceipients(pm_id, text, div) {
  text = text.replace(/\n\r/g,";");
  text = text.replace(/\n/g,";");
  submitRequest('get', 'add_receipients.php?message_id='+pm_id+'&mode=users&text=' + text, div);
}

function addGroupsReceipients(pm_id, groups, div) {
  var pm_groups = '';
  for (var i = 0; i < groups.options.length; i++) 
    if (groups.options[ i ].selected) 
      pm_groups += groups.options[ i ].value + "|";

  submitRequest('get', 'add_receipients.php?message_id='+pm_id+'&mode=groups&pm_groups=' + pm_groups, div);
}

function removeUserReceipient(pm_id, user, div) {
  submitRequest('get', 'remove_receipients.php?message_id='+pm_id+'&mode=users&user=' + user, div);
}

function removeGroupReceipient(pm_id, group, div) {
  submitRequest('get', 'remove_receipients.php?message_id='+pm_id+'&mode=groups&group=' + group, div);
}

function wagerThrowChallenge(game_id, stake, div, form) {
  submitSingleRequestMultipleResponces('get', 'wager_throw_challenge.php?stake=' + stake + '&game_id=' + game_id + '&' + parseForm(form), div);
}

function wagerWithdrawChallenge(game_id, div) {
  submitSingleRequestMultipleResponces('get', 'wager_withdraw_challenge.php?game_id=' + game_id, div);
}

function wagerAcceptChallenge(challenge_id, div) {
  submitSingleRequestMultipleResponces('get', 'wager_accept_challenge.php?challenge_id=' + challenge_id, div);
}

function markPlayer(season_id, player_id, div) {
  submitRequest('get', 'f_manager_mark_player.php?action=mark&player_id='+player_id+'&season_id='+season_id, div);
}

function unmarkPlayer(season_id, player_id, div) {
  submitRequest('get', 'f_manager_mark_player.php?action=unmark&player_id='+player_id+'&season_id='+season_id, div);
}

function createClan(div) {
  submitRequest('get', 'clan_init.php', div);
}

function createClanTeam(div, season_id, clan_id, event_type) {
  submitRequest('get', 'clan_team_init.php?season_id=' + season_id + '&clan_id=' + clan_id + '&event_type=' + event_type, div);
}

