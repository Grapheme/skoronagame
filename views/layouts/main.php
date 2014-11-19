<?php
use yii\helpers\Html;
use app\assets\AppAsset;
use app\helpers\LoaderFH;

/* @var $this \yii\web\View */
/* @var $content string */

//AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">

    <title><?=Html::encode($this->title)?></title>

    <?php $this->head() ?>
</head>
<body>

<?php $this->beginBody() ?>

            <?= $content ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
