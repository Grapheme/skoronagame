<?
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use app\helpers\LoaderFH;
use yii\helpers\ArrayHelper;
?>
<?
$poz = [0,1,2,1,2,0,2,0,1];

$arr = ['a','b','c'];

print_r(array_intersect_key($arr,$poz));
?>
<?=Html::input('text','send_msg','',['id'=>"send_msg"])?>
<span class="btn btn-success" id="send">Отправить</span>
<span class="btn btn-success" id="new">NEW</span>
<span class="btn btn-success" id="quiz">QUIZ ANS</span>
<span class="btn btn-success" id="quest">QUEST ANS</span>

<div id="sender"></div>
<div id="color-box">MY COLOR</div>
<div><span id="points">0</span> POINTS</div>

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

<br/>
<?=Html::textarea('textarea','',['id'=>"log_input",'rows'=>30,'style'=>"width:600px;"])?>

    <style>
        #map td {
            width: 35px;
            height: 35px;
        }

        #color-box {
            width: 50px;
        }
    </style>
<?
$soc = <<<SCRIPT


    var msg = $('#send_msg');
    var log = $('#log_input');

    var conn = null;

/// server///
    function wsStart() {

        conn = new WebSocket('ws://localhost:8080');

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

                $("#sender").text(data.msg);
                console.log(data.msg);
                break;

            case ('initgame'):

               $("#color-box").css("background",data.color);
               $("#points").text(data.points);
               my_color = data.color;
               SKGame.setCastle(data.castle);
                break;

            case ('segmentmap'):

                gameStatus(data.color, 'Выберите территорию', 'Идет выбор территории');
                SKGame.setMap(data.map);
                $("#points").text(data.points);
                break;

            case ('status'):

                SKGame.setMap(data.map);
                $("#points").text(data.points);
                break;

            case ('quiz'):

               $("#sender").text('Ответьте на вопрос');
               $("#question").text(data.question);
                break;

            case ('quest'):

                $("#sender").text('Ответьте на вопрос');
                $("#question").text(data.question);
                SKGame.setVariants(data.variants);
                SKGame.selectRegion(data.region);
                break;

            case ('quest_passiv'):

               $("#sender").text('Нападение на территорию соперника');
               SKGame.selectRegion(data.region);
                break;

            case ('conquest'):

               conquest = true;
               gameStatus(data.color, 'Выберите территорию для нападения', 'Соперник нападает');
               SKGame.setMapAttack(data.map);
                break;

            case ('endgame'):
                SKGame.resetGame();
                alert ('Конец игры!');
                break;

        };
    }

//////////

SKGame = {

        setMap: function (data) {

            for(var coord in data)
                $("#"+coord+"map").css({"background":data[coord], "opacity":"1"});
        },

        setCastle: function (data) {

            for(var coord in data)
                $("#"+coord+"map").css("background",data[coord]).append('*');
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
                $("#question").append(data[variant] + ' ('+ variant +')<br/>');
        },

         resetGame: function () {

            msg.val('');
            $("#points").text('');
            SKGame.resetMap;
            $("#color-box").css("background",'none');
         },

}

    function gameStatus(color, active, passive) {

        var text;
        if(my_color == color)
            text = active;
        else
            text = passive;

        $("#sender").text(text);
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
        msg.val('');
    });

     $('#quest').on('click',function() {
        send({'action':'quest','answer':msg.val()});
        msg.val('');
    });

    $('#new').on('click',function() {
        send({'action':'new'});
    });

    $('#map td').on('click',function() {
        var id_map = parseInt(this.id);
        var action;

        if(conquest === true) action = 'conquest'; else action = 'segment';

        send({'action':action,'map':id_map});
    });

///auto////

    $(window).on('load',function() {
       setTimeout(send({'action':'new'}), 5000);
    });



//////////
SCRIPT;

$this->registerJs($soc);?>