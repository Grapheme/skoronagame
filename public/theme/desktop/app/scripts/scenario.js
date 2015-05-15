var firstTime = new Date().getTime()/1000;
var search_timeout = 90;
var quiz_timer = 10;

function renderPlayers() {
  $.each(GAME.users, function(index, value){
    if (value.id != GAME.user.id) {
      var is_reserved = false;
      $('#mathcmaking .ava .name').each(function(){
        if ($(this).text()==value.name) {
          is_reserved = true;
        }
      });
      if (is_reserved == false) {
        $('#mathcmaking .ava:not(.reserved):first .name').text(value.name).closest('.ava').addClass('reserved');
      }
    }
    
    $('#question-1 .left .unit').eq(index).find('.name').text(value.name);
    
  });
}

function renderMap() {
  $('.temp-map').html('');
  $.each(GAME.map, function(index, value){
    $('<div> \
      capital:'+ value.capital+'<br>\
      id:'+ value.id+'<br>\
      lives:'+ value.lives+'<br>\
      user_id:'+ value.user_id+'<br>\
      zone:'+ value.zone+'\
    </div>').appendTo('.temp-map').css({
      "background-color":value.settings.color
    });
  });
}

function quizTimerStart() {
  $('#question-1 .right .timer').text(quiz_timer);
  setInterval(function(){
    var _timer = $('#question-1 .right .timer').text();
    $('#question-1 .right .timer').text(_timer-1);
  }, 1000)
}

function getQuize() {
  GAME.users_question=[];
  GAME.getQuizQuestion(function(){
    var qn = parseInt($('#question-1 .title .qn').text());
    $('#question-1 .title .qn').text(qn+1);
    $('#question-1 .right .q').html(GAME.question.text);
    openFrame('question-1');
    showPoppups();
    quizTimerStart();
    //startOrSearch();
  });
}

function startOrSearch() {
  GAME.game_id = _skoronagame_.game_id;
  GAME.getGame(function(){
    renderPlayers();
    if (GAME.status == "wait") {
      
      var now = new Date().getTime()/1000;
      if (now-firstTime<search_timeout) {
        
      } else {
        alert('Игроки не найдены!')
      }
      
    } else if (GAME.status == "start") {
      
      //setTimeout(hidePoppups, 500)
      //startOrSearch();
      setTimeout(getQuize(), 1000);
    } else if (GAME.status == "ready") {
      getQuize();
      renderMap();
    } else if (GAME.status == "over") {
      alert('Игра завершена одним из игроков.');
      location.href="/game"
    }
    console.log(GAME.status)
    window.setTimeout(startOrSearch, 5000);
  });
}

$('#question-1 form.a').submit(function(){
  GAME.question.answer = $(this).find('input').val();
  GAME.question.time = 10 - $('#question-1 .right .timer').text()
  GAME.sendQuestionAnswer();
  GAME.getResultQuestion();
});