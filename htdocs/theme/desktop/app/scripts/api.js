/*  Author: Grapheme Group
 *  http://grapheme.ru/
 */

var GAME = GAME || {};
GAME.game_id = 0;//19                                       // id игры
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
    console.log(GAME.mustConquer);
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
                        setTimeout(function(){
                            if (GAME.stage == 2) {
                                quizQuesionRender([GAME.duel.conqu, GAME.duel.def]);
                            } else {
                                quizQuesionRender();
                            }
                        }, 3000) 
                    //});
                //});
                    
                  //console.log(response)
                } else {
                  showQuestionResult(response);
                  if (GAME.stage == 2) {
                    tryToConquer();
                    GAME.question = {};
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
                    GAME.question = {};
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
      if (response.status) {
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