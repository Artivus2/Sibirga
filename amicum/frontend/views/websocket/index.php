<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;
use yii\web\View;
$this->registerCssFile('/css/websocket.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/websocket.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>
<div id="form">
    <fieldset>
        <input id="firstKey" type="text" class="sending-data form-control" placeholder="Ключ" value="ActionType">
        <span> : </span>
        <select name="actionType" id="actionType">
            <option value="publish">publish</option>
            <option value="subscribe" selected>subscribe</option>
        </select>

        <input id="secondKey" type="text" class="sending-data form-control" placeholder="Ключ" value="ClientType">
        <span> : </span>

        <select name="clientType" id="clientType">
            <option value="javaFront">Android</option>
            <option value="unityFront">unityFront</option>
            <option value="webFront" selected>webFront</option>
        </select>

        <input id="thirdKey" type="text" class="sending-data form-control" placeholder="Ключ" value="SubscribeList">
        <span> : </span>
        <input id="subscribeListValue" type="text" class="sending-data form-control" placeholder="Значение">

    </fieldset>

    <button class="btn btn-primary sendRequestBtn">Отправить запрос</button>
</div>
<div id="output">
    <div class="header">Ответ с сервера</div>
    <div class="output-body"></div>
</div>
