<?
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use app\helpers\LoaderFH;
use yii\helpers\ArrayHelper;

use app\models\Games;

use yii\db\Expression;
?>
    <!--<span class="btn btn-success" id="send">Отправить</span>-->
<?=Html::input('text','send_msg','',['id'=>"send_msg"])?>
    <span class="btn btn-success" id="quiz">QUIZ ANS</span>
    <span class="btn btn-success" id="new">NEW</span>
    <!--<span class="btn btn-success" id="quest">QUEST ANS</span>-->

<span id="timer" >0</span>


<?//
//print_r(Yii::$app->getSession()->get('id'));
//print_r($_COOKIE['PHPSESSID']);
//print_r(Yii::$app->session->getId());
//
//$t =  '4f6e01e6542a95ea55d67c696420624430d457ee9e7dc76383496a797afdd54as%3A17%3A%22%5B13%2Cnull%2C2592000%5D%22%3B';


$sessid = Yii::$app->getSession()->getId();
phpinfo();
Yii::$app->session->close();
$cmd = PHP_BINDIR . '/php '.Yii::$app->basePath.'/yii socket/sid '.$sessid;
$usr = shell_exec($cmd);
Yii::$app->session->open();
echo 'результат';

print_r('<br/>');

print_r($usr);
//?>

<div id="sender"></div>

<table style="width: 400px;" >
    <tr>
        <td><div id="color-box"><?=Yii::$app->user->identity['nickname']?></div></td>
        <td><div id="nickname1"></div></td>
        <td><div id="nickname2"></div></td>
    </tr>
    <tr>
        <td><span id="points">0</span></td>
        <td><span id="points1"></span></td>
        <td><span id="points2"></span></td>
    </tr>
</table>

<div id="question"></div>

<table id="map">
    <tr>
        <td id="1map">1</td>
        <td id="2map">2</td>
        <td id="3map">3</td>
        <td id="4map">4</td>
        <td id="5map">5</td>
    </tr>
    <tr>
        <td id="6map">6</td>
        <td id="7map">7</td>
        <td id="8map">8</td>
        <td id="9map">9</td>
        <td id="10map">10</td>
    </tr>
    <tr>
        <td id="11map">11</td>
        <td id="12map">12</td>
        <td id="13map">13</td>
        <td id="14map">14</td>
        <td id="15map">15</td>
    </tr>
</table>

<div id="rezult"></div>



<br/>
<?=Html::textarea('textarea','',['id'=>"log_input",'rows'=>30,'style'=>"width:600px;"])?>

    <style>
        #map td {
            width: 50px;
            height: 50px;
        }

        #color-box {
            width: 50px;
        }
    </style>
<?
$soc = <<<SCRIPT


    var msg = $('#send_msg');
    var log = $('#log_input');
    var timer = $('#timer');
    var question = $("#question");
    var sender = $("#sender");

    var colorbox = $("#color-box");
    var points = $("#points");

    var nickname1 = $("#nickname1");
    var nickname2 = $("#nickname2");

    var points1 = $("#points1");
    var points2 = $("#points2");

    var rezult = $("#rezult");

    var conn = null;

/// server///
    function wsStart() {

        conn = new WebSocket('ws://skoronagame:8080');

        conn.onopen = function(e) {
            sendToInput("Connection established!");
            console.log("Connection established!");
        };

        conn.onmessage = function(e) {
            sendToInput(e.data);
            executeCommand(e.data);
        };

        conn.onclose = function(e) {
            sendToInput("Connection closed, reconnect...");
            console.log("Connection closed, reconnect...");
            setTimeout(wsStart, 5000);
        };

    }

    wsStart();

    var my_color;
    var conquest = false;

///command ///
    function executeCommand(obj) {

        var obj = JSON.parse(obj);
        var data = obj.data;


        switch(data.action) {

            case ('sendmsg'):

                sender.text(data.msg);
                console.log(data.msg);
                break;

            case ('initgame'):

               colorbox.css("background",data.color);
               SKGame.setPoint(data.points);
               my_color = data.color;
               SKGame.setCastle(data.castle);
                break;

            case ('segmentmap'):

                gameStatus(data.color, 'Выберите территорию', 'Идет выбор территории');
                SKGame.setMap(data.map);
                SKGame.setPoint(data.points);
                break;

            case ('status'):

                question.text('');
                SKGame.setMap(data.map);
//                SKGame.setPoint(data.points);
                SKGame.setPlayers(data.players);
                break;

            case ('quiz'):

                sender.text('Ответьте на вопрос');
                question.text(data.question);
                SKGame.startTimer(data.time);
                break;

            case ('quest'):

                sender.text('Ответьте на вопрос');
                question.text(data.question);
                SKGame.setVariants(data.variants);
                SKGame.selectRegion(data.region);
                SKGame.startTimer(data.time);
                break;

            case ('quest_passiv'):

                question.text('');
                sender.text('Нападение на территорию соперника');
                SKGame.selectRegion(data.region);
                break;

            case ('conquest'):

                question.text('');
                conquest = true;
                gameStatus(data.color, 'Выберите территорию для нападения', 'Соперник нападает');
                SKGame.setMapAttack(data.map);
                SKGame.startTimer(data.time);
                break;

            case ('endgame'):

                question.text('');
                SKGame.resetGame();
                debugger;
                SKGame.setRezult(data.rezult);
                break;

        };
    }

