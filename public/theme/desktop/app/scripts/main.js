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

var bg_width = $('#map').width();

function scale() {
  var doc_width = $(window).width();
  $('#map').transition({ scale: doc_width/bg_width });
  $('#user-list').transition({ scale: doc_width/bg_width });
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
    $('.infowindow-holder .infowindow-small').text(_points);
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

function hidePoppups() {
  $('.popup-wrapper').slideUp();
}

function showPoppups() {
  $('.popup-wrapper').slideDown();
}

sendForm = function(form) {
  //alert('!!!')
  //$(form).submit();
  /*if ($(form).is('.noajax')) {
    $(form).submit();
  }*/

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