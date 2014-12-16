<?
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use app\helpers\LoaderFH;
use yii\helpers\ArrayHelper;
use app\modules\socket\models\Game;
?>

<table>

    <?foreach($settings as $setting):?>
        <tr>
            <td><?=$setting['desc']?></td>
            <td><?=$setting['val']?></td>
        </tr>
    <?endforeach;?>
</table>

