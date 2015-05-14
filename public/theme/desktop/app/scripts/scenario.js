var firstTime = new Date().getTime()/1000;
var search_timeout = 90;

function renderPlayers() {
  $.each(GAME.users, function(index, value){
    if (value.id != GAME.user.id) {
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
  });
}

function startOrSearch() {
  GAME.game_id = _skoronagame_.game_id;
  GAME.getGame(function(){
    if (GAME.status == "wait") {
      renderPlayers();
      var now = new Date().getTime()/1000;
      if (now-firstTime<search_timeout) {
        window.setTimeout(startOrSearch, 1000);
      } else {
        alert('Игроки не найдены!')
      }
    } else if (GAME.status == "start") {
      renderPlayers();
      setTimeout(hidePoppups, 1000)
    }
    console.log(GAME.status)
  });
}