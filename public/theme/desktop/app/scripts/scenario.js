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
  getQuizQuestion(GAME.users, function(){
    //alert(GAME.question.text);
    $('#question-1 .left .timer').text('...').prev('.led').removeClass('red').addClass('black');
    $('#question-1 .left .answer').text('');
    $('#question-1 .right .answerlkhbdsfksdlhfg').slideUp();
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
    //getUsersResultQuestions();
  });
}

function matchmaking() {
  getGame(function(){
    if (GAME.status == 'wait') {
      renderPlayers();
      setTimeout(matchmaking, 1000)
    } else {
      createPlayers();
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

function createPlayers() {
  $.each(GAME.users, function(index, value){
    $('#question-1 .left .unit').eq(index).find('.name').text(value.name).addClass(value.color);
  })
}

function renderPlayers() {
  $.each(GAME.users, function(index, value){
    
    if (!GAME.resultQuestion || !GAME.resultQuestion[value.id]) {
      //$('#question-1 .left .unit').eq(index).find('.name').text(value.name).addClass(value.color);
    } else {
      /*
      var _answ = GAME.resultQuestion[value.id];
      var $unit = $('#question-1 .left .unit .name:contains('+value.name+')').closest('.unit');
      $unit.data('place', _answ.place);
      $unit.find('.timer').text(_answ.seconds);
      $unit.find('.timer').prev('.led').removeClass('black').addClass('red');
      $unit.find('.answer').text(_answ.answer);
      orderPlayers();*/
    }
    
    if (GAME.stage==1 && GAME.status =='ready') {
      $('#user-list').show();
    }
    var $user = $('#user-list .user.'+value.color).find('.name').text(value.name).find('.points').text(value.points);
    if (value.id == GAME.user.id) {
      $('#user-list .user.'+value.color).find('.name').text('Вы');
    }
    
    if (value.id == GAME.next_turn) {
      $('#user-list .user').removeClass('active');
      $('#user-list .user.'+value.color).addClass('active');
    }
    
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

$('body').on('click', '#map .area', function(event){
  if (GAME.user.id == GAME.next_turn && GAME.stage == 1 && !$(this).hasClass('reserved')) {
    sendConquestEmptyTerritory($(this).data('zone'),function(){
      getGame(function(){
        renderMap(true);
      });
    });
  } else if (GAME.user.id == GAME.next_turn && GAME.stage == 2 && $(this).data('info').user_id != GAME.user.id) {
    renderNormalQuestion(GAME.user.id, $(this).data('info').user_id);
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
  $('#map').addClass('user_'+GAME.user.color);
  $.each(GAME.map, function(index, value){
    if (nodelay) {
      var offset_index = parseInt(index)+1;
      var $area = $('#map #area-'+offset_index);
      $area.data('zone', value.zone).data('info', value);
      if (value.user_id>0) {
        $area.removeClass('empty');
        $area.addClass('reserved');
        $area.addClass(getUserById(value.user_id).color);
      } else {
        $area.removeClass('reserved');
        $area.addClass('empty');
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


renderNormalQuestion = function(conqu, enemy_id){
  conqu = conqu || GAME.user.id;
  GAME.users_question = {
    conqu: conqu,
    def: enemy_id
  }
  getNormalQuestion(function(){
    //console.log(GAME.response);
    $('#question-2 .q').html(GAME.question.text);
    $('#question-2 .a').html();
    $.each(GAME.question.answers, function(index, value){
      $('#question-2 .a').append('<a href="">'+value+'</a>');
    });
    //$('#question-2 .a')
    openFrame('question-2');
    showPoppups();
  })
}

var last_turn = 0;

whoTurn = function() {
  getGame(function(){
    //getUsersResultQuestions();
    if (GAME.stage == 1) {
      if (GAME.next_turn==GAME.user.id) {
        if (GAME.next_turn != last_turn) {
          last_turn = GAME.next_turn;
          //alert('Ваш ход! Ваш цвет: '+GAME.user.color+'. Кол-во доступных ходов: '+ GAME.user.available_steps)
          //hidePoppups();
        }
      } else {
        if (GAME.next_turn != last_turn) {
          last_turn = GAME.next_turn;
          var user_turn = getUserById(GAME.next_turn);
          if (user_turn) {
            //alert('Ходит игрок:'+user_turn.color+'! Ваш цвет: '+GAME.user.color+'. Кол-во доступных ходов: '+ GAME.user.available_steps)
            //hidePoppups();
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
        };
        
        if (GAME.duel) {
          GAME.duel.def == GAME.user.id;
          renderNormalQuestion(GAME.duel.conqu, GAME.duel.def);
        }
        /*$.each(GAME.duel, function(index, value){
          if (value == GAME.user.id) {
            renderNormalQuestion()
          }
        });*/
        
        //getResultQuestion();
        setTimeout(whoTurn, 1000);
      }
      
    }
    renderMap(true);
  });
}

function orderPlayers() {
  $('#question-1 .left').find('.unit').sort(function (a, b) {
    return $(a).data('place') - $(b).data('place');
  }).appendTo('#question-1 .left');
}

showQuestionResult = function(response){
  GAME.question.result = response.responseJSON.result;
  var _s = '';
  $.each(GAME.users, function(index, value){
    value.place = GAME.question.result[value.id]
    _s = _s+'Игрок: '+value.color+' - '+ value.place +' место. \n'
  });
  getUsersResultQuestions(function(){
    $.each(GAME.users, function(index, value){
      var _answ = GAME.resultQuestion.results[value.id];
      var $unit = $('#question-1 .left .unit .name:contains('+value.name+')').closest('.unit');
      $unit.data('place', _answ.place);
      $unit.find('.timer').text(_answ.seconds+' сек.');
      //$unit.find('.timer').text('10 сек.');
      $unit.find('.timer').prev('.led').removeClass('black').addClass('red');
      $unit.find('.answer').text('Ответ: '+_answ.answer);
    })
    orderPlayers();
    setTimeout(hidePoppups, 4000);
  })
  //alert(_s);
  whoTurn();
}


function afterAnswer() {
  $('#question-1 form.a, #question-1 .numpad, #question-1 .right .timer').slideUp();
  $('#question-1 .right .answerlkhbdsfksdlhfg').slideDown();
}

$(document).ready(function () {

  $('#question-1 form.a').submit(function(){
    GAME.question.answer = $(this).find('input').val();
    $('#question-1 .right .answerlkhbdsfksdlhfg .qa').text(GAME.question.answer);
    GAME.question.answer
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