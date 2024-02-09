<?php

/** @var \yii\web\View $this */
/** @var string $content */

use common\widgets\Alert;
use frontend\assets\AppAsset;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\web\View;

AppAsset::register($this);
$session = Yii::$app->session;
$sess_id = session_id();

$this->registerJsFile('/js/main.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<!--<body class="d-flex flex-column h-100"> -->
<body class="main_garage">
<?php $this->beginBody() ?>

<header>
    <?php
    NavBar::begin([
        'brandLabel' => '<img src="/img/logo_20.png" class="img-logo"/>Система учета</img>', // Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
//            'class' => 'navbar navbar-expand-md navbar-dark bg-dark fixed-top container-fluid',
	      'class' => 'navbar navbar-expand-md navbar-dark bg-dark fixed-top container-fluid navbar-height',
        ],
    ]);
    $menuItems = [
        ['label' => 'Меню1', 'url' => ['/jobs']],
        ['label' => 'Меню2', 'url' => ['/repair']],
        ['label' => 'Меню3', 'url' => ['/stoptime']],
        ['label' => 'Меню4', 'url' => ['/reports']],
        ['label' => 'Меню5', 'url' => ['/garage']],
        ['label' => 'Настройки', 'url' => ['/settings']],    ];

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav me-auto mb-2 mb-md-0'],
        'items' => $menuItems,
    ]);
    if (Yii::$app->user->isGuest) {
        echo Html::tag('div',Html::a('Login',['/site/login'],['class' => ['btn btn-link login text-decoration-none']]));
    } else {
        echo Html::beginForm(['/site/logout'], 'post', ['class' => ''])
            . Html::submitButton(
                'Выход (' . Yii::$app->user->identity->fio . ')',
                ['class' => 'btn btn-link logout text-decoration-none']
            )
            . Html::endForm();
    }
    NavBar::end();
    ?>
</header>

    <div class="container-fluid">
        <?= $content ?>
    </div>


<footer class="footer">
        <div class="logo-footer"></div>
        <div class="footer-text">&copy; <?= Html::encode('Artiv systems ltd.') ?> <?= date('Y') ?></div>
<!--        <div id="footer-line" class="footer-line">Добрый день, коллеги!!! Приглашаем вас начать новый рабочий день с хорошей горячей чашечки кофе!!! Хорошего дня !!!</div> -->
	<div class="footer-text">Все права защищены</div>
	</div>
	<div class="logo-footer"</div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
