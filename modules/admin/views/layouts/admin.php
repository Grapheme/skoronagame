<?php
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

/* @var $this \yii\web\View */
/* @var $content string */

//AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>

<?php $this->beginBody() ?>
    <div class="wrap">
        <?php
            NavBar::begin([
                'brandLabel' => 'Сибирская корона',
                'brandUrl' => Yii::$app->homeUrl,
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                    'style'=>'position:relative;'
                ],
            ]);
        echo Nav::widget([
            'options' => ['class' => 'navbar-nav navbar-right'],
            'items' => [
                Yii::$app->user->can('admin') ?
                    ['label' => 'SignUp', 'url' => ['/user/default/signup']]:'',

                !Yii::$app->user->isGuest ?
                ['label' => 'Users', 'url' => ['/admin/default/users']] : '',

                !Yii::$app->user->isGuest ?
                ['label' => 'Questions', 'url' => ['/admin/default/index']] : '',

                Yii::$app->user->can('admin') ?
                    ['label' => 'SERVER', 'url' => ['/socket/default/server']]:'',

                Yii::$app->user->isGuest ?
                    ['label' => 'Login', 'url' => ['/user/default/login']]:
                    ['label' => 'Logout (' .Yii::$app->user->identity->email . ')',
                        'url' => ['/user/default/logout'],
                        'linkOptions' => ['data-method' => 'post']],
            ],
        ]);
            NavBar::end();
        ?>

        <div class="container">
            <?= $content ?>
        </div>
    </div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