//////////

window.SKGame = {
        cfg: {
            time    :   10,
            timer   :   10,
            players :   null,
        },

        setMap: function (data) {

            for(var coord in data)
                $("#"+coord+"map").css({"background":data[coord], "opacity":"1"});
        },

        setPoint: function (point) {

            points.text(point);
        },

        setCastle: function (data) {

            for(var coord in data)
                $("#"+coord+"map").css("background",data[coord]).append('*');
        },

        setPlayers: function (data) {

            SKGame.cfg.players = data;

            var i = 1;
            for(var player in SKGame.cfg.players) {

                if(player == my_color) {
                    SKGame.setPoint(SKGame.cfg.players[player]['points'])
                } else {

                    var pl = eval('points'+i);
                    pl.text(SKGame.cfg.players[player]['points']);

                    var pl = eval('nickname'+i);
                    pl.text(SKGame.cfg.players[player]['nickname']);
                    pl.css('background',player);

                    var type = SKGame.cfg.players[player]['type'];

                    if(type == 'off')
                        pl.css('opacity','0.5');

                    i++;
                }

            }
        },

        setMapAttack: function (data) {

            for(var coord in data)
                $("#"+data[coord]+"map").css("opacity","0.5");
        },

        resetMap: function() {

            var i = 1;

            while(i < 16) {
                $("#"+i+"map").css({"background":'none', "opacity":"1"}).text(i);
                i++;
            }
        },

        selectRegion: function (data) {

            $("#map td").css("opacity","1");
            $("#"+data+"map").css("opacity","0.5");
        },

        setVariants: function(data) {

            data = JSON.parse(data);

            for(var variant in data)
                question.append('<br/><span class="variant" data-var='+variant+'>' + data[variant] + '</span>');
        },

        setRezult: function(data) {

            var rez='';
            var i = 1
            while (i < 4) {
                for(var player in data[i]) {

                    nickname = SKGame.cfg.players[data[i][player].color]['nickname'];
                    rez+= nickname +' '+ data[i][player].points + ' +' + data[i][player].raiting + '<br/>';
                    i++;
                }

            }

            rezult.text(rez);
        },

         resetGame: function () {

            msg.val('');
            points.text('');
            SKGame.resetMap();
            colorbox.css('background','none');

            nickname1.css('background','none').text();
            nickname2.css('background','none').text();

            points1.text();
            points2.text();
         },

        startTimer: function (time) {

            SKGame.cfg.time  = time;
            timer.text(time);

            SKGame.cfg.timer = setInterval(SKGame.tickTimer , 1000);
        },

        stopTimer: function () {

                timer.text('');
                clearInterval(SKGame.cfg.timer);
        },

        tickTimer: function () {

            SKGame.cfg.time  = SKGame.cfg.time - 1;

            if(SKGame.cfg.time <= 0) {
                SKGame.stopTimer();
            } else
                timer.text(SKGame.cfg.time);
        },

}

    function gameStatus(color, active, passive) {

        var text;
        if(my_color == color)
            text = active;
        else
            text = passive;

        sender.text(text);
    }

    function sendToInput(data) {
        log.html(data + '\\n' + log.val());
    }

    function send(msg) {
        msg = JSON.stringify(msg);
        conn.send(msg);
    }

    $('#send').on('click',function() {
        send({'action':msg.val()});
        msg.val('');
    });

     $('#quiz').on('click',function() {
        send({'action':'quiz','answer':msg.val()});
        SKGame.stopTimer();
        question.html();
        msg.val('');
    });

//     $('#quest').on('click',function() {
//        send({'action':'quest','answer':msg.val()});
//        SKGame.stopTimer();
//        msg.val('');
//    });

    $('#question').on('click','.variant',function() {
        send({'action':'quest','answer':this.attributes['data-var'].value});
        SKGame.stopTimer();
        question.html();
        msg.val('');
    });

    $('#new').on('click',function() {
        send({'action':'new'});
    });

    $('#map td').on('click',function() {
        var id_map = parseInt(this.id);
        var action;

        if(conquest === true) {
            action = 'conquest';
            SKGame.stopTimer();
        } else
            action = 'segment';

        send({'action':action,'map':id_map});
    });

///auto////

    $(window).on('load',function() {
       setTimeout(send({'action':'new'}), 5000);
    });

//////////
SCRIPT;

$this->registerJs($soc);?>