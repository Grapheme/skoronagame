<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>
<div class="site-login">

    <h1>Общий рейтинг</h1>
    <ul>
    <?
    $i=0;
    foreach($top as $player):?>
        <li><?=++$i?>  <?=$player['nickname']?>   <?=$player['winns']?>   <?=$player['points']?></li>
    <?endforeach;?>
    </ul>

    <?if($place):?>
        Мое место <?=$place?> <?=$identity->nickname?>  <?=$identity->winns?>   <?=$identity->points?>
    <?endif?>
</div>

<div class="site-login">

    <h1>Рейтинг за месяц</h1>
    <ul>
    <?
    $i=0;
    foreach($m_top as $player):?>
        <li><?=++$i?>  <?=$player['nickname']?>   <?=$player['m_winns']?>   <?=$player['m_points']?></li>
    <?endforeach;?>
    </ul>

    <?if($m_place):?>
        Мое место <?=$m_place?> <?=$identity->nickname?>  <?=$identity->m_winns?>   <?=$identity->m_points?>
    <?endif?>
</div>
