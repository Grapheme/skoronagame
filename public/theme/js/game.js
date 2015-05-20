/*  Author: Grapheme Group
 *  http://grapheme.ru/
 */

var GAME = GAME || {};
GAME.statuses = ['wait','start','ready','over'];        // возможные статусы игры

GAME.reInitialize = function(){

    GAME.game_id = 0;                                       // id игры
    GAME.user = {};                                         // пользователь
    GAME.status = 0;                                        // статус игры
    GAME.stage = 0;                                         // этап игры
    GAME.response = {};                                     // ответ от сервера
    GAME.map = {};                                          // карта
    GAME.users_question = [];                               // массив id пользователей которым нужно создать вопрос. пустой - если всем
    GAME.question = {};                                     // текущий вопрос
    GAME.steps = 0;                                         // доступные шаги
    GAME.user_step = 0;                                     // id пользователя который сейчас делает шаг
    GAME.conquerorZone = 0                                  // номер зоны для завоевания
    GAME.timer = {timer_object:{},time:10};
}

/*
Метод получает информацию о текущей игре или инициирует новую
Отправляет:
        game  - (int)ID игры или (string)null - если игра не существует.
Результат:
 game_id - (int) ID текущей игры
 game_stage - (int) этап игры (0-не началась, 1,2 и т.д.)
 game_status - (string) статус игры. (GAME.statuses)
        wait - ожидает начала
        start - игра началась
        ready - игра активная
        over - игра завершена
 current_user - (int) ID текущего пользователя
 users - список пользователей
        color - цвет
        points - очки
        place - занятое место
        status - статус пользователя
        available_steps - доступно ходов
        make_steps - сделано ходов
        settings - (JSON) иные данные
 map - карта
        id - (int) ID области
        zone - (int) номер области (1-15)
        user_id - (int) ID пользователя владельца области
        capital - (int) Столица (1/0)
        lives - (int) количество доступных жизней области
        settings - (JSON) иные данные
        settings.color - код цвета области
 settings - (JSON) иные данные
        settings.next_step - (int) ID пользователя который делает следующий шаг
        settings.message - (string) сообщение для всех (необязательное)

*/

