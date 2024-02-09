<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\AppAsset;

$this->registerCssFile('/css/admin.css', ['depends' => [AppAsset::className()]]);


?>
<div class ="middle-panel">
<?php
$form = ActiveForm::begin([
'id' => 'login-form',
'options' => ['class' => 'form-horizontal'],
]) ?>
    <?= $form->field($model, 'username') ?>
    <?= $form->field($model, 'login') ?>
    <?= $form->field($model, 'fio') ?>
    <?= $form->field($model, 'status') ?>
    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
<?php ActiveForm::end() ?>
    
    </div>