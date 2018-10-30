<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\widgets\Alert;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;
use app\models\BtUser;
$role=Yii::$app->params['urole'];
AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body >
    
<?php $this->beginBody() ?>


<div id="app-init-container" class="wrap">
    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => [
            ['label'=>'Пользователи','visible'=>in_array($role, [BtUser::ROLE_ADMIN,BtUser::ROLE_SADMIN]),'items'=>[
                ['label'=>'Список Админов','url'=>['bt/users','role'=>BtUser::ROLE_ADMIN],'visible'=>$role==BtUser::ROLE_SADMIN],
                ['label'=>'Список Владельцев','url'=>['bt/users','role'=>BtUser::ROLE_OWNER]],
                ['label'=>'Добавить нового','url'=>['bt/user-edit']],
            ]],
            //['label' => 'Login', 'url' => ['/site/login'],'visible'=>Yii::$app->user->isGuest],
            ['label'=>'Logout (' .(Yii::$app->user->isGuest?'':Yii::$app->user->identity->username) . ')','url'=>['/bt/logout'],'visible'=>!Yii::$app->user->isGuest],
        ],
    ]);
    NavBar::end();
    ?>

    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; My Company <?= date('Y') ?></p>

        <p class="pull-right"><?= Yii::powered() ?></p>
    </div>
</footer>

<?php $this->endBody() ?>

</body>
</html>
<?php $this->endPage() ?>