GAME.getGame = function(){
    $.ajax({
        type: "POST",
        url: '/game/get-game',
        data: {game: GAME.game_id},
        dataType: 'json',
        beforeSend: function () {
            $("#js-server-response").html('');
        },
        success: function (response) {
            if (response.status) {
                GAME.response = response.responseJSON;
                GAME.map = GAME.response.map;
                GAME.parseGameResponse();
                $("#game-id").html(GAME.game_id);
                $("#js-server-response").html(JSON.stringify(GAME.response));
            }
            $("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {}
    });
};

/*
 Метод завершает текущую игру
 Отправляет:
 game  - (int)ID игры

 */
GAME.overGame = function(){

    $.ajax({
        type: "POST",
        url: '/game/over-game',
        data: {game: GAME.game_id},
        dataType: 'json',
        beforeSend: function () {
            $("#js-server-response").html('');
        },
        success: function (response) {
            if (response.status) {
                GAME.response = response.responseJSON;
                $("#game-id").html(GAME.game_id);
                $("#js-server-response").html(JSON.stringify(GAME.response));
            }
            $("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {}
    });
}
/*
 Метод получает Квиз-вопрос
 Отправляет:
        game  - (int)ID игры.
        users - (array) id пользователей кому выдавать вопрос
 Результат:
    ...
 question - вопрос
        id - (int) ID вопроса
        text - (string) текст вопроса
        type - (string) тип вопроса (quiz/normal)
*/
GAME.getQuizQuestion = function(){
    $.ajax({
        type: "POST",
        url: '/game/question/get-quiz',
        data: {game: GAME.game_id, users: GAME.users_question},
        dataType: 'json',
        beforeSend: function () {
            $("#js-server-response").html('');
        },
        success: function (response) {
            if (response.status) {
                GAME.user_step = 0;
                GAME.response = response.responseJSON;
                GAME.question.id = GAME.response.question.id;
                GAME.question.text = GAME.response.question.text;
                GAME.question.answers = [];
                GAME.question.type = GAME.response.question.type;
                GAME.parseQuestionResponse();
                $("#js-server-response").html(JSON.stringify(GAME.response));
            }
            $("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {}
    });
}

/*
 Метод получает нормальный-вопрос
 Отправляет:
 game  - (int)ID игры.
 users - (array) id пользователей кому выдавать вопрос или пустой если всем
 Результат:
 ...
 question - вопрос
 id - (int) ID вопроса
 text - (string) текст вопроса
 type - (string) тип вопроса (quiz/normal)
 */
GAME.getNormalQuestion = function(){
    $.ajax({
        type: "POST",
        url: '/game/question/get-normal',
        data: {game: GAME.game_id, users: GAME.users_question},
        dataType: 'json',
        beforeSend: function () {
            $("#js-server-response").html('');
        },
        success: function (response) {
            if (response.status) {
                GAME.user_step = 0;
                GAME.response = response.responseJSON;
                GAME.question.id = GAME.response.question.id;
                GAME.question.text = GAME.response.question.text;
                GAME.question.answers = GAME.response.question.answers;
                GAME.question.type = GAME.response.question.type;
                GAME.parseQuestionResponse();
                $("#js-server-response").html(JSON.stringify(GAME.response));
            }
            $("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {}
    });
}
/*
 Метод отправляет ответ на вопрос
 Отправляет:
        game  - (int)ID игры.
        question  - (int)ID вопроса.
        answer  - (int) ответ.
        time  - (int) время потраченное на ответ.
 Результат:
 ...
 status - (boolean) результат обработки
 responseText - (string) текст результата (не обязательно)
 */

GAME.sendQuestionAnswer = function(){
    $.ajax({
        type: "POST",
        url: '/game/question/send-answer',
        data: {game: GAME.game_id, question: GAME.question.id, answer: GAME.question.answer, time: GAME.question.time},
        dataType: 'json',
        beforeSend: function () {
            $("#js-server-response").html('');
        },
        success: function (response) {
            if (response.status) {
                GAME.response = response.responseJSON;
                $("#js-server-response").html(JSON.stringify(GAME.response));
            }
            $("#js-question-result").parent().show();
            $("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {
        }
    });
}

/*
 Метод запрашивает состояние ответов на вопрос
 Отправляет:
 game  - (int)ID игры.
 question  - (int)ID вопроса.
 type  - (string) тип вопроса.
 Результат:
 ...
 result - результат ответов
 result = (string)retry -  повтор запроса
 result = (string)standoff - ничья
 result = (JSON) = "12":3,"13":1,"14":2 . ID пользователя: занятое место (1-3)
 responseText - (string) текст результата (не обязательно)

 */
GAME.getResultQuestion = function(){
    $.ajax({
        type: "POST",
        url: '/game/question/get-result',
        data: {game: GAME.game_id, question: GAME.question.id, type: GAME.question.type},
        dataType: 'json',
        beforeSend: function () {
            $("#js-server-response").html('');
        },
        success: function (response) {
            if (response.status) {
                GAME.response = response.responseJSON;
                GAME.parseResultQuestionResponse();
                $("#js-server-response").html(JSON.stringify(GAME.response));
            }
            $("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {
        }
    });
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

GAME.getUsersResultQuestions = function () {

    $.ajax({
        type: "POST",
        url: '/game/question/get-users-results',
        data: {game: GAME.game_id, question: 1, type: 'normal'},
        dataType: 'json',
        beforeSend: function () {
            $("#js-server-response").html('');
        },
        success: function (response) {
            if (response.status) {
                GAME.response = response.responseJSON;
                $("#js-server-response").html(JSON.stringify(GAME.response));
            }
            $("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {
        }
    });
}
/*
 Метод отправляет запрос на захват пустой территории
 Отправляет:
 game  - (int)ID игры.
 zone  - (int)номер зоны.
 Результат:
 ...
 status - (boolean) результат обработки
 responseText - (string) текст результата (не обязательно)
 */
GAME.sendConquestEmptyTerritory = function(territory){
    GAME.conquerorZone = $(territory).data('zone');
    $.ajax({
        type: "POST",
        url: '/game/conquest/territory',
        data: {game: GAME.game_id, zone: GAME.conquerorZone},
        dataType: 'json',
        beforeSend: function () {
            $("#js-server-response").html('');
        },
        success: function (response) {
            if (response.status) {
                $(territory).css('background-color', GAME.user.color).attr('data-user',GAME.user.id).html('Zona: ' + $(territory).data('zone') + '<br>ID: ' + $(territory).data('zone_id') + '<br>User: ' + $(territory).data('user') + '<br>Lives: ' + $(territory).data('lives'));
                GAME.response = response.responseJSON;
                GAME.steps--;
                if (GAME.steps == 0) {
                    $("#js-question-game").parent().show();
                }
                $("#js-server-response").html(JSON.stringify(GAME.response));
            }
            $("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {}
    });
}

GAME.sendConquestTerritory = function(){
    $.ajax({
        type: "POST",
        url: '/game/conquest/territory',
        data: {game: GAME.game_id, zone: GAME.conquerorZone},
        dataType: 'json',
        beforeSend: function () {
            $("#js-server-response").html('');
        },
        success: function (response) {
            if (response.status) {
                $(".js-map-block[data-zone='"+GAME.conquerorZone+"']").css('background-color', GAME.user.color).attr('data-user',GAME.user.id).html('Zona: ' + $(territory).data('zone') + '<br>ID: ' + $(territory).data('zone_id') + '<br>User: ' + $(territory).data('user') + '<br>Lives: ' + $(territory).data('lives'));
                GAME.response = response.responseJSON;
                GAME.steps--;
                GAME.conquerorZone = 0;
                if (GAME.steps == 0) {
                    $("#js-question-game").parent().show();
                }
                $("#js-server-response").html(JSON.stringify(GAME.response));
            }
            $("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {}
    });
}

/*
 Метод обрабатывает игровую информацию
 */
GAME.parseGameResponse = function(){
    GAME.game_id = GAME.response.game_id;
    GAME.status = GAME.response.game_status;
    GAME.stage = GAME.response.game_stage;
    GAME.user_step = GAME.response.settings.next_step;
    $.each(GAME.response.users,function(index, value){
        if(value.id == GAME.response.current_user) {
            GAME.user = value;
            $("#js-user-response").html(JSON.stringify(GAME.user));
        }
    });
    if(GAME.status == GAME.statuses[1]){
        GAME.createMap();
        if(GAME.user_step == GAME.user.id){
            GAME.stage = 1;
            GAME.getQuizQuestion();
        }
    }else if(GAME.status == GAME.statuses[2]){
        GAME.updateMap();
        if(GAME.user_step == 0 && GAME.stage == 1 && $.isEmptyObject(GAME.question)){
            GAME.getQuizQuestion();
        }
        if(GAME.stage == 2 && typeof GAME.response.settings.duel == "object"){
            if (GAME.response.settings.duel[0] == GAME.user.id || GAME.response.settings.duel[1] == GAME.user.id) {
                GAME.getNormalQuestion();
            }
        }
    }else if(GAME.status == GAME.statuses[3]){
        alert(GAME.response.settings.message);
        GAME.reInitialize();
        location.reload();
    }
}

/*
 Метод обрабатывает ответ сервера на запрос - "дай вопрос"
 */
GAME.parseQuestionResponse = function(){
    if(GAME.stage == 1){
        if(GAME.question.id > 0){
            $("#js-question-game").parent().hide();
            $("#quiz-question-text").html(GAME.question.text);
            $("#quiz-question-block").show();
            GAME.startTimer();
        }else{
            GAME.getQuizQuestion();
        }
    }else if(GAME.stage == 2){
        if(GAME.question.type == 'normal'){
            if(GAME.question.id > 0 && GAME.question.answers.length > 0){
                $("#js-question-game").parent().show();
                var inputs = '';
                $.each(GAME.question.answers,function(index, value){
                    inputs = inputs + '<input type="radio" name="answer" value="'+index+'">'+value;
                });
                $("#normal-question-text").html(GAME.question.text);
                $("#normal-question-answers").html(inputs);
                $("#normal-question-block").show();
                GAME.startTimer();
            }else{
                GAME.getNormalQuestion();
            }
        }else if(GAME.question.type == 'quiz'){
            if(GAME.question.id > 0){
                $("#js-question-game").parent().hide();
                $("#quiz-question-text").html(GAME.question.text);
                $("#quiz-question-block").show();
                GAME.startTimer();
            }else{
                GAME.getQuizQuestion();
            }
        }

    }
}

/*
 Метод обрабатывает ответ сервера на запрос - "какой результат на ответ"
 */

GAME.parseResultQuestionResponse = function(){
    if(GAME.stage == 1){
        if(GAME.response.result === 'standoff'){
            GAME.question = {};
            GAME.getQuizQuestion();
            $("#js-question-result").parent().hide();
            $("#js-question-game").parent().show();
        }else if(GAME.response.result === 'retry'){
            GAME.getResultQuestion();
            $("#js-question-result").parent().hide();
            $("#js-question-game").parent().hide();
        }else if(typeof GAME.response.result == "object"){
            GAME.question = {};
            GAME.users_question = [];
            $("#js-question-result").parent().hide();
            $("#js-question-game").parent().hide();
            GAME.steps = Math.abs(GAME.response.result[GAME.user.id]-3);
        }
    }else if(GAME.stage == 2){
        if(GAME.response.result === 'standoff'){
            GAME.question = {};
            GAME.getQuizQuestion();
        }else if(GAME.response.result === 'retry'){
            GAME.getResultQuestion();
        }else if(typeof GAME.response.result == "object"){
            GAME.question = {};
            GAME.users_question = [];
            GAME.steps = GAME.response.result[GAME.user.id];
            GAME.sendConquestTerritory();
        }
    }
}

/*
 Метод создания карты
 */

GAME.createMap = function(){
    var block = $("#map-block-template");
    var block_class = '';
    $.each(GAME.map, function (index, value) {
        if (value.user_id > 0)
            block_class = 'js-map-block';
        else
            block_class = 'js-map-empty-block';
        if (value.lives > 1)
            block_class = 'js-map-capital-block';
        $(block).clone(true).appendTo($("#russia-map-blocks")).removeAttr('id').addClass(block_class).attr('data-zone', value.zone).attr('data-lives', value.lives).attr('data-points', value.points).attr('data-user', value.user_id).attr('data-zone_id', value.id).css('background-color', value.settings.color).html('Zona: ' + value.zone + '<br>ID: ' + value.id + '<br>User: ' + value.user_id + '<br>Lives: ' + value.lives + '<br>Points: ' + value.points);
    });
    $("#map-block-template").remove();
    $("#russia-map").show();
}

/*
 Метод обновления карты
 */

GAME.updateMap = function(){
    if(GAME.isEmptyMap === false || $(".js-map-block").length == 0) GAME.createMap();
    var block_class = '';
    $.each(GAME.map, function (index, value) {
        if (value.user_id > 0)
            block_class = 'js-map-block';
        else
            block_class = 'js-map-empty-block';
        if (value.lives > 1)
            block_class = 'js-map-capital-block';
        $(".territory-block[data-zone='"+value.zone+"']").addClass(block_class).attr('data-zone', value.zone).attr('data-lives', value.lives).attr('data-points', value.points).attr('data-user', value.user_id).attr('data-zone_id', value.id).css('background-color', value.settings.color).html('Zona: ' + value.zone + '<br>ID: ' + value.id + '<br>User: ' + value.user_id + '<br>Lives: ' + value.lives + '<br>Points: ' + value.points);
    });
}

/*
 Метод запуска таймера при ответе на вопрос
 */

GAME.startTimer = function () {
    var timer;
    var questionTime = GAME.timer.time;
    var questionTimer = $("#" + GAME.question.type + "-question-timer span");
    questionTimer.text(questionTime).parent('p');
    GAME.question.time = 0;
    GAME.timer.timer_object = setInterval(function () {
        questionTimer.text(--questionTime);
        GAME.question.time = GAME.question.time + 1 || 1;
        if (questionTime <= 0){
            clearInterval(GAME.timer.timer_object);
            if(GAME.question.type == 'quiz'){
                GAME.question.answer = 0;
            }else if(GAME.question.type == 'normal'){
                GAME.question.answer = 100;
            }
            GAME.sendQuestionAnswer();
            $("#" + GAME.question.type + "-question-block").hide();
        }
    }, 1000);
}

/*
 Метод проверяет "не пустая" ли карта
 */

GAME.isEmptyMap = function() {
    return GAME.map.length === 0;
}
$(document).ready(function () {
    GAME.reInitialize();
    GAME.game_id = $("#game-id").html();
    if(isNaN(GAME.game_id) === false) GAME.getGame(this);
    $("#js-start-game").click(function(event){
        event.preventDefault();
        GAME.getGame();
        $(this).parent().hide();
        $("#js-update-game").parent().show();
        $("#js-over-game").parent().show();
    });
    $("#js-update-game").click(function(event){
        event.preventDefault();
        GAME.getGame();
    });
    $("#js-question-quiz-game").click(function(event){
        event.preventDefault();
        GAME.users_question = [];
        GAME.getQuizQuestion();
    });
    $("#js-question-normal-game").click(function(event){
        event.preventDefault();
        //GAME.users_question = [GAME.user.id,OTHER_USER_ID];
        GAME.users_question = [15,16];
        GAME.getNormalQuestion();
    });
    $("#js-question-result").click(function(event){
        event.preventDefault();
        GAME.getResultQuestion();
    });
    $("#js-over-game").click(function(event){
        event.preventDefault();
        GAME.overGame();
    });
    $("#js-users-questions-result").click(function(event){
        event.preventDefault();
        GAME.getUsersResultQuestions();
    });
    $("#quiz-question-form").submit(function(){
        clearInterval(GAME.timer.timer_object);
        GAME.question.answer = $("#quiz-question-answer").val().trim();
        $("#quiz-question-block").hide();
        GAME.sendQuestionAnswer();
        return false;
    });
    $("#normal-question-form").submit(function(){
        clearInterval(GAME.timer.timer_object);
        GAME.question.answer = $("#normal-question-answers input:checked").val().trim();
        $("#normal-question-block").hide();
        GAME.sendQuestionAnswer();
        return false;
    });
    $(document).on("click",".js-map-empty-block",function(event){
        event.preventDefault();

        if(GAME.steps > 0){
            if($(this).attr('data-user') != GAME.user.id && GAME.user_step == GAME.user.id){
                GAME.sendConquestEmptyTerritory(this);
            }
        }
    });
    $(document).on("click",".js-map-block",function(event){
        event.preventDefault();
        if($(this).attr('data-user') != GAME.user.id && GAME.user_step == GAME.user.id){
            GAME.users_question = [GAME.user.id,$(this).data('user')];
            GAME.conquerorZone = $(this).data('zone');
            GAME.getNormalQuestion();
        }
    });
});