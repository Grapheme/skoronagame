idleTimer = null;
idleState = false; // состояние отсутствия
idleWait = 900*1000; // время ожидания в мс. (1/1000 секунды)
idleUrl = "/game/disconnect_user";


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

function infoWhoTurnText(text, show) {
    show = show || true
    $('.infowindow.who-turn .holder').html(text);
    if (show) {
        $('.infowindow.who-turn').show();
    } else {
        $('.infowindow.who-turn').hide();
    }
}

function idleController() {
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
            sexyAlert(text, 99999, function(){
                sexyAlert(text, 99999, function(){
                    sexyAlert(text, 99999, function(){
                        sexyAlert(text, 99999, function(){
                            sexyAlert(text, 99999, function(){
                                sexyAlert(text, 99999, function(){
                                    sexyAlert(text, 99999, function(){
                                    
                                    });
                                });
                            });
                        });
                    });
                });
            });
        }, 100);
        sexyAlert(text, 99999);
        playerDisconect();
        idleState = true; 
      }, idleWait);
    });
}
//$("body").trigger("mousemove"); // сгенерируем ложное событие, для запуска скрипта

var bg_width = $('#map, #tutorial').width();

function scale() {
  var doc_width = $(window).width();
  $('#map').transition({ scale: doc_width/bg_width });
  $('#user-list').transition({ scale: doc_width/bg_width });
  $('.infowindow.tour1, .infowindow.tour2, .infowindow.who-turn .holder, #tutorial').transition({ scale: doc_width/bg_width });
}

$(window).resize(function (){
  scale();
});

$(window).load(function() {
  bg_width = $('#map, #tutorial').width();
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

function sexyAlert(text, timeOut, callback, width) {
  timeOut = timeOut || 3;
  callback = callback || function(){};
  width = width || 320;
  
  if ($('#sexy-alert .note').html()!=text && !$('.popup-wrapper').is(':visible')) {
    $('#sexy-alert .note').width(width);
    showPoppups();
    $('#sexy-alert .note').html(text);
    openFrame('sexy-alert');
    
    setTimeout(function(){
      hidePoppups();
      callback();
      $('#secy-alert').removeClass('with-tabs');
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

$.validator.addMethod("passRegex", function(value, element) {
    return this.optional(element) || /^[a-z0-9\-]+$/i.test(value);
}, "Username must contain only letters, numbers, or dashes.");

$('#new-password form').validate({
  rules: {
    password :{
        required: true,
        minlength: 6,
        passRegex: true
    },
    ver_password :{
        required: true,
        equalTo: "#password"
    }
  },
  messages: {
    password :{
        required: 'Обязательное поле',
        minlength: 'Должно быть не мение 6 символов',
        passRegex: 'Используйте латинский алфавит<br> и цифры'
    },
    ver_password :{
        required: 'Обязательное поле',
        equalTo: 'Пароли не совпадают',
    }
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

if ($('#tutorial').length > 0) {
    var c = $('#tutorial .tip').size();
    
    setTimeout(function(){
        $('#tutorial .screen').fadeIn(300, function(){
            for (i=1; i<=c; i++) {
                setTimeout(function(i){
                    $('#tutorial .help-'+i).addClass('active');
                }, 1000*i, i);
            } 
        });
    }, 1000);
}
