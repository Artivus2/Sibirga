<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\SignupForm $model */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Регистрация';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="wrap-login">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <p>Заполните поля для регистрации: </p>

    
        <div class="form-group">
            <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>

                <?= $form->field($model, 'username')->textInput(['autofocus' => true, 'class' => 'fields']) ?>
                
                <?= $form->field($model, 'fio')->textInput(['autofocus' => true,'class' => 'fields']) ?>

                <?= $form->field($model, 'email')->textInput(['autofocus' => true,'class' => 'fields']) ?>

                <?= $form->field($model, 'password')->passwordInput(['autofocus' => true,'class' => 'fields']) ?>

                
                    <?= Html::submitButton('Регистрация', ['class' => 'register-button', 'name' => 'signup-button']) ?>
                </div>

            <?php ActiveForm::end(); ?>
    
    
</div>
