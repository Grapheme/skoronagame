var firstTime = new Date().getTime()/1000;
var search_timeout = 90;
var quiz_timer_default = 10;

var quiz_interval;

function startQuizeTimer() {
  var timer = quiz_timer_default;
  $('#question-1 .right .timer').text(timer);
  quiz_interval = setInterval(function(){
    $('#question-1 .right .timer').text(timer);
    timer--
  }, 1000)
}

function quizQuesionRender() {
  clearInterval(quiz_interval);
  renderPlayers();
  getQuizQuestion(callback=function(){
    $('#question-1 form.a, #question-1 .numpad, #question-1 .right .timer').slideDown();
    $('#question-1 .right .q').html(GAME.question.text);
    openFrame('question-1');
    showPoppups();
    startQuizeTimer();
  });
}

function takingLand() {
  getGame(function(){
    console.log(GAME.status);
    quizQuesionRender();
  });
}

function matchmaking() {
  getGame(function(){
    if (GAME.status == 'wait') {
      renderPlayers();
      setTimeout(matchmaking, 1000)
    } else {
      renderPlayers();
      if (GAME.status == "start" || GAME.status == "ready") {
        renderMap(true);
        hidePoppups();
        takingLand();
        //setTimeout(takingLand, 10000);
      }
    }
  });
}


function renderPlayers() {
  $.each(GAME.users, function(index, value){
    $('#question-1 .left .unit').eq(index).find('.name').text(value.name).css({
      color: value.color
    });  
  });
  $.each(GAME.enemies, function(index, value){
    if (index==0) {
      $('#mathcmaking .ava').first().find('.name').text(value.name);
    }
    if (index==1) {
      $('#mathcmaking .ava').last().find('.name').text(value.name);
    }
    /*if (value.id != GAME.user.id) {
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
    
    */
    
  });
}

$('body').on('click', '.temp-map div', function(event){
  if (GAME.user.id == GAME.next_turn && GAME.stage == 1 && !$(this).hasClass('reserved')) {
    sendConquestEmptyTerritory($(this).data('zone'),function(){
      getGame(function(){
        renderMap(true);
      });
    });
  } else if (GAME.user.id == GAME.next_turn && GAME.stage == 2 && $(this).data('info').user_id != GAME.user.id) {
    renderNormalQuestion($(this).data('info').user_id);
  }
})

function renderMap(nodelay) {
  nodelay = nodelay || false;
  if (nodelay) {
    var delay = 0
  } else {
    var delay = 500
  }
  $('.temp-map').html('');
  $.each(GAME.map, function(index, value){
    if (nodelay) {
      var $area =  $('<div> \
        capital:'+ value.capital+'<br>\
        id:'+ value.id+'<br>\
        lives:'+ value.lives+'<br>\
        user_id:'+ value.user_id+'<br>\
        zone:'+ value.zone+'\
      </div>').appendTo('.temp-map').css({
        "background-color":value.settings.color
      }).data('zone', value.zone).data('info', value);
      if (value.user_id>0) {
        $area.addClass('reserved');
        //$area.data('user', value.zone);
      }
    } else {
      setTimeout(function(){
        $('<div> \
          capital:'+ value.capital+'<br>\
          id:'+ value.id+'<br>\
          lives:'+ value.lives+'<br>\
          user_id:'+ value.user_id+'<br>\
          zone:'+ value.zone+'\
        </div>').appendTo('.temp-map').css({
          "background-color":value.settings.color
        }).data('zone', value.zone);
      }, delay*index);
    }
  });
}


renderNormalQuestion = function(enemy_id){
  GAME.users_question = [GAME.user.id, enemy_id]
  getNormalQuestion(function(){
    console.log(GAME.response);
    openFrame('question-2');
    showPoppups();
  })
}

var last_turn = 0;

whoTurn = function() {
  getGame(function(){
    if (GAME.stage == 1) {
      if (GAME.next_turn==GAME.user.id) {
        if (GAME.next_turn != last_turn) {
          last_turn = GAME.next_turn;
          alert('Ваш ход! Ваш цвет: '+GAME.user.color+'. Кол-во доступных ходов: '+ GAME.user.available_steps)
          hidePoppups();
        }
      } else {
        if (GAME.next_turn != last_turn) {
          last_turn = GAME.next_turn;
  
          var user_turn = getUserById(GAME.next_turn);
          if (user_turn) {
            alert('Ходит игрок:'+user_turn.color+'! Ваш цвет: '+GAME.user.color+'. Кол-во доступных ходов: '+ GAME.user.available_steps)
            hidePoppups();
          } else {
            renderMap(true);
            takingLand();
          }
        }
      }
      setTimeout(whoTurn, 1000);
    } else if (GAME.stage == 2) {
      //alert('Этап захвата')
      console.log('Этап захвата');
      //alert(GAME.response.settings.next_step);
      if (GAME.next_turn==GAME.user.id) {
        alert('Ваш ход. Этап захвата.');
        //renderNormalQuestion();
      } else {
        if (GAME.next_turn != last_turn) {
          last_turn = GAME.next_turn;
          //var user_turn = getUserById(GAME.next_turn);
        }
        getResultQuestion();
      }
    }
    renderMap(true);
  });
}

showQuestionResult = function(response){
  GAME.question.result = response.responseJSON.result;
  var _s = '';
  $.each(GAME.users, function(index, value){
    value.place = GAME.question.result[value.id]
    _s = _s+'Игрок: '+value.color+' - '+ value.place +' место. \n'
  });
  alert(_s);
  whoTurn();
}


function afterAnswer() {
  $('#question-1 form.a, #question-1 .numpad, #question-1 .right .timer').slideUp();
}

$(document).ready(function () {

  $('#question-1 form.a').submit(function(){
    GAME.question.answer = $(this).find('input').val();
    $(this).find('input').val('');
    GAME.question.time = 10 - $('#question-1 .right .timer').text();
    sendQuestionAnswer(function(){
      afterAnswer();
      getResultQuestion();
    });
    //GAME.sendQuestionAnswer();
    //GAME.getResultQuestion();
  });

})