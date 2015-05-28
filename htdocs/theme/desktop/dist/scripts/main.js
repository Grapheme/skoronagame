function getUserById(e){var t;return $.each(GAME.users,function(s,n){n.id==e&&(t=n)}),t}function parseGameData(e){e.responseJSON.users&&(GAME.users=e.responseJSON.users,$.each(e.responseJSON.users,function(t,s){if(s.id==e.responseJSON.current_user)GAME.user=s;else{var n=!1;$.each(GAME.enemies,function(e,t){t.id==s.id&&(n=!0,GAME.enemies[e]=s)}),0==n&&GAME.enemies.push(s)}})),e.responseJSON.question&&(GAME.question=e.responseJSON.question),GAME.game_id=e.responseJSON.game_id,GAME.stage=e.responseJSON.game_stage,GAME.status=e.responseJSON.game_status,GAME.map=e.responseJSON.map,e.responseJSON.settings&&(GAME.next_turn=e.responseJSON.settings.next_step||0,GAME.duel=e.responseJSON.settings.duel?e.responseJSON.settings.duel:{},e.responseJSON.settings.stage2_tours_json&&(GAME.stage2_tours_json=$.parseJSON(e.responseJSON.settings.stage2_tours_json),renderSteps())),GAME.response=e.responseJSON}function startQuizeTimer(){clearInterval(quiz_interval);var e=quiz_timer_default;$("#question-1 .right .timer").text(e),quiz_interval=setInterval(function(){$("#question-1 .right .timer").text(e),e--,0>e&&quizeExpire()},1e3)}function quizeExpire(){clearInterval(quiz_interval),$(".numpad").is(":visible")&&($("#question-1 form.a").find("input").val(99999),$("#question-1 form.a").submit())}function startNormalTimer(){clearInterval(normal_interval);var e=quiz_timer_default;$("#question-2 .timer").text(e),normal_interval=setInterval(function(){$("#question-2 .timer").text(e),e--,0>e&&normalExpire()},1e3)}function normalExpire(){clearInterval(normal_interval),0==$("#question-2 .a a.active").size()&&(GAME.question.answer=99999,GAME.question.time=quiz_timer_default-$("#question-2 .timer").text(),sendQuestionAnswer(function(){getResultQuestion()}))}function compareUsers(e,t){return t.points-e.points}function renderGameOver(){GAME.users.sort(compareUsers),$("#winer .places .first .name").text(GAME.users[0].name),""!=GAME.users[0].photo&&$("#winer .places .first .ava .img").css({"background-image":"url("+GAME.users[0].photo+")"}),$("#winer .places .second .name").text(GAME.users[1].name),""!=GAME.users[1].photo&&$("#winer .places .second .ava .img").css({"background-image":"url("+GAME.users[1].photo+")"}),$("#winer .places .third .name").text(GAME.users[2].name),""!=GAME.users[2].photo&&$("#winer .places .third .ava .img").css({"background-image":"url("+GAME.users[2].photo+")"}),openFrame("winer"),showPoppups()}function quizQuesionRender(e){e=e||[],clearInterval(quiz_interval),clearInterval(normal_interval),renderPlayers(),e.length>0&&($("#question-1 .left .unit").hide(),$.each(e,function(e,t){var s=getUserById(t);$("#question-1 .left .unit."+s.color).show()})),getQuizQuestion(e,function(){$("#question-1 .left .timer").text("...").prev(".led").removeClass("red").addClass("black"),$("#question-1 .left .answer").text(""),$("#question-1 .right .answerlkhbdsfksdlhfg").slideUp(),$("#question-1 form.a, #question-1 .numpad, #question-1 .right .timer").slideDown(),$("#question-1 .right .q").html(GAME.question.text),$("#question-1 .right .answer-true .qa").text(""),$("#question-1 .right .answer-true").slideUp(100),openFrame("question-1"),showPoppups(),startQuizeTimer()})}function takingLand(){getGame(function(){quizQuesionRender()})}function matchmaking(){getGame(function(){"wait"==GAME.status?(renderPlayers(),setTimeout(matchmaking,1e3)):(createPlayers(),renderPlayers(),("start"==GAME.status||"ready"==GAME.status)&&(renderMap(!0),hidePoppups(),0==GAME.stage&&takingLand(),1==GAME.stage&&takingLand(),2==GAME.stage&&whoTurn()))})}function renderSteps(){$(".infowindow.tour1").hide(),$(".infowindow.tour2").show(),$.each(GAME.stage2_tours_json,function(e,t){var s=$(".infowindow.tour2 .tour.n"+(e+1));GAME.response&&GAME.response.settings&&GAME.response.settings.current_tour==e?s.addClass("active"):s.removeClass("active"),$.each(t,function(e,t){var n=Object.keys(t)[0],r=(t[n],getUserById(n)),a=s.find(".flag").eq(e);a.removeClass("red green blue").addClass(r.color),GAME.next_turn==n?a.addClass("active"):a.removeClass("active")})})}function tryToConquer(){2==GAME.stage&&"normal"==GAME.question.type&&setTimeout(hidePoppups,1e3),normalQuestionIsrender=!1,getGame(function(){console.log(GAME.stage,GAME.mustConquer,GAME.user.available_steps),2==GAME.stage&&GAME.mustConquer&&(GAME.user.available_steps>0||GAME.question.result&&1==GAME.question.result[GAME.user.id])&&sendConquestEmptyTerritory(GAME.mustConquer,function(){})})}function createPlayers(){$.each(GAME.users,function(e,t){GAME.user.id!=t.id?$("#question-1 .left .unit").eq(e).find(".name").text(t.name).addClass(t.color):$("#question-1 .left .unit").eq(e).find(".name").text("Вы").addClass(t.color),$("#question-1 .left .unit").eq(e).addClass(t.color),""!=t.photo&&$("#question-1 .left .unit").eq(e).find(".ava .img").css({"background-image":"url("+t.photo+")"}),$("#question-1 .left .unit").eq(e).addClass(t.color)})}function renderPlayers(){$.each(GAME.users,function(e,t){!GAME.resultQuestion||!GAME.resultQuestion[t.id],$("#user-list").show();var s=$("#user-list .user."+t.color);s.find(".name").text(t.name),""!=t.photo&&s.find(".ava .img").css({"background-image":"url("+t.photo+")"}),s.find(".points").text(t.points),t.id==GAME.user.id&&$("#user-list .user."+t.color).find(".name").text("Вы"),t.id==GAME.next_turn&&($("#user-list .user").removeClass("active"),$("#user-list .user."+t.color).addClass("active"))}),$.each(GAME.enemies,function(e,t){0==e&&($("#mathcmaking .ava").first().find(".name").text(t.name),""!=t.photo&&$("#mathcmaking .ava").first().find(".img").css({"background-image":"url("+t.photo+")"})),1==e&&($("#mathcmaking .ava").last().find(".name").text(t.name),""!=t.photo&&$("#mathcmaking .ava").last().find(".img").css({"background-image":"url("+t.photo+")"}))}),""!=GAME.user.photo&&$("#mathcmaking .ava").eq(1).find(".img").css({"background-image":"url("+GAME.user.photo+")"})}function renderMap(e){if(e=e||!1)var t=0;else var t=500;$("#map").addClass("user_"+GAME.user.color),$.each(GAME.map,function(s,n){if(e){var r=parseInt(s)+1,a=$("#map #area-"+r);a.data("zone",n.zone).data("info",n),a.attr("data-zone",n.zone),n.user_id>0?(a.removeClass("empty"),a.addClass("reserved"),a.removeClass("red green blue"),a.addClass(getUserById(n.user_id).color)):(a.removeClass("reserved"),a.addClass("empty"))}else setTimeout(function(){$("<div>           capital:"+n.capital+"<br>          id:"+n.id+"<br>          lives:"+n.lives+"<br>          user_id:"+n.user_id+"<br>          zone:"+n.zone+"        </div>").appendTo(".temp-map").css({"background-color":n.settings.color}).data("zone",n.zone)},t*s)})}function orderPlayers(){$("#question-1 .left").find(".unit").sort(function(e,t){return $(e).data("place")-$(t).data("place")}).appendTo("#question-1 .left")}function afterAnswer(){$("#question-1 form.a, #question-1 .numpad, #question-1 .right .timer").slideUp(100),$("#question-1 .right .answerlkhbdsfksdlhfg").slideDown(100)}function isEmpty(e){if(null==e)return!0;if(e.length>0)return!1;if(0===e.length)return!0;for(var t in e)if(hasOwnProperty.call(e,t))return!1;return!0}function scale(){var e=$(window).width();$("#map").transition({scale:e/bg_width}),$("#user-list").transition({scale:e/bg_width})}function openFrame(e){"close"==e?_history.pop():_history.push(e),"mathcmaking"==e&&matchmaking();var t=_history[_history.length-1];$(".popup-wrapper .popup-holder .popup").removeClass("active"),$(".popup-wrapper .popup-holder .popup#"+t).addClass("active")}function sexyAlert(e,t,s){t=t||3,s=s||function(){},$("#sexy-alert .note").html()==e||$(".popup-wrapper").is(":visible")||(showPoppups(),$("#sexy-alert .note").html(e),openFrame("sexy-alert"),setTimeout(function(){hidePoppups(),s()},1e3*t))}function hidePoppups(){$(".popup-wrapper").fadeOut(100)}function showPoppups(){$(".popup-wrapper").fadeIn(100)}var GAME=GAME||{};GAME.game_id=0,GAME.user={},GAME.enemies=[],GAME.status=0,GAME.stage=0,GAME.response={},GAME.map={},GAME.question={},GAME.steps=0,GAME.user_step=0,GAME.statuses=["wait","start","ready","over"],GAME.users={},GAME.mustConquer=null;var getGame=function(e){e=e||function(){},$.ajax({type:"POST",url:"/game/get-game",data:{game:GAME.game_id},dataType:"json",success:function(t){t.status&&(parseGameData(t),e(),renderPlayers(),console.log(GAME.status),0==GAME.next_turn&&"over"==GAME.status&&overGame())},error:function(t,s,n){getGame(e)}})};overGame=function(){$.ajax({type:"POST",url:"/game/over-game",data:{game:GAME.game_id},dataType:"json",success:function(e){e.status&&(GAME.response=e.responseJSON,renderGameOver())},error:function(e,t,s){}})},getNormalQuestion=function(e){e=e||function(){},console.log("Получить нормальный вопрос! Данные отправлены:",{game:GAME.game_id,users:GAME.users_question}),$.ajax({type:"POST",url:"/game/question/get-normal",data:{game:GAME.game_id,users:GAME.users_question},dataType:"json",success:function(t){t.status?(parseGameData(t),e()):getNormalQuestion(e)},error:function(e,t,s){}})},getQuizQuestion=function(e,t){t=t||function(){},e=e||GAME.users,2==GAME.stage,$.ajax({type:"POST",url:"/game/question/get-quiz",data:{game:GAME.game_id,users:e},dataType:"json",success:function(s){s.status?(parseGameData(s),t()):getQuizQuestion(e,t)},error:function(s,n,r){getQuizQuestion(e,t)}})},sendConquestEmptyTerritory=function(e,t){t=t||function(){},$.ajax({type:"POST",url:"/game/conquest/territory",data:{game:GAME.game_id,zone:e},dataType:"json",success:function(e){e.status&&(console.log(e,"ОТВЕТ НА ЗАХВАТ"),t())},error:function(e,t,s){}})},getUsersResultQuestions=function(e){e=e||function(){},$.ajax({type:"POST",url:"/game/question/get-users-results",data:{game:GAME.game_id,question:GAME.question.id,type:GAME.question.type},dataType:"json",success:function(t){t.status&&(GAME.resultQuestion=t.responseJSON,e())},error:function(e,t,s){}})},getResultQuestion=function(){$.ajax({type:"POST",url:"/game/question/get-result",data:{game:GAME.game_id,question:GAME.question.id,type:GAME.question.type,zone:GAME.conquerorZone},dataType:"json",success:function(e){e.status&&(console.log(e.responseJSON.result),"quiz"==GAME.question.type?"retry"==e.responseJSON.result?setTimeout(getResultQuestion,1e3):"standoff"==e.responseJSON.result?2==GAME.stage?quizQuesionRender([GAME.duel.conqu,GAME.duel.def]):quizQuesionRender():(showQuestionResult(e),2==GAME.stage&&(tryToConquer(),GAME.question={})):"normal"==GAME.question.type&&("standoff"===e.responseJSON.result?(console.log("Ничья"),quizQuesionRender([GAME.duel.conqu,GAME.duel.def])):"retry"===e.responseJSON.result?setTimeout(getResultQuestion,1e3):"object"==typeof e.responseJSON.result&&(console.log("РЕЗУЛЬТАТ!!",e.responseJSON.result),tryToConquer(),GAME.question={})))},error:function(e,t,s){getResultQuestion()}})},sendQuestionAnswer=function(e){e=e||function(){},$.ajax({type:"POST",url:"/game/question/send-answer",data:{game:GAME.game_id,question:GAME.question.id,answer:GAME.question.answer,time:GAME.question.time},dataType:"json",success:function(t){t.status?e():sendQuestionAnswer(e)},error:function(t,s,n){sendQuestionAnswer(e)}})};var firstTime=(new Date).getTime()/1e3,search_timeout=90,quiz_timer_default=10,quiz_interval,normal_interval;$("body").on("click","#question-2 .a a",function(e){e.preventDefault(),0==$("#question-2 .a a.active").size()&&(clearInterval(normal_interval),GAME.question.answer=$(this).data("id"),GAME.question.time=quiz_timer_default-$("#question-2 .timer").text(),$(this).addClass("active"),sendQuestionAnswer(function(){getResultQuestion()}))}),$("body").on("click","#map .area",function(e){GAME.user.id!=GAME.next_turn||1!=GAME.stage||$(this).hasClass("reserved")?GAME.user.id==GAME.next_turn&&2==GAME.stage&&$(this).data("info").user_id!=GAME.user.id&&(GAME.mustConquer=$(this).data("info").zone,renderNormalQuestion(GAME.user.id,$(this).data("info").user_id)):sendConquestEmptyTerritory($(this).data("zone"),function(){getGame(function(){renderMap(!0)})})}),normalQuestionIsrender=!1,renderNormalQuestion=function(e,t){normalQuestionIsrender=!0,e=e||GAME.user.id,GAME.users_question={conqu:e,def:t},clearInterval(quiz_interval),clearInterval(normal_interval),getNormalQuestion(function(){clearInterval(quiz_interval),clearInterval(normal_interval),startNormalTimer(),$("#question-2 .left").removeClass("red green blue"),$("#question-2 .right").removeClass("red green blue"),$("#question-2 .left").addClass(getUserById(GAME.users_question.conqu).color),$("#question-2 .left .score").text(getUserById(GAME.users_question.conqu).points),$("#question-2 .right").addClass(getUserById(GAME.users_question.def).color),$("#question-2 .right .score").text(getUserById(GAME.users_question.def).points),$("#question-2 .q").html(GAME.question.text),$("#question-2 .a").html(""),$.each(GAME.question.answers,function(e,t){$('<a href="">'+t+"</a>").appendTo($("#question-2 .a")).data("id",e)}),openFrame("question-2"),showPoppups()})};var last_turn=0,whoTurn_is_run=!1;whoTurn=function(){whoTurn_is_run=!0,getGame(function(){if(console.log(GAME.next_turn),1==GAME.stage){if(GAME.next_turn==GAME.user.id)sexyAlert("Ваш ход!"),$("#map .areas").addClass("active"),GAME.next_turn!=last_turn&&(last_turn=GAME.next_turn);else{$("#map .areas").removeClass("active");var e=getUserById(GAME.next_turn);if(e&&sexyAlert("Ходит игрок: "+e.name),GAME.next_turn!=last_turn){last_turn=GAME.next_turn;var e=getUserById(GAME.next_turn);e||(renderMap(!0),takingLand())}}setTimeout(whoTurn,1e3)}else if(2==GAME.stage){if(console.log("Этап захвата"),GAME.next_turn==GAME.user.id)sexyAlert("Ваш ход!");else{var e=getUserById(GAME.next_turn);e&&sexyAlert("Ходит игрок: "+e.name),GAME.next_turn!=last_turn&&(last_turn=GAME.next_turn),isEmpty(GAME.duel)||GAME.duel.def==GAME.user.id&&($(".popup-wrapper").is(":visible")||renderNormalQuestion(GAME.duel.conqu,GAME.duel.def))}"quize"==GAME.question.type||"normal"==GAME.question.type,setTimeout(whoTurn,1e3)}renderMap(!0)})},showQuestionResult=function(e){GAME.question.result=e.responseJSON.result;getUsersResultQuestions(function(){$("#question-1 .left .unit").hide(),$("#question-1 .right .answer-true .qa").text(GAME.resultQuestion.current_answer),$("#question-1 .right .answer-true").slideDown(100),$.each(GAME.users,function(e,t){var s=GAME.resultQuestion.results[t.id],n=$("#question-1 .left .unit."+t.color);s&&(n.show(),n.data("place",s.place),99999==s.answer&&(s.answer=""),n.find(".timer").text(s.answer),n.find(".timer").prev(".led").removeClass("black").addClass("red"),n.find(".answer").text(s.seconds+" сек."))}),0==whoTurn_is_run&&whoTurn(),tryToConquer(),orderPlayers(),setTimeout(hidePoppups,7e3)})},$(document).ready(function(){$("#question-1 form.a").submit(function(){GAME.question.answer=$(this).find("input").val(),$("#question-1 .right .answerlkhbdsfksdlhfg .qa").text(99999!=parseInt(GAME.question.answer)?GAME.question.answer:""),$(this).find("input").val(""),GAME.question.time=quiz_timer_default-$("#question-1 .right .timer").text(),afterAnswer(),sendQuestionAnswer(function(){getResultQuestion()})})}),console.log("'Allo 'Allo!");var hasOwnProperty=Object.prototype.hasOwnProperty,bg_width=$("#map").width();$(window).resize(function(){scale()}),$(window).load(function(){bg_width=$("#map").width(),scale()}),$(".areas .countur svg path").hover(function(){if($(this).closest(".area").toggleClass("active"),$(this).closest(".area").data("info")){var e=$(this).closest(".area").data("info").points||0;$(".infowindow-holder .infowindow-small").text(e),$(".infowindow-holder").toggle()}});var _history=[],_history=["menu"];$("body").on("click","a",function(e){var t=$(this).attr("href").split("#");t[1]&&(openFrame(t[1]),e.preventDefault())}),$("body").on("click",".numpad a",function(e){var t=$(this).closest(".numpad").prev("form").find("input");t.focus(),$(this).hasClass("clear")?t.val(""):$(this).hasClass("del")?t.val(t.val().slice(0,-1)):t.val().length<4&&t.val(t.val()+$(this).text()),e.preventDefault()}),$("body").on("click",".popup.with-tabs .tabs-btn a",function(e){e.preventDefault();var t=$(".popup.with-tabs .tabs-btn a").index($(this));$(this).is(".active")||($(".popup.with-tabs .tabs-btn a").removeClass("active"),$(this).addClass("active"),$(".popup.with-tabs .tabs .tab").removeClass("active"),$(".popup.with-tabs .tabs .tab").eq(t).addClass("active"))}),$("#help .q").click(function(){$(this).toggleClass("active"),$(this).next(".a").slideToggle()}),_skoronagame_.open_frame&&openFrame(_skoronagame_.open_frame),sendForm=function(e){if($(e).is(".noajax"))return alert("!!!"),!1;console.log(e),console.log($(e));var t=$(e).attr("action"),s=$(e).attr("method"),n=$(e).attr("data-result");$.ajax({type:s,url:t,data:$(e).serialize(),success:function(t){console.log(t),1==t.status?(t.redirect&&(location.href=t.redirect),openFrame(n)):0==t.status&&$(e).prepend('<label class="error">'+t.responseText+"</label>")},error:function(e,t,s){console.log(e),console.log(t)}})},$("#login form").validate({rules:{email:{required:!0,email:!0},password:"required"},messages:{email:{required:"Обязательное поле",email:"Неверный формат. Попробуйте еще"},password:"Обязательное поле"},submitHandler:function(e){sendForm(e)}}),$("#register form").validate({rules:{email:{required:!0,email:!0},name:"required"},messages:{email:{required:"Обязательное поле",email:"Неверный формат. Попробуйте еще"},name:"Обязательное поле"},submitHandler:function(e){sendForm(e)}}),$("#new-password form").validate({rules:{},messages:{},submitHandler:function(e){sendForm(e)}}),$("form").submit(function(e){return $(this).is(".noajax")?!1:void 0});