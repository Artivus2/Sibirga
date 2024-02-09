<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use frontend\assets\AppAsset;
use yii\web\View;

$this->registerCssFile('/css/media-layout.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/media-login.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/login.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>
<div class = "wrap-login">

    
        
        
        <img src="/img/Mechel.ru.svg.png" class = "img-responsive">    
        
        <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

                <?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>

                <?= $form->field($model, 'password')->passwordInput() ?>
                <br/>
                <div class="form-group">
                    <?= Html::submitButton('Вход', ['class' => 'login-button', 'name' => 'login-button']) ?>
                </div>
                <br/>
		<div class="text-mutedet">
                    Нет учетной записи ? <?= Html::a('Регистрация', ['site/signup']) ?>
                </div>
            <?php ActiveForm::end(); ?>
</div>
