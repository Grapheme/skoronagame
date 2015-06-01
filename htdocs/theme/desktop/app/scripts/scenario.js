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

/*stage2_tours = [
[{4:false},{5:true},{6:true}],
[{6:true},{4:false},{5:true}]
]*/

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
        
        if (GAME.stage==0) {
          takingLand();
        }
        if (GAME.stage==1) {
          takingLand();
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
    setTimeout(hidePoppups, 1000);
  } else {
    //setTimeout(hidePoppups, 7000);
  }
  
  normalQuestionIsrender = false;
  getGame(function(){
    //console.log(GAME.stage, GAME.mustConquer, GAME.user.available_steps)
    if (GAME.stage == 2 && GAME.mustConquer && (GAME.user.available_steps > 0 || (GAME.question.result && GAME.question.result[GAME.user.id]==1) )) {
      var _area = getAreaById(GAME.mustConquer);
      if (_area.capital == 1) {
        sendConquestCapital(GAME.mustConquer, function(response){
          //console.log(arguments);
          //console.log(response);
          if (response.conquest_result == 'retry') {
            alert('повтор!');
            setTimeout(function(){
              $('#area-'+GAME.mustConquer).click();
            }, 2000)
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
    console.log(GAME.users);
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
      $('#user-list .user').removeClass('active');
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
    $(this).addClass('active');
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

var last_turn = 0;
var whoTurn_is_run = false;
whoTurn = function() {
  whoTurn_is_run = true;
  getGame(function(){
    if (GAME.stage == 1) {
      $('#map .areas').addClass('stage-1');
      if (GAME.next_turn==GAME.user.id) {
        sexyAlert('Ваш ход!');
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
          sexyAlert('Ходит игрок: '+ user_turn.name);
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
        sexyAlert('Ваш ход!');
        //alert('Ваш ход. Этап захвата.');
        //renderNormalQuestion();
      } else {
        var user_turn = getUserById(GAME.next_turn);
        if (user_turn) {
          sexyAlert('Ходит игрок: '+ user_turn.name);
        }
        if (GAME.next_turn != last_turn) {
          last_turn = GAME.next_turn;
          //var user_turn = getUserById(GAME.next_turn);
        };
        
        if (!isEmpty(GAME.duel)) {
          if (GAME.duel.def == GAME.user.id) {
            if (!$('.popup-wrapper').is(":visible")) {
            //if (normalQuestionIsrender == false) {
              renderNormalQuestion(GAME.duel.conqu, GAME.duel.def);
            }
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

showQuestionResult = function(response){
  GAME.question.result = response.responseJSON.result;
  var _s = '';
  
  /*$.each(GAME.users, function(index, value){
    value.place = GAME.question.result[value.id]
    _s = _s+'Игрок: '+value.color+' - '+ value.place +' место. \n'
  });*/
  
  getUsersResultQuestions(function(){
    //console.log('РУЗЕЛЬТАТ ВОООПРОООСАААА', GAME.question.result)
    $('#question-1 .left .unit').hide();
    $('#question-1 .right .answer-true .qa').text(GAME.resultQuestion.current_answer);
    $('#question-1 .right .answer-true').slideDown(100);
    $.each(GAME.users, function(index, value){
      var _answ = GAME.resultQuestion.results[value.id];
      var $unit = $('#question-1 .left .unit.'+value.color);
      console.log(_answ, $unit, $unit.size());
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
    tryToConquer();
    orderPlayers();
    setTimeout(hidePoppups, 7000);
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