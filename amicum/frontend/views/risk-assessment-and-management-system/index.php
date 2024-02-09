<?php

use frontend\controllers\Assistant as Assistant;

$amicumWebsocketString1 = AMICUM_CONNECT_STRING_WEBSOCKET;
$amicumWebsocketString2 = AMICUM_CONNECT_STRING_WEBSOCKET_OUTER;
$amicumWebsocketString3 = AMICUM_CONNECT_STRING_WEBSOCKET_INNER;

$this->registerJsVar('AMICUM_CONNECT_STRING_WEBSOCKET', $amicumWebsocketString1);
$this->registerJsVar('AMICUM_CONNECT_STRING_WEBSOCKET_OUTER', $amicumWebsocketString2);
$this->registerJsVar('AMICUM_CONNECT_STRING_WEBSOCKET_INNER', $amicumWebsocketString3);

$this->registerJsVar('AMICUM_DEFAULT_SHIFTS', Assistant::GetCountShifts());

$mode = 'dev';

if ($mode === 'dev') {
    $this->render('devPage');
    $this->title = 'Система оценки и управления риском';
} else {
    $this->render('prodPage');
    $this->title = 'Система оценки и управления риском';
}


?>

<!--Прелодер-->
<div id="preload" class="hidden hidden-print">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>
<div id="app"></div>
