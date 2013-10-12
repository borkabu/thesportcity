function updateAnswers(question_id, answers, div) {
    //make a connection to the server ... specifying that you intend to make a GET request 
    //to the server. Specifiy the page name and the URL parameters to send
    submitRequest('post', 'survey_answers_load.php', div, 'answers='+ answers + '&question_id=' + question_id);
}

function setPublish(user_id, season_id, type, mode, div_id) {
  submitRequest('get', 'ajax_activate_deactivate.php?user_id=' + user_id + '&season_id=' + season_id + '&type=' + type + "&" + mode + "=Y", 'publish_' + user_id + '_' + season_id, div_id);
}

function playerStateUpdate(season_id, player_id, mode, div) {
  submitRequest('get', 'manager_player_state_update.php?mode='+mode+'&season_id=' + season_id + '&player_id=' + player_id, div);
}

function prolongTime(id, div_id) {
  submitRequest('get', 'ajax_prolong_time.php?id=' + id , div_id);
}

function playerNumberUpdate(member_id, num, div) {
  submitRequest('get', 'manager_player_number_update.php?mode=update&member_id=' + member_id + '&num=' + num, div);
}
