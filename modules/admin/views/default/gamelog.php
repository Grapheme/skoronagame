<?
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use app\helpers\LoaderFH;
use yii\helpers\ArrayHelper;
use app\modules\socket\models\Game;
?>

<?if(isset($game['game'][0])):?>
    <table>

        <tr>
            <td>Начало игры</td>
            <td><?=$game['game'][0]['date_start']?></td>
        </tr>

        <tr>
            <td>Конец игры</td>
            <td><?=$game['game'][0]['date_stop']?></td>
        </tr>

        <tr>
            <td>Идентификатор игры</td>
            <td><?=$game['game'][0]['game']?></td>
        </tr>

        <tr style="font-weight: bold">
            <td width="200px">Никнейм</td>
            <td width="50px">Цвет</td>
            <td width="200px">Тип</td>
            <td width="150px">Очки за игру</td>
            <td width="50px">Место</td>
        </tr>

        <?foreach($game['game'] as $player):?>
            <tr>
                <td><?=$player['nickname']?></td>
                <td><?=$player['color']?></td>
                <td><?=($player['type']== Game::USER)? 'User':
                        (empty($player['player'])? 'BOT': 'Вышел и был заменен ботом')?></td>
                <td><?=$player['points']?></td>
                <td><?=$player['place']?></td>
            </tr>
        <?endforeach;?>
    </table>
<?endif;?>

<br/>
<table>

    <tr style="font-weight: bold">
        <td width="50px">#</td>
        <td width="200px">Пользователь</td>
        <td width="300px">Ответ</td>
        <td width="150px">Время</td>
        <td width="100px">Баллы</td>
        <td width="100px">Тип</td>
    </tr>

    <?foreach($levels as $level):?>
        <tr style="background: whitesmoke;">

            <td><?=$level['lvl']?></td>
            <td><?=$level['quest']?></td>
            <?
                if(empty($level['variants'])) {
                    $type = 'quiz';
                    $answer = $level['answer'];
                } else {
                    $type = 'quest';
                    $ans = json_decode($level['variants']);
                    $ans[$level['answer']] = '['.$ans[$level['answer']].']';
                    $answer = implode(', ', $ans);
                }
            ?>
            <td><?=$answer?></td>
            <td>Территория: <?=$level['region']?></td>
            <td colspan="2"><?
                switch($level['type']) {
                    case (Game::SEGMENT):
                        print('раздел территории');
                        break;
                    case (Game::LCLT_GRAB):
                        print('территория захвачена');
                        break;
                    case (Game::LCLT_DEF):
                        print('территория защищена');
                        break;
                    case (Game::CSTL_GRAB):
                        print('замок захвачен');
                        break;
                    case (Game::CSTL_DEF):
                        print('замок защищен');
                        break;
                    case (Game::CSTL_GRAB_LF):
                        print('замок потерял жизнь');
                        break;
                    case (Game::REPEAT):
                        print('вопрос переигран');
                        break;
                }
                    ?>

            </td>

        </tr>

        <?
        $pl = json_decode($level['pl_answer'],true);
        foreach($pl as $color => $player):?>
        <tr>
            <td></td>
            <td><?=$color?></td>
            <td><?=($type == 'quiz')?$player['answer']:$ans[$player['answer']]?></td>
            <td><?=$player['time']?> сек.</td>
            <td><?=$player['points']?></td>
            <td><?=isset($player['type'])?$player['type']:''?></td>
        </tr>
        <?endforeach;?>

        <tr><td style="border-top: 2px rgba(128, 128, 128, 0.14) groove;height: 10px;"colspan="6"></td></tr>
    <?endforeach;?>

</table>
