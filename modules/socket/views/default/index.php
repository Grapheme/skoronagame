<?
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use app\helpers\LoaderFH;
use yii\helpers\ArrayHelper;
?>


<?=Html::input('text','send_msg','',['id'=>"send_msg"])?>
<span class="btn btn-success" id="send">Отправить</span>
<span class="btn btn-success" id="new">NEW</span>

<div id="color-box">MY COLOR</div>

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

///command ///
    function executeCommand(obj) {

        var obj = JSON.parse(obj);
        var data = obj.data;

        switch(data.action) {

            case ('sendmsg'):
                console.log(data.msg);
                break;

            case ('initgame'):
               $("#color-box").css("background",data.color);
               setCastle(data.castle);
                break;

        };
    }

//////////


    function setCastle(data) {

        for(var coord in data)
        {
            $("#"+coord+"map").css("background",data[coord]).append('*');
        }
    }

    function sendToInput(data) {
        log.html(data + '\\n' + log.val());
    }

    function send(msg) {
        conn.send(msg);
    }

    $('#send').on('click',function() {
        send(msg.val());
        msg.val('');
    });

    $('#new').on('click',function() {
        send('new:');
    });

//////////
SCRIPT;

$this->registerJs($soc);?>