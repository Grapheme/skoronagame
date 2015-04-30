/*  Author: Grapheme Group
 *  http://grapheme.ru/
 */

var GAME = GAME || {};
GAME.game_id = 0;
GAME.user = {};
GAME.status = 0;
GAME.stage = 0;
GAME.response = {};
GAME.map = {};
GAME.question = {};
GAME.steps = 0;
GAME.user_step = 0;
GAME.statuses = ['wait','start','ready','over'];
GAME.timer = {timer_object:{},time:10};
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
GAME.getQuizQuestion = function(){
    $.ajax({
        type: "POST",
        url: '/game/question/get-quiz',
        data: {game: GAME.game_id},
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
                GAME.question.type = GAME.response.question.type;
                GAME.parseQuestionResponse();
                $("#js-server-response").html(JSON.stringify(GAME.response));
            }
            $("#js-server-notification").html(response.responseText);
        },
        error: function (xhr, textStatus, errorThrown) {}
    });
}
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
GAME.getResultQuestion = function(){
    $.ajax({
        type: "POST",
        url: '/game/question/get-result',
        data: {game: GAME.game_id, question: GAME.question.id},
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
GAME.sendConquestEmptyTerritory = function(territory){
    $.ajax({
        type: "POST",
        url: '/game/conquest/territory',
        data: {game: GAME.game_id, zone: $(territory).data('zone')},
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
GAME.sendConquestTerritory = function(territory){

}
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
        if(GAME.user_step == 0){
            if(GAME.stage == 1 && $.isEmptyObject(GAME.question)){
                GAME.getQuizQuestion();
                // этап 1-й
            }else if(GAME.stage == 2){
                // этап 2-й
            }
        }
    }
}
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
        if(GAME.question.id > 0){
            $("#js-question-game").parent().show();
            $("#normal-question-block").show();
        }else{
            //GAME.getQuizQuestion();
        }
    }
}
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
            $("#js-question-result").parent().hide();
            $("#js-question-game").parent().hide();
            GAME.steps = Math.abs(GAME.response.result[GAME.user.id]-3);
        }
    }else if(GAME.stage == 2){
        // этап 2
    }
}
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
        $(block).clone(true).appendTo($("#russia-map-blocks")).removeAttr('id').addClass(block_class).attr('data-zone', value.zone).attr('data-lives', value.lives).attr('data-user', value.user_id).attr('data-zone_id', value.id).css('background-color', value.settings.color).html('Zona: ' + value.zone + '<br>ID: ' + value.id + '<br>User: ' + value.user_id + '<br>Lives: ' + value.lives);
    });
    $("#map-block-template").remove();
    $("#russia-map").show();
}
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
        $(".territory-block[data-zone='"+value.zone+"']").addClass(block_class).attr('data-zone', value.zone).attr('data-lives', value.lives).attr('data-user', value.user_id).attr('data-zone_id', value.id).css('background-color', value.settings.color).html('Zona: ' + value.zone + '<br>ID: ' + value.id + '<br>User: ' + value.user_id + '<br>Lives: ' + value.lives);
    });
}
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
            GAME.question.answer = null;
            GAME.sendQuestionAnswer();
            $("#" + GAME.question.type + "-question-block").hide();
        }
    }, 1000);
}
GAME.isEmptyMap = function() {
    return GAME.map.length === 0;
}
$(document).ready(function () {
    GAME.game_id = $("#game-id").html();
    if(isNaN(GAME.game_id) === false) GAME.getGame(this);
    $("#js-start-game").click(function(event){
        event.preventDefault();
        GAME.getGame();
        $(this).parent().hide();
        $("#js-update-game").parent().show();
    });
    $("#js-update-game").click(function(event){
        event.preventDefault();
        GAME.getGame();
    });
    $("#js-question-game").click(function(event){
        event.preventDefault();
        GAME.getQuizQuestion();
    });
    $("#js-question-result").click(function(event){
        event.preventDefault();
        GAME.getResultQuestion();
    });

    $("#quiz-question-form").submit(function(){
        clearInterval(GAME.timer.timer_object);
        GAME.question.answer = $("#quiz-question-answer").val().trim();
        $("#quiz-question-block").hide();
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
        if(GAME.steps > 0){
            if($(this).attr('data-user') != GAME.user.id && GAME.user_step == GAME.user.id){
                GAME.sendConquestTerritory(this);
            }
        }
    });
});