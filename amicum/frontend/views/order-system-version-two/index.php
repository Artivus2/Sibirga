<?php
$amicumWebsocketString1 = AMICUM_CONNECT_STRING_WEBSOCKET;
$amicumWebsocketString2 = AMICUM_CONNECT_STRING_WEBSOCKET_OUTER;
$amicumWebsocketString3 = AMICUM_CONNECT_STRING_WEBSOCKET_INNER;

$this->registerJsVar('AMICUM_CONNECT_STRING_WEBSOCKET', AMICUM_CONNECT_STRING_WEBSOCKET);
$this->registerJsVar('AMICUM_CONNECT_STRING_WEBSOCKET_OUTER', AMICUM_CONNECT_STRING_WEBSOCKET_OUTER);
$this->registerJsVar('AMICUM_CONNECT_STRING_WEBSOCKET_INNER', AMICUM_CONNECT_STRING_WEBSOCKET_INNER);

$this->registerJsVar('AMICUM_DASH_BOARD_WEBSOCKET_ADDRESS', AMICUM_DASH_BOARD_WEBSOCKET_ADDRESS);
$this->registerJsVar('AMICUM_DASH_BOARD_WEBSOCKET_PORT', AMICUM_DASH_BOARD_WEBSOCKET_PORT);

$this->registerJsVar('AMICUM_DEFAULT_SHIFTS', AMICUM_DEFAULT_SHIFTS);

$mode = 'dev';

if ($mode === 'dev') {
    $this->render('devPage');
    $this->title = 'Выдача наряда';
} else {
    $this->render('prodPage');
    $this->title = 'Выдача наряда';
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

