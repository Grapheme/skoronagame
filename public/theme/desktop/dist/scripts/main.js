/*  Author: Grapheme Group
 *  http://grapheme.ru/
 */

var GAME = GAME || {};
GAME.game_id = 0;                                       // id игры
GAME.user = {};                                         // пользователь
GAME.enemies = [];                                         // враги
GAME.status = 0;                                        // статус игры
GAME.stage = 0;                                         // этап игры
GAME.response = {};                                     // ответ от сервера
GAME.map = {};                                          // карта
GAME.question = {};                                     // текущий вопрос
GAME.steps = 0;                                         // доступные шаги
GAME.user_step = 0;                                     // id пользователя который сейчас делает шаг
GAME.statuses = ['wait','start','ready','over'];        // возможные статусы игры
GAME.users = {};
GAME.question;


var getGame = function(callback){
    callback = callback || function(){}
    $.ajax({
        type: "POST",
        url: '/game/get-game',
        data: {game: GAME.game_id},
        dataType: 'json',
        success: function (response) {
            if (response.status) {
                parseGameData(response);
                callback();
                console.log(response);
                //GAME.response = response.responseJSON;
                //GAME.map = GAME.response.map;
                
            }
        },
        error: function (xhr, textStatus, errorThrown) {}
    });
};

getQuizQuestion = function(_users, callback){
    callback = callback || function(){}
    _users = _users || GAME.users
    $.ajax({
        type: "POST",
        url: '/game/question/get-quiz',
        data: {game: GAME.game_id, users: _users},
        dataType: 'json',
        success: function (response) {
            if (response.status) {
                //GAME.user_step = 0;
                parseGameData(response);
                //GAME.response = response.responseJSON;
                //AME.question.id = GAME.response.question.id;
                //GAME.question.text = GAME.response.question.text;
                //GAME.question.answers = [];
                //GAME.question.type = GAME.response.question.type;
                //GAME.parseQuestionResponse();
                //$("#js-server-response").html(JSON.stringify(GAME.response));
            }
            //$("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {}
    });
}

function getUserById(id) {
    var returnVal;
    $.each(GAME.users, function(index, value){
        if (value.id == id) {
            returnVal = value;
        }
    });
    return returnVal;
}

sendConquestEmptyTerritory = function(territory, callback){
    callback = callback || function(){}
    $.ajax({
        type: "POST",
        url: '/game/conquest/territory',
        data: {game: GAME.game_id, zone: territory},
        dataType: 'json',
        success: function (response) {
            if (response.status) {
                console.log(response);
                callback();
            }
        },
        error: function (xhr, textStatus, errorThrown) {}
    });
}


function parseGameData(response) {
    if (response.responseJSON.users) {
        GAME.users = response.responseJSON.users;
        $.each(response.responseJSON.users, function(index, value){
            if (value.id == response.responseJSON.current_user) {
                GAME.user = value;
            } else {
                var exist = false;
                $.each(GAME.enemies, function(i, v){
                    if (v.id == value.id) {
                        exist = true;
                        GAME.enemies[i] = value
                    }
                });
                if (exist == false) {
                    GAME.enemies.push(value) 
                }
            }
        });
    }
    
    if (response.responseJSON.question) {
        GAME.question = response.responseJSON.question;
    }
    
    GAME.game_id = response.responseJSON.game_id;
    GAME.stage = response.responseJSON.game_stage;
    GAME.status = response.responseJSON.game_status;
    GAME.map = response.responseJSON.map;
    if (response.responseJSON.settings) {
        GAME.next_turn = response.responseJSON.settings.next_step || 0;
    }
    GAME.response = response.responseJSON;
    
}


getResultQuestion = function(){
  $.ajax({
      type: "POST",
      url: '/game/question/get-result',
      data: {game: GAME.game_id, question: GAME.question.id, type: GAME.question.type},
      dataType: 'json',
      success: function (response) {
          if (response.status) {
            if (response.responseJSON.result == 'retry') {
              //console.log(response)
              setTimeout(getResultQuestion, 500)
            } else if (response.responseJSON.result == 'standoff') {
              alert('Ничья')
              //console.log(response)
            } else {
              showQuestionResult(response);
            }
          }
      },
      error: function (xhr, textStatus, errorThrown) {
      }
  });
}

sendQuestionAnswer = function(callback){
  callback = callback || function(){}
  $.ajax({
    type: "POST",
    url: '/game/question/send-answer',
    data: {game: GAME.game_id, question: GAME.question.id, answer: GAME.question.answer, time: GAME.question.time},
    dataType: 'json',
    success: function (response) {
      if (response.status) {
        console.log(response);
        callback();
      }
    },
    error: function (xhr, textStatus, errorThrown) {
    }
  });
}
/* jshint devel:true */
console.log('\'Allo \'Allo!');

var bg_width = $('#map').width();

function scale() {
  var doc_width = $(window).width();
  $('#map').transition({ scale: doc_width/bg_width });
}

$(window).resize(function (){
  scale();
});

$(window).load(function() {
  bg_width = $('#map').width();
  scale();
});

$('.areas .countur svg path').hover(function(){
  $(this).closest('.area').toggleClass('active');
});

var _history = [];
var _history = ['menu'];

function openFrame(href) {
  if (href=='close') {
    _history.pop();
  } else {
    _history.push(href);
  }
  if (href=="mathcmaking") {
    matchmaking();
  }
  var last_item = _history[_history.length-1]
  $('.popup-wrapper .popup-holder .popup').removeClass('active');
  $('.popup-wrapper .popup-holder .popup#'+last_item).addClass('active');
}

$('body').on('click', 'a',function(e){
  var href = $(this).attr('href').split('#');
  //console.log(href[1]);
  if (href[1]) {
    openFrame(href[1]);
    e.preventDefault();
  }
});

$('body').on('click', '.numpad a', function(e){
  var $input = $(this).closest('.numpad').prev('form').find('input');
  $input.focus();
  if ($(this).hasClass('clear')) {
    $input.val('');
  } else if ($(this).hasClass('del')) {
    $input.val($input.val().slice(0, -1));
  } else {
    $input.val($input.val()+$(this).text());
  }
  e.preventDefault();
});

$('body').on('click', '.popup.with-tabs .tabs-btn a', function(e){
  e.preventDefault();
  var _c = $('.popup.with-tabs .tabs-btn a').index($(this));
  if (!$(this).is('.active')) {
    $('.popup.with-tabs .tabs-btn a').removeClass('active');
    $(this).addClass('active');
    $('.popup.with-tabs .tabs .tab').removeClass('active');
    $('.popup.with-tabs .tabs .tab').eq(_c).addClass('active');
  }
});

$('#help .q').click(function(){
  $(this).toggleClass('active');
  $(this).next('.a').slideToggle();
});

if (_skoronagame_.open_frame) {
  openFrame(_skoronagame_.open_frame);
}

function hidePoppups() {
  $('.popup-wrapper').slideUp();
}

function showPoppups() {
  $('.popup-wrapper').slideDown();
}

$('form').submit(function(e){
  e.preventDefault();
  
  if ($(this).is('.noajax')) return false;
  
  var _href = $(this).attr('action');
  var _method = $(this).attr('method');
  var _popup = $(this).attr('data-result');
  $.ajax({
    type: _method,
    url: _href,
    data: $(this).serialize(),
    success: function (response) {
      console.log(response)
      if (response.status == true) {
      //open_popup = $(this).attr('data-result');
        if (response.redirect) {
          location.href=response.redirect
        }
        openFrame(_popup);
      } else if (response.status == false) {
        alert(response)
      }
    },
    error: function (xhr, textStatus, errorThrown) {
      console.log(xhr)
      console.log(textStatus)
    }
  });

});
var firstTime = new Date().getTime()/1000;
var search_timeout = 90;
var quiz_timer_default = 10;

function startQuizeTimer() {
  var timer = quiz_timer_default;
  $('#question-1 .right .timer').text(timer);
  setInterval(function(){
    $('#question-1 .right .timer').text(timer);
    timer--
  }, 1000)
}

function quizQuesionRender() {
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
        renderMap();
        hidePoppups();
        setTimeout(takingLand, 10000);
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
  if (GAME.user.id == GAME.next_turn && GAME.stage == 1) {
    sendConquestEmptyTerritory($(this).data('zone'),function(){
      getGame(function(){
        renderMap(true);
      });
    });
  }
})

function renderMap(nodelay) {
  nodelay = nodelay || false;
  if (nodelay) {
    var delay = 0
  } else {
    var delay = 500
  }
  $.each(GAME.map, function(index, value){
    setTimeout(function(){
      $('.temp-map').html('');
      
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
  });
}

var last_turn = 0;

whoTurn = function() {
  getGame(function(){
    if (GAME.next_turn==GAME.user.id) {
      if (GAME.next_turn != last_turn) {
        last_turn = GAME.next_turn;
        alert('Ваш ход! Ваш цвет: '+GAME.user.color+'. Кол-во доступных ходов: '+ GAME.user.available_steps)
        hidePoppups();
      }
      setTimeout(whoTurn, 1000);
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
      setTimeout(whoTurn, 1000);
    }
    renderMap(true);
  });
}

showQuestionResult = function(response){
  GAME.question.result = response.responseJSON.result;
  alert(response.responseText);
  whoTurn();
}


function afterAnswer() {
  $('#question-1 form.a, #question-1 .numpad, #question-1 .right .timer').slideUp();
}

$(document).ready(function () {

  $('#question-1 form.a').submit(function(){
    GAME.question.answer = $(this).find('input').val();
    $(this).find('input').val('');
    GAME.question.time = 10 - $('#question-1 .right .timer').text()
    sendQuestionAnswer(function(){
      afterAnswer();
      getResultQuestion();
    });
    //GAME.sendQuestionAnswer();
    //GAME.getResultQuestion();
  });

})