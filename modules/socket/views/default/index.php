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
<br/>
<?=Html::textarea('textarea','',['id'=>"log_input",'rows'=>30,'style'=>"width:600px;"])?>

<?
$soc = <<<SCRIPT


    var msg = $('#send_msg');
    var log = $('#log_input');

    var conn = null;

    function wsStart() {

        conn = new WebSocket('ws://localhost:8080');

        conn.onopen = function(e) {
            sendToInput("Connection established!");
            console.log("Connection established!");
        };

        conn.onmessage = function(e) {
            sendToInput(e.data);
        };

        conn.onclose = function(e) {
            sendToInput("Connection closed, reconnect...");
            console.log("Connection closed, reconnect...");
            setTimeout(wsStart, 5000);
        };
    }

    wsStart();

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


SCRIPT;

$this->registerJs($soc);?>