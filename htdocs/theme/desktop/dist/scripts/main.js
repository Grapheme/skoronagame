/*  Author: Grapheme Group
 *  http://grapheme.ru/
 */

var GAME = GAME || {};
GAME.game_id = 0;//10                                       // id игры
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
//GAME.question;
GAME.mustConquer = 0;

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
                console.log(GAME.status);
                callback();
                //console.log(response);
                //console.log(GAME.status);
                //GAME.response = response.responseJSON;
                //GAME.map = GAME.response.map;
                renderPlayers();
                if (GAME.next_turn == 0 && GAME.status == "over") {
                    /*var _status = true;
                    $.each(GAME.users, function(index, value){
                        if (value.status != 2) {
                            _status = false;
                        }
                    })
                    if (_status == true) {*/
                    
                        overGame();
                    //}
                }
                
            }
        },
        error: function (xhr, textStatus, errorThrown) {
            getGame(callback);
        }
    });
};

getBots = function (){
    $.ajax({
        type: "POST",
        url: '/game/add-bots',
        data: {game: GAME.game_id},
        dataType: 'json',
        beforeSend: function () {},
        success: function (response) {
            if (response.status) {
                GAME.response = response.responseJSON;
                $("#js-server-response").html(JSON.stringify(GAME.response));
            }
            $("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {
            clearInterval(GAME.game_timer);
        }
    });
}

playerDisconect = function() {
    $.ajax({
        type: "POST",
        url: idleUrl,
        data: {game: GAME.game_id, user: GAME.user.id},
        dataType: 'json',
        success: function (response) {
            if (response.status) {
                
            }
        },
        error: function (xhr, textStatus, errorThrown) {
            playerDisconect();
        }
    });
}

overGame = function(){

    $.ajax({
        type: "POST",
        url: '/game/over-game',
        data: {game: GAME.game_id},
        dataType: 'json',
        success: function (response) {
            if (response.status) {
                GAME.response = response.responseJSON;
                renderGameOver();
                //alert('КОнец')
            }
        },
        error: function (xhr, textStatus, errorThrown) {
            overGame();
        }
    });
}


getNormalQuestion = function(callback){
    callback = callback || function(){};
    console.log('Получить нормальный вопрос! Данные отправлены:', {game: GAME.game_id, users: GAME.users_question});
    $.ajax({
        type: "POST",
        url: '/game/question/get-normal',
        data: {game: GAME.game_id, users: GAME.users_question},
        dataType: 'json',
        success: function (response) {
            if (response.status) {
                //GAME.user_step = 0;
                //console.log(response, 'ВНИМАНИЕ НОРМАЛЬНЫЙ ВОПРОС!!')
                parseGameData(response);
                /*GAME.response = response.responseJSON;
                GAME.question.id = GAME.response.question.id;
                GAME.question.text = GAME.response.question.text;
                GAME.question.answers = GAME.response.question.answers;
                GAME.question.type = GAME.response.question.type;*/
                callback();
                //GAME.parseQuestionResponse();
                //$("#js-server-response").html(JSON.stringify(GAME.response));
            } else {
                getNormalQuestion(callback);
            }
        },
        error: function (xhr, textStatus, errorThrown) {
            getNormalQuestion(callback);
        }
    });
}

getQuizQuestion = function(_users, callback){
    callback = callback || function(){}
    _users = _users || GAME.users
    //console.log(arguments)
    if (GAME.stage==2) {
        //alert('тревога. Лишний запрос!')
    }
    $.ajax({
        type: "POST",
        url: '/game/question/get-quiz',
        data: {game: GAME.game_id, users: _users},
        dataType: 'json',
        success: function (response) {
            if (response.status) {
                //GAME.user_step = 0;
                parseGameData(response);
                callback();
                //GAME.response = response.responseJSON;
                //AME.question.id = GAME.response.question.id;
                //GAME.question.text = GAME.response.question.text;
                //GAME.question.answers = [];
                //GAME.question.type = GAME.response.question.type;
                //GAME.parseQuestionResponse();
                //$("#js-server-response").html(JSON.stringify(GAME.response));
            } else {
                getQuizQuestion(_users, callback);
            }
            //$("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {
            getQuizQuestion(_users, callback);
        }
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

function getAreaById(id) {
    var returnVal;
    $.each(GAME.map, function(index, value){
        if (value.zone == id) {
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
                console.log(response, 'ОТВЕТ НА ЗАХВАТ');
                callback();
            }
        },
        error: function (xhr, textStatus, errorThrown) {
            sendConquestEmptyTerritory(territory, callback);
        }
    });
}

sendConquestCapital = function(territory, callback){
    callback = callback || function(){}

    $.ajax({
        type: "POST",
        url: '/game/conquest/capital',
        data: {game: GAME.game_id, zone: territory},
        dataType: 'json',
        success: function (response) {
            if (response.status) {
                console.log(response, 'ОТВЕТ НА ЗАХВАТ СТОЛИЦЫ');
                callback(response);
            }
        },
        error: function (xhr, textStatus, errorThrown) {
            sendConquestCapital(territory, callback)
        }
    });
}


var last_stage;
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
    if (last_stage == 1 && GAME.stage==2) {
        sexyAlert('Начался 2 этап!')
    }
    last_stage = GAME.stage;
    GAME.status = response.responseJSON.game_status;
    GAME.map = response.responseJSON.map || GAME.map;
    /*if (response.responseJSON.disconnect_user_timeout) {
        idleWait = (response.responseJSON.disconnect_user_timeout||30)*1000;
        idleUrl = response.responseJSON.disconnect_user_url;
    }*/
    if (response.responseJSON.settings) {
        GAME.next_turn = response.responseJSON.settings.next_step || 0;
        if (response.responseJSON.settings.duel) {
            GAME.duel = response.responseJSON.settings.duel;
        } else {
            GAME.duel = {};
        }
        if (response.responseJSON.settings.stage2_tours_json) {
            GAME.stage2_tours_json = $.parseJSON(response.responseJSON.settings.stage2_tours_json);
            renderSteps();
        }
    }
    GAME.response = response.responseJSON;
}


/*
 Метод запрашивает состояние ответов на вопрос
 Отправляет:
 game  - (int)ID игры.
 question  - (int)ID вопроса.
 type  - (string) тип вопроса.
 Результат:
 ...
 current_answer - (string) Приавильный ответ
 results - (JSON) ответы пользователей
 results = {"15":{"answer":"9","seconds" :2,"place":1,"status":2,"correctly":1}
    15 - (int)ID пользователя
    answer - (string) ответ пользователя
    seconds - (int) время потраченное на ответ пользователем
    place - (int) занятое место по результам всех ответов
    status - (int) статус ответа: 0 - нет ответа, 1 - ответил, 2 - результаты отпределены, вопрос закрыт, 99 - ничья
    correctly - (int) 0 - если пользователь ответил не точно, 1 - если ответил точно
 */

getUsersResultQuestions = function (callback) {
    callback = callback || function(){}
    $.ajax({
        type: "POST",
        url: '/game/question/get-users-results',
        data: {game: GAME.game_id, question: GAME.question.id, type: GAME.question.type},
        dataType: 'json',
        success: function (response) {
            if (response.status) {
                console.log(response, 'ВНИМАНИЕ!!');
                GAME.resultQuestion = response.responseJSON;
                callback();
            }
        },
        error: function (xhr, textStatus, errorThrown) {
            getUsersResultQuestions(callback);
        }
    });
}

getResultQuestion = function(){
    //console.log('РЕЗУЛЬТАТ ВОПРОСААААА', {game: GAME.game_id, question: GAME.question.id, type: GAME.question.type})
  $.ajax({
      type: "POST",
      url: '/game/question/get-result',
      data: {game: GAME.game_id, question: GAME.question.id, type: GAME.question.type, zone: GAME.mustConquer},
      dataType: 'json',
      success: function (response) {
          if (response.status) {
            console.log(response.responseJSON.result);
            //if(GAME.stage == 1 || GAME.question.type=='quiz'){
            if(GAME.question.type=='quiz'){
                if (response.responseJSON.result == 'retry') {
                  //console.log(response)
                  //if (GAME.stage == 1) {
                    setTimeout(getResultQuestion, 1000)
                  //}
                } else if (response.responseJSON.result == 'standoff') {
                  //alert('Ничья');
                    //hidePoppups(function(){
                    //   sexyAlert('Ничья! Будет задан другой вопрос.', callback = function(){
                        
                    if (GAME.stage == 2) {
                        quizQuesionRender([GAME.duel.conqu, GAME.duel.def]);
                    } else {
                        quizQuesionRender();
                    }
                    //});
                //});
                    
                  //console.log(response)
                } else {
                  showQuestionResult(response);
                  if (GAME.stage == 2) {
                    showQuestionResult(response);
                    tryToConquer();
                    //GAME.question = {};
                  }
                }
            //} else if (GAME.stage == 2 || GAME.question.type=='normal') {
            } else if (GAME.question.type=='normal') {
                if(response.responseJSON.result === 'standoff'){
                    //hidePoppups(fun);
                    //sexyAlert('Ничья! Будет задан квиз-вопрос.', function(){
                       //setTimeout(function(){
                        quizQuesionRender([GAME.duel.conqu, GAME.duel.def]);    
                        //}, 0) 
                    //});
                    //GAME.getQuizQuestion();
                }else if(response.responseJSON.result === 'retry'){
                    setTimeout(getResultQuestion, 1000)
                }else if(typeof response.responseJSON.result == "object"){
                    console.log('РЕЗУЛЬТАТ!!', response.responseJSON.result)
                    showQuestionResult(response);
                    tryToConquer();
                    //GAME.question = {};
                    /*GAME.question = {};
                    GAME.users_question = [];
                    GAME.steps = GAME.response.result[GAME.user.id];
                    GAME.sendConquestTerritory();*/
                }
            }
          }
      },
      error: function (xhr, textStatus, errorThrown) {
        getResultQuestion();
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
      if (response.status && response.status == true) {
        //console.log(response);
        callback();
      } else {
        sendQuestionAnswer(callback);
      }
    },
    error: function (xhr, textStatus, errorThrown) {
        sendQuestionAnswer(callback);
    }
  });
}
/* jshint devel:true */
console.log('\'Allo \'Allo!');

var hasOwnProperty = Object.prototype.hasOwnProperty;

function isEmpty(obj) {

    // null and undefined are "empty"
    if (obj == null) return true;

    // Assume if it has a length property with a non-zero value
    // that that property is correct.
    if (obj.length > 0)    return false;
    if (obj.length === 0)  return true;

    // Otherwise, does it have any properties of its own?
    // Note that this doesn't handle
    // toString and valueOf enumeration bugs in IE < 9
    for (var key in obj) {
        if (hasOwnProperty.call(obj, key)) return false;
    }

    return true;
}

idleTimer = null;
idleState = false; // состояние отсутствия
idleWait = 900*1000; // время ожидания в мс. (1/1000 секунды)
idleUrl = "/game/disconnect_user";

$(document).bind('mousemove keydown scroll', function(){
  clearTimeout(idleTimer); // отменяем прежний временной отрезок
  if(idleState == true){ 
    // Действия на возвращение пользователя
     //$("body").append("<p>С возвращением!</p>");
  }

  idleState = false;
  idleTimer = setTimeout(function(){ 
    // Действия на отсутствие пользователя
    var text = "Вы отсутствовали более " + idleWait/1000 + " секунд и были отключены от сервера. <a href='' style='font-size:18px'>Обновите страницу</a> чтобы начать новую игру."
    hidePoppups();
    setInterval(function(){
        sexyAlert(text, 99999999999, function(){
            sexyAlert(text, 99999999999, function(){
                sexyAlert(text, 99999999999, function(){
                    sexyAlert(text, 99999999999, function(){
                        sexyAlert(text, 99999999999, function(){
                            sexyAlert(text, 99999999999, function(){
                                sexyAlert(text, 99999999999, function(){
                                
                                });
                            });
                        });
                    });
                });
            });
        });
    }, 500);
    sexyAlert(text, 99999999999);
    playerDisconect();
    idleState = true; 
  }, idleWait);
});
 
//$("body").trigger("mousemove"); // сгенерируем ложное событие, для запуска скрипта

var bg_width = $('#map').width();

function scale() {
  var doc_width = $(window).width();
  $('#map').transition({ scale: doc_width/bg_width });
  $('#user-list').transition({ scale: doc_width/bg_width });
  $('.infowindow.tour1, .infowindow.tour2').transition({ scale: doc_width/bg_width });
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
  if ($(this).closest('.area').data('info')) {
    var _points = $(this).closest('.area').data('info').points || 0;
    //var _sgt =
    if ($(this).closest('.area').data('info').capital == 1) {
        var _usr = getUserById($(this).closest('.area').data('info').user_id)
        $('.infowindow-holder .infowindow-small').text('Столица игрока '+_usr.name+', '+_points);
    } else {
        $('.infowindow-holder .infowindow-small').text(_points);        
    }
    $('.infowindow-holder').toggle();
  }
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
  var last_item = _history[_history.length-1];
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
    if ($input.val().length<4) {
      $input.val($input.val()+$(this).text());
    }
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

$('#infowindow-question').click(function(e){
    if (!$('.popup-wrapper').is(':visible')) {
        showPoppups();
        openFrame('help-stage-'+GAME.stage)
    }
    e.preventDefault();
});

$('#help-stage-2 .close, #help-stage-1 .close').click(function(){
    hidePoppups();
});

$('#help .q').click(function(){
  $(this).toggleClass('active');
  $(this).next('.a').slideToggle();
});

if (_skoronagame_.open_frame) {
  openFrame(_skoronagame_.open_frame);
}

function sexyAlert(text, timeOut, callback) {
  timeOut = timeOut || 3;
  callback = callback || function(){};
  
  if ($('#sexy-alert .note').html()!=text && !$('.popup-wrapper').is(':visible')) {
    showPoppups();
    $('#sexy-alert .note').html(text);
    openFrame('sexy-alert');
    
    setTimeout(function(){
      hidePoppups();
      callback();
    }, timeOut*1000);
  }
  
}

function hidePoppups(callback) {
    callback = callback || function(){};
    $('.popup-wrapper').fadeOut(100, function(){
        callback();
    });
    clearInterval(quiz_interval);
    clearInterval(normal_interval);
}

function showPoppups() {
  $('.popup-wrapper').fadeIn(100);
}

sendForm = function(form) {
  //alert('!!!')
  //$(form).submit();
  if ($(form).is('.noajax')) {
    alert('!!!')
    return false;
  }
  
  console.log(form)
  console.log($(form))
  
  var _href = $(form).attr('action');
  var _method = $(form).attr('method');
  var _popup = $(form).attr('data-result');
  $.ajax({
    type: _method,
    url: _href,
    data: $(form).serialize(),
    success: function (response) {
      console.log(response);
      if (response.status == true) {
      //open_popup = $(this).attr('data-result');
        if (response.redirect) {
          location.href=response.redirect
        }
        openFrame(_popup);
      } else if (response.status == false) {
        $(form).prepend('<label class="error">'+response.responseText+'</label>');
      }
    },
    error: function (xhr, textStatus, errorThrown) {
      console.log(xhr)
      console.log(textStatus)
    }
  });
}


$('#login form').validate({
  rules: {
    email: {
      required: true,
      email: true
    },
    password: 'required',
  },
  messages: {
    email: {
      required: 'Обязательное поле',
      email: 'Неверный формат. Попробуйте еще'
    },
    password: 'Обязательное поле'
  },
  submitHandler: function(form) {
    sendForm(form);
  }
});

$('#register form').validate({
  rules: {
    email: {
      required: true,
      email: true
    },
    name: 'required',
  },
  messages: {
    email: {
      required: 'Обязательное поле',
      email: 'Неверный формат. Попробуйте еще'
    },
    name: 'Обязательное поле'
  },
  submitHandler: function(form) {
    sendForm(form);
  }
});

$('#new-password form').validate({
  rules: {
  },
  messages: {
  },
  submitHandler: function(form) {
    sendForm(form);
  }
});

$('form').submit(function(e){
  if ($(this).is('.noajax')) {
    return false
    //e.preventDefault();
  }
});

var firstTime = new Date().getTime()/1000;
var search_timeout = 90;
var quiz_timer_default = 10;

var quiz_interval;
var normal_interval;


function startQuizeTimer() {
  clearInterval(quiz_interval);
  var timer = quiz_timer_default;
  $('#question-1 .right .timer').text(timer);
  quiz_interval = setInterval(function(){
    $('#question-1 .right .timer').text(timer);
    timer--
    if (timer < 0) {
      quizeExpire();
    }
  }, 1000)
}

function quizeExpire() {
  clearInterval(quiz_interval)
  if ($('.numpad').is(':visible')) {
    $('#question-1 form.a').find('input').val(99999);
    $('#question-1 form.a').submit();
  }
}

function startNormalTimer() {
  clearInterval(normal_interval);
  var timer = quiz_timer_default;
  $('#question-2 .timer').text(timer);
  normal_interval = setInterval(function(){
    $('#question-2 .timer').text(timer);
    timer--
    if (timer < 0) {
      normalExpire();
    }
  }, 1000)
}

function normalExpire() {
  clearInterval(normal_interval);
  if ($('#question-2 .a a.active').size()==0) {
    GAME.question.answer = 99999;
    GAME.question.time = quiz_timer_default - $('#question-2 .timer').text();
    sendQuestionAnswer(function(){
      getResultQuestion();
      //normalQuestionIsrender=false;
    });
  }
}

function compareUsers(userA, userB) {
  return userB.points - userA.points;
}

function renderGameOver() {
  
  GAME.users.sort(compareUsers);
  
  $('#winer .places .first .name').text(GAME.users[0].name)
  
  if (GAME.users[0].photo != '') {
    $('#winer .places .first .ava .img').css({
      'background-image': 'url('+GAME.users[0].photo+')'
    });
  }
  
  $('#winer .places .second .name').text(GAME.users[1].name)
  if (GAME.users[1].photo != '') {
    $('#winer .places .second .ava .img').css({
      'background-image': 'url('+GAME.users[1].photo+')'
    });
  }
  
  $('#winer .places .third .name').text(GAME.users[2].name)
  if (GAME.users[2].photo != '') {
    $('#winer .places .third .ava .img').css({
      'background-image': 'url('+GAME.users[2].photo+')'
    });
  }
  
  openFrame('winer');
  showPoppups();
}

function quizQuesionRender(players) {
  players = players || [];
  clearInterval(quiz_interval);
  clearInterval(normal_interval);
  renderPlayers();
  
  if (players.length>0) {
    $('#question-1 .left .unit').hide();
    $.each(players, function(index, value){
      var _user = getUserById(value);
      $('#question-1 .left .unit.'+_user.color).show();
    });
  } else if (players.length==0) {
    $('#question-1 .left .unit').show();
  }
  
  getQuizQuestion(players, function(){
    //alert(GAME.question.text);
    $('#question-1 .left .timer').text('...').prev('.led').removeClass('red').addClass('black');
    $('#question-1 .left .answer').text('');
    $('#question-1 .right .answerlkhbdsfksdlhfg').slideUp();
    $('#question-1 form.a, #question-1 .numpad, #question-1 .right .timer').slideDown();
    $('#question-1 .right .q').html(GAME.question.text);
    $('#question-1 .right .answer-true .qa').text('');
    $('#question-1 .right .answer-true').slideUp(100);
    openFrame('question-1');
    showPoppups();
    startQuizeTimer();
  });
}

function takingLand() {
  getGame(function(){
    //console.log(GAME.status);
    quizQuesionRender();
    //getUsersResultQuestions();
  });
}

function matchmaking() {
  getGame(function(){
    if (GAME.status == 'wait') {
      //createPlayers();
      renderPlayers();
      setTimeout(matchmaking, 1000)
    } else {
      renderPlayers();
      createPlayers();
      if (GAME.status == "start" || GAME.status == "ready") {
        renderMap(true);
        hidePoppups();
        console.log('ВНИМАНИЕ!', GAME.status, GAME.stage);
        if (GAME.stage==0) {
          takingLand();
        }
        if (GAME.stage==1) {
          setTimeout(function(){
            takingLand();            
          }, 3000)
        }
        if (GAME.stage==2) {
          whoTurn();
        }
        //setTimeout(takingLand, 10000);
      }
    }
  });
}

function renderSteps(){
  $('.infowindow.tour1').hide();
  $('.infowindow.tour2').show();
  $.each(GAME.stage2_tours_json, function(index, value){
    var $tour = $('.infowindow.tour2 .tour.n'+(index+1));
    if (GAME.response && GAME.response.settings && GAME.response.settings.current_tour == index) {
      $tour.addClass('active');
    } else {
      $tour.removeClass('active');
    }
    
    $.each(value, function(index2, value2){
      var user_id = Object.keys(value2)[0];
      var user_statuc = value2[user_id];
      var user = getUserById(user_id);
      var $flag = $tour.find('.flag').eq(index2);
      
      $flag.removeClass('red green blue').addClass(user.color);
      if (GAME.next_turn == user_id) {
        $flag.addClass('active');
      } else {
        $flag.removeClass('active');
      }
    });
  })
}

function tryToConquer() {
  //if (GAME.stage == 2 && GAME.mustConquer && GAME.question.result[GAME.user.id] == 1) {
  if (GAME.stage == 2 && GAME.question.type == 'normal') {
    //setTimeout(hidePoppups, 4000);
  } else {
    //setTimeout(hidePoppups, 7000);
  }
  
  //normalQuestionIsrender = false;
  getGame(function(){
    //console.log(GAME.stage, GAME.mustConquer, GAME.user.available_steps)
    console.log('ВНИМАНИЕ', GAME.stage, GAME.mustConquer, GAME.user.available_steps, GAME.question.result, GAME.question.result[GAME.user.id])
    if (GAME.stage == 2 && GAME.mustConquer && (GAME.user.available_steps > 0 || (GAME.question.result && GAME.question.result[GAME.user.id]==1) )) {
      var _area = getAreaById(GAME.mustConquer);
      if (_area.capital == 1) {
        sendConquestCapital(GAME.mustConquer, function(response){
          //console.log(arguments);
          //console.log(response);
          console.log(response.conquest_result, 'Ответ на захват')
          if (response.conquest_result == 'retry') {
            console.log('Ходит игрок', GAME.next_turn, getUserById(GAME.next_turn).name, getUserById(GAME.next_turn).color);
            console.log('повтор!', $('#area-'+GAME.mustConquer), $('#area-'+GAME.mustConquer).size());
            setTimeout(function(){
              hidePoppups(function(){
                $('#area-'+GAME.mustConquer).click();
              }) 
            }, 8000);
          }
        })
      } else {
        sendConquestEmptyTerritory(GAME.mustConquer, function(){
          //normalQuestionIsrender=false;
          //GAalert('ЗАХВАт')
          //GAME.mustConquer = null
        })
      }
    }
  })
}

function createPlayers() {
  $.each(GAME.users, function(index, value){
    if (GAME.user.id != value.id) {
      $('#question-1 .left .unit').eq(index).find('.name').text(value.name).addClass(value.color);
    } else {
      $('#question-1 .left .unit').eq(index).find('.name').text('Вы').addClass(value.color);      
    }
    //$('#question-1 .left .unit').eq(index).find('.name').text(value.name).addClass(value.color);
    $('#question-1 .left .unit').eq(index).addClass(value.color);
    if (value.photo != '') {
      $('#question-1 .left .unit').eq(index).find('.ava .img').css({
        'background-image': 'url('+value.photo+')'
      });
    }
    $('#question-1 .left .unit').eq(index).addClass(value.color);
  })
}

function renderPlayers() {
  $('#user-list .user').removeClass('active');
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
    
    //if ((GAME.stage==1|| GAME.stage==2)&& GAME.status =='ready') {
    $('#user-list').show();
    //}
    var $user = $('#user-list .user.'+value.color);
    $user.find('.name').text(value.name);
    if (value.photo != '') {
      $user.find('.ava .img').css({
        'background-image': 'url('+value.photo+')'
      });
    }
    
    $user.find('.points').text(value.points);
    if (value.id == GAME.user.id) {
      $('#user-list .user.'+value.color).find('.name').text('Вы');
    }
    
    if (value.id == GAME.next_turn) {
      $('#user-list .user.'+value.color).addClass('active');
    }
    
  });
  $.each(GAME.enemies, function(index, value){
    if (index==0) {
      $('#mathcmaking .ava').first().find('.name').text(value.name);
      if (value.photo != '') {
        $('#mathcmaking .ava').first().find('.img').css({
          'background-image': 'url('+value.photo+')'
        });
      }
    }
    if (index==1) {
      $('#mathcmaking .ava').last().find('.name').text(value.name);
      if (value.photo != '') {
        $('#mathcmaking .ava').last().find('.img').css({
          'background-image': 'url('+value.photo+')'
        });
      }
    }
  });
  
  if (GAME.user.photo != '') {
    $('#mathcmaking .ava').eq(1).find('.img').css({
      'background-image': 'url('+GAME.user.photo+')'
    });
  }
}


$('body').on('click', '#question-2 .a a', function(e){
  e.preventDefault();
  if ($('#question-2 .a a.active').size()==0) {
    clearInterval(normal_interval);
    GAME.question.answer = $(this).data('id');
    GAME.question.time = quiz_timer_default - $('#question-2 .timer').text();
    $(this).addClass(GAME.user.color);
    sendQuestionAnswer(function(){
      getResultQuestion();
      //normalQuestionIsrender=false;
    });
  }
});

$('body').on('click', '#map .area', function(event){
  if (GAME.user.id == GAME.next_turn && GAME.stage == 1 && !$(this).hasClass('reserved')) {
    sendConquestEmptyTerritory($(this).data('zone'),function(){
      getGame(function(){
        renderMap(true);
      });
    });
  } else if (GAME.user.id == GAME.next_turn && GAME.stage == 2 && $(this).data('info').user_id != GAME.user.id) {
    GAME.mustConquer = $(this).data('info').zone;
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
  $('#map').addClass('user_'+GAME.user.color);
  $.each(GAME.map, function(index, value){
    if (nodelay) {
      var offset_index = parseInt(index)+1;
      var $area = $('#map #area-'+offset_index);
      $area.data('zone', value.zone).data('info', value);
      $area.attr('data-zone', value.zone);
      if (value.user_id>0) {
        $area.removeClass('empty');
        $area.addClass('reserved');
        $area.removeClass('red green blue');
        $area.addClass(getUserById(value.user_id).color);
        if (value.user_id == GAME.user.id) {
          $area.addClass('my');
        } else {
          $area.removeClass('my');          
        }
        if (value.capital == 1 && value.lives == 2) {
          $area.addClass('lives-2');
        } else {
          $area.removeClass('lives-2');
        }
        if (value.capital == 1 && value.lives == 1) {
          $area.addClass('lives-1');
        } else {
          $area.removeClass('lives-1');
        }
      } else {
        $area.removeClass('reserved');
        $area.removeClass('my');
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

normalQuestionIsrender = false;
renderNormalQuestion = function(conqu, enemy_id){
  if (!$('.popup-wrapper').is(":visible")) {
    //showPoppups();
    normalQuestionIsrender = true;
    conqu = conqu || GAME.user.id;
    
    GAME.users_question = {
      conqu: conqu,
      def: enemy_id
    }
    
    clearInterval(quiz_interval);
    clearInterval(normal_interval);
    getNormalQuestion(function(){
      clearInterval(quiz_interval);
      clearInterval(normal_interval);
      startNormalTimer();
      
      GAME.duel.conqu, GAME.duel.def
      if (GAME.mustConquer) {
        console.log(GAME.mustConquer);
      }
      var _note = '';
      if (GAME.duel || GAME.duel.length==0) {
        var _user = getUserById(enemy_id);
        if (GAME.mustConquer != 0) {
          var _zone = getAreaById(GAME.mustConquer);
          if (_zone.capital == 1) {
            _note = 'Вы напали на столицу игрока: <span class="'+_user.color+'">'+_user.name+'</span><br>Осталось жизней столицы: '+_zone.lives;
          }else {
            _note = 'Вы напали на игрока: <span class="'+_user.color+'">'+_user.name+'</span>';
          }
        } else {
          _note = 'Вы напали на игрока: <span class="'+_user.color+'">'+_user.name+'</span>';       
        }
      }
      if (GAME.duel.conqu == GAME.user.id) {
        var _user = getUserById(GAME.duel.def);
        console.log(GAME.mustConquer);
        if (GAME.mustConquer != 0) {
          console.log(GAME.mustConquer);
          var _zone = getAreaById(GAME.mustConquer);
          console.log('zone!', _zone)
          if (_zone.capital == 1) {
            _note = 'Вы напали на столицу игрока: <span class="'+_user.color+'">'+_user.name+'</span><br>Осталось жизней столицы: '+_zone.lives;
          } else {
            _note = 'Вы напали на игрока: <span class="'+_user.color+'">'+_user.name+'</span>';
          }
        } else {
          _note = 'Вы напали на игрока: <span class="'+_user.color+'">'+_user.name+'</span>';       
        }
      }
      
      if (GAME.duel.def == GAME.user.id) {
        var _user = getUserById(GAME.duel.conqu);
        _note = 'На вас напал игрок: <span class="'+_user.color+'">'+_user.name+'</span>'        
      }
      
      $('#question-2 .small-title').html(_note);
      $('#question-2 .left').removeClass('red green blue');
      $('#question-2 .right').removeClass('red green blue');
      $('#question-2 .left').addClass(getUserById(GAME.users_question.conqu).color)
      $('#question-2 .left .score').text(getUserById(GAME.users_question.conqu).points)
      $('#question-2 .right').addClass(getUserById(GAME.users_question.def).color)
      $('#question-2 .right .score').text(getUserById(GAME.users_question.def).points)
      $('#question-2 .q').html(GAME.question.text);
      $('#question-2 .a').html('');
      $.each(GAME.question.answers, function(index, value){
        //$('#question-2 .a').append
        $('<a href="">'+value+'</a>').appendTo($('#question-2 .a')).data('id', index);
      });
      //$('#question-2 .a')
      openFrame('question-2');
      showPoppups();
    })
  
  }
}

var last_turn = 0;
var whoTurn_is_run = false;
whoTurn = function() {
  whoTurn_is_run = true;
  getGame(function(){
    if (GAME.next_turn!=GAME.user.id) {
      setTimeout(function(){
        $("body").trigger("mousemove");
      }, 1000)
    }
    if (GAME.stage == 1) {
      $('#map .areas').addClass('stage-1');
      if (GAME.next_turn==GAME.user.id) {
        if (GAME.user.available_steps == 2) {
          sexyAlert('Ваш ход. <br>Выберите 2 территории.');
        }
        if (GAME.user.available_steps == 1) {
          sexyAlert('Ваш ход. <br>Выберите 1 территорию.');
        }
        $('#map .areas').addClass('active');
        if (GAME.next_turn != last_turn) {
          last_turn = GAME.next_turn;
          //alert('Ваш ход! Ваш цвет: '+GAME.user.color+'. Кол-во доступных ходов: '+ GAME.user.available_steps)
          //hidePoppups();
        }
      } else {
        $('#map .areas').removeClass('active');
        var user_turn = getUserById(GAME.next_turn);
        if (user_turn) {
          sexyAlert('<span class="'+user_turn.color+'">'+ user_turn.name + '</span> выбирает территорию');
        }
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
      $('#map .areas').removeClass('stage-1');
      $('#map .areas').addClass('stage-2');
      //alert('Этап захвата')
//      console.log('Этап захвата');
      //alert(GAME.response.settings.next_step);
      if (GAME.next_turn==GAME.user.id) {
        sexyAlert('Выберите территорию для нападения.');
        //alert('Ваш ход. Этап захвата.');
        //renderNormalQuestion();
      } else {
        if (GAME.next_turn!=0) {
          GAME.mustConquer = 0;
        }
        var user_turn = getUserById(GAME.next_turn);
        if (user_turn) {
          sexyAlert('<span class="'+user_turn.color+'">'+ user_turn.name + '</span> выбирает территорию');
        }
        if (GAME.next_turn != last_turn) {
          last_turn = GAME.next_turn;
          //var user_turn = getUserById(GAME.next_turn);
        };
        
        if (!isEmpty(GAME.duel)) {
          //if (GAME.duel.def == GAME.user.id || GAME.duel.conqu == GAME.user.id) {
          if (GAME.duel.def == GAME.user.id || GAME.duel.conqu == GAME.user.id) {
            console.log('!!!!');
            if (!$('.popup-wrapper').is(":visible")) {
            //if (normalQuestionIsrender == false) {
              renderNormalQuestion(GAME.duel.conqu, GAME.duel.def);
            }
          } else {
            var _conq = getUserById(GAME.duel.conqu);
            var _def = getUserById(GAME.duel.def);
            sexyAlert('Подождите, пока закончится противостояние игроков <span class="'+_conq.color+'">'+_conq.name+'</span> и <span class="'+_def.color+'">'+_def.name+'</span>', 10)
          }
        }
        //getResultQuestion();
        //setTimeout(whoTurn, 1000);
      }
      if (GAME.question.type == 'quize' || GAME.question.type == 'normal') {
        //getResultQuestion();
      }
      setTimeout(whoTurn, 1000);      
    }
    renderMap(true);
  });
}

function orderPlayers() {
  $('#question-1 .left').find('.unit').sort(function (a, b) {
    return $(a).data('place') - $(b).data('place');
  }).appendTo('#question-1 .left');
}

var showQuestionResult_timeout;
showQuestionResult = function(response){
  GAME.question.result = response.responseJSON.result;
  //var _s = '';
  clearTimeout(showQuestionResult_timeout);
  /*$.each(GAME.users, function(index, value){
    value.place = GAME.question.result[value.id]
    _s = _s+'Игрок: '+value.color+' - '+ value.place +' место. \n'
  });*/
  
  getUsersResultQuestions(function(){
    //console.log('РУЗЕЛЬТАТ ВОООПРОООСАААА', GAME.question.result)
    if(GAME.question.type=='quiz'){
      $('#question-1 .left .unit').hide();
      $('#question-1 .right .answer-true .qa').text(GAME.resultQuestion.current_answer);
      $('#question-1 .right .answer-true').slideDown(100);
      $.each(GAME.users, function(index, value){
        var _answ = GAME.resultQuestion.results[value.id];
        var $unit = $('#question-1 .left .unit.'+value.color);
        if (_answ) {
          $unit.show();
          $unit.data('place', _answ.place);
          if (_answ.answer == 99999) {
            _answ.answer = '';
          }
          $unit.find('.timer').text(_answ.answer);
          //$unit.find('.timer').text('10 сек.');
          $unit.find('.timer').prev('.led').removeClass('black').addClass('red');
          $unit.find('.answer').text(_answ.seconds+' сек.');
          }
        })
      if (whoTurn_is_run == false) {
        whoTurn();
      }
      orderPlayers();
      //showQuestionResult_timeout = setTimeout(hidePoppups, 7000);
    } else {
      $.each(GAME.users, function(index, value){
        var _answ = GAME.resultQuestion.results[value.id];
        console.log(_answ)
        if (_answ) {
          var _usr = getUserById(value.id);
          if (_answ.correctly==1) {
            $('#question-2 .a a').eq(_answ.current_answer_index).addClass('true');
            $('#question-2 .a a').eq(_answ.current_answer_index).addClass(_usr.color);
          }
          $('#question-2 .a a:contains("'+value.answer+'")').addClass(_usr.color);
        }
      });
    }
    showQuestionResult_timeout = setTimeout(function(){
      hidePoppups(function(){
        tryToConquer();
      });
    }, 7000);
  })
  //alert(_s);
  //whoTurn();
}


function afterAnswer() {
  $('#question-1 form.a, #question-1 .numpad, #question-1 .right .timer').slideUp(100);
  $('#question-1 .right .answerlkhbdsfksdlhfg').slideDown(100);
}

$(document).ready(function () {

  $('#question-1 form.a').submit(function(){
    GAME.question.answer = $(this).find('input').val();
    if (parseInt(GAME.question.answer)!=99999) {
      $('#question-1 .right .answerlkhbdsfksdlhfg .qa').text(GAME.question.answer);
    } else {
      $('#question-1 .right .answerlkhbdsfksdlhfg .qa').text('');
    }
    //GAME.question.answer
    $(this).find('input').val('');
    GAME.question.time = quiz_timer_default - $('#question-1 .right .timer').text();
    afterAnswer();
    sendQuestionAnswer(function(){
      //afterAnswer();
      getResultQuestion();
    });
    //GAME.sendQuestionAnswer();
    //GAME.getResultQuestion();
  });

})