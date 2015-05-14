var firstTime = new Date().getTime()/1000;
var search_timeout = 90;
function startOrSearch() {
  GAME.game_id = _skoronagame_.game_id;
  GAME.getGame(function(){
    if (GAME.status == "wait") {
      var now = new Date().getTime()/1000;
      if (now-firstTime<search_timeout) {
        window.setTimeout(startOrSearch, 1000);
      } else {
        alert('Игроки не найдены!')
      }
    }
    console.log(GAME.status)
  });
}