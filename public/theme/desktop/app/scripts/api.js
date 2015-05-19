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

getNormalQuestion = function(callback){
    callback = callback || function(){};
    $.ajax({
        type: "POST",
        url: '/game/question/get-normal',
        data: {game: GAME.game_id, users: GAME.users_question},
        dataType: 'json',
        success: function (response) {
            if (response.status) {
                //GAME.user_step = 0;
                parseGameData(response);
                /*GAME.response = response.responseJSON;
                GAME.question.id = GAME.response.question.id;
                GAME.question.text = GAME.response.question.text;
                GAME.question.answers = GAME.response.question.answers;
                GAME.question.type = GAME.response.question.type;*/
                callback();
                //GAME.parseQuestionResponse();
                //$("#js-server-response").html(JSON.stringify(GAME.response));
            }
        },
        error: function (xhr, textStatus, errorThrown) {}
    });
}

getQuizQuestion = function(_users, callback){
    callback = callback || function(){}
    _users = _users || GAME.users
    if (GAME.stage==2) {
        alert('тревога. Лишний запрос!')
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
            if(GAME.stage == 1){
                if (response.responseJSON.result == 'retry') {
                  //console.log(response)
                  setTimeout(getResultQuestion, 500)
                } else if (response.responseJSON.result == 'standoff') {
                  alert('Ничья')
                  //console.log(response)
                } else {
                  showQuestionResult(response);
                }
            } else if (GAME.stage==2) {
                if(response.responseJSON.result === 'standoff'){
                    //GAME.question = {};
                    //GAME.getQuizQuestion();
                    alert('Ничья')
                }else if(response.responseJSON.result === 'retry'){
                    setTimeout(getResultQuestion, 500)
                }else if(typeof response.responseJSON.result == "object"){
                    console.log('INSPECT!', response)
                    /*GAME.question = {};
                    GAME.users_question = [];
                    GAME.steps = GAME.response.result[GAME.user.id];*/
                }
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