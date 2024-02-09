<?php

namespace backend\controllers\sms;

use backend\controllers\Assistant;
use Exception;
use Throwable;
use Yii;
use yii\httpclient\Client;
use yii\web\Controller;
use yii\web\Response;

require_once 'PhpSerial.php';                                                                                           //Подключение библиотеки PhpSerial

class SmsSender extends Controller
{
    // actionSendMessage    - метод для тестирования отправки текстовых сообщений с браузера
    // SendMessage         - метод отправки текстового сообщения по массиву телефонных номеров

    /**
     * Функция перевода строки в формат, подходящий для отправки по СМС
     * @param string $msg исходная строка
     * @return mixed|string строка, переведённая в СМС формат
     */
    public static function encodeToSmsString($msg)
    {
        //Массив с кириллическими символами
        $cyrillic = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И',
            'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х',
            'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в',
            'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о',
            'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы',
            'ь', 'э', 'ю', 'я', '№');
        //Массив с латинскими символами
        $latin = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I',
            'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H',
            'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b',
            'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n',
            'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch',
            'y', 'y', 'y', 'e', 'yu', 'ya', 'No');

        // Замена кириллицы на латиницу
        $msg = str_replace($cyrillic, $latin, $msg);
        // Удаление скобок и их содержимого
        $msg = trim(preg_replace("/\(([^()]*+|(?R))*\)/", '', $msg));
        // Удаление лишних пробелов в начале, между словами и конце строки
        $msg = trim(preg_replace('/\s+/', ' ', $msg));

        return $msg;
    }

    /**
     * Отправка запрос на СМС сервер в Воркуте для рассылки СМС сообщений
     * @param string $message текст сообщения
     * @param array $numbers массив телефонных номеров
     * @return array
     */
    public static function actionSendSmsProxy($message, $numbers)
    {
        $status = 1;
        $count_sends = array();
        $warnings = array();
        $errors = array();

        $warnings[] = 'actionSendSmsProxy. Начало метода.';

        try {
            $client = new Client();
            $response = $client->createRequest()
                ->setMethod('POST')
                ->setUrl('http://10.36.59.202/admin/sms-send/send-message')
                ->setData(['message' => $message, 'list_phone_number' => json_encode($numbers)])
                ->send();
            if ($response->isOk) {
                if ($response->data['status'] === 1) {
                    $count_sends = $response->data['Items'];
                    $warnings[] = $response->data['warnings'];
                } else {
                    $warnings[] = $response->data['warnings'];
                    $errors[] = $response->data['errors'];
                    throw new Exception('actionSendSmsProxy. Ошибка выполнения метода');
                }
            } else {
                $errors[] = $response->data;
                throw new Exception('actionSendSmsProxy. Ошибка при отправлении запроса');
            }
        } catch (Throwable $exception) {
            $warnings[] = 'actionSendSmsProxy. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = 'actionSendSmsProxy. Конец метода.';
        return array('Items' => $count_sends, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // SendMessage - метод отправки текстового сообщения по массиву телефонных номеров
    // входные параметры:
    //      message                 - текстовое сообщение на отправку
    //      list_phone_number       - массив телефонных номеров
    // выходной массив данных:
    //      стандартный набор данных
    //      count_sends             - счетчик отправленных сообщений
    // Разработал: Файзулоев А.Э.
    // Пример использование: SmsSend::SendMessage("сообщение на отправку",['+79333002774','+79059675355']);
    public static function SendMessage($message, $list_phone_number)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $result = array();                                                                                              // Массив предупреждений
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        try {
            $warnings[] = 'SendMessage. Начал выполнять метод';
            /**
             * Проверка наличия не пустого сообщения перед отправкой
             */
            if (empty($message) || $message == '') {
                throw new Exception('SendMessage. Текстовое сообщение на отправку пустое');
            }

            /**
             * Переформатируем входное сообщение в транслит, т.к. в кириллице отправляется пустое сообщение - косяк не решен из-за консоли
             */
            $warnings[] = "SendMessage. Исходное текстовое сообщение: $message";
            $translit_message = self::encodeToSmsString($message);
            $warnings[] = "SendMessage. Текст после транслитизации: $translit_message";

            /**
             * Блок отправки сообщений по массиву телефонных номеров
             */
            $count_sends = 0;                                                                                             //счетчик отправленных сообщений
            foreach ($list_phone_number as $phone_number) {
                $warnings[] = "SendMessage. Номер телефона: $phone_number";
                //вызов утилита gnokii из консоли Linux на отправку смс
                exec('echo "' . $translit_message . '" | gnokii --sendsms ' . $phone_number . ' ', $result);
                $warnings[] = 'SendMessage. Отправил текстовое сообщение';
                $count_sends++;
                sleep(2);                                                                                       // сделана задержка для того, что бы оператор связи не записал в спам рассылку.
            }
        } catch (Throwable $exception) {
            $warnings[] = 'SendMessage. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = 'SendMessage. Закончил выполнять метод';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'count_sends' => $count_sends);

    }

    // actionSendMessage - метод для тестирования отправки текстовых сообщений с браузера
    // Разработал: Якимов М.Н.
    //пример вызова метода в браузере - http://10.36.59.202/admin/sms-sender/send-message?message=%22%D0%9F%D1%80%D0%B8%D0%B2%D0%B5%D1%82%22&list_phone_number=['+79333002774','+79059675355']
    // важно!!! так как на сервере проект старый, пример вызова будет по-старому, т.е. что в примере а не http://10.36.59.202/admin/sms/sms-send
    public static function actionSendMessage()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $result = array();                                                                                              // Массив предупреждений
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $count_sends = -1;
        try {
            $warnings[] = 'actionSendMessage. начал выполнять метод';
            $post = Assistant::GetServerMethod();
            /**
             * Проверка наличия правильности полученных полей
             */
            if (isset($post['message']) and $post['message'] != "" and
                isset($post['list_phone_number']) and $post['list_phone_number'] != ""
            ) {
                $message = $post['message'];
                $list_phone_number = json_decode($post['list_phone_number']);
            } else {
                throw new Exception('actionSendMessage. переменная message или list_phone_number не существуют');
            }

            /**
             * проверка наличия массива. Если не массив, то ошибка
             */

            if (!is_array($list_phone_number)) {
                throw new Exception('actionSendMessage. Входной набор списка номеров не массив');
            }

            /**
             * Блок вызова метода для отправки текстовых сообщений
             */
            $response = self::SendMessage($message, $list_phone_number);

            /**
             * Блок стандартных проверок
             */
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $count_sends = $response['count_sends'];

            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('actionSendMessage. Ошибка при отправке текстовых сообщений ');
            }
        } catch (Throwable $exception) {
            $warnings[] = 'actionSendMessage. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = 'actionSendMessage. Закончил выполнять метод';
        $result_main = array('Items' => $count_sends, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    /*
    public static function sendSmsMessage ($ph_num, $message)
    {
        //ТЕСТ
        echo nl2br("В функции sendSmsMessage \n");
        //Массив с кириллическими символами
        $cyrillic= array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
        //Массив с латинскими символами
        $latin = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
        $translit_message=str_replace($cyrillic,$latin,$message);                                                       //Транслитизируем исходное сообщение
        echo nl2br("Текст после транслитизации: $translit_message \n");

        //Настройка COM-порта
        $port="ttyUSB1";                                                                                                //Указываем порт к которому подключен модем
        echo nl2br("Порт : $port \n");
        echo nl2br("Конфигурирую порт \n");
        $serial = new PhpSerial;
        $serial->deviceSet("/dev/".$port);                                                                        //Имя устройства
        $serial->confBaudRate(9600);                                                                                //Скорость передачи данных
        $serial->confParity("none");                                                                               //Контроль четности
        $serial->confCharacterLength(8);                                                                             //Длина сообщения
        $serial->confStopBits(1);                                                                                 //Количество стоповых бит
        $serial->confFlowControl("none");                                                                          //Контроль потока
        $serial->deviceOpen();                                                                                          //Открытие порта
        exec("stty  -F /dev/".$port."-echo");                                                                   //Отключение "Эхо" COM-порта
        $phone_number="+".$ph_num;                                                                                      //Добавление к номеру символа "+"
        $CtrlZ = chr(26);                                                                                           //Сохранение в переменную комбинации Ctrl+Z

        //Отправка сообщения
        echo nl2br("Вызываю методы отправки сообщения \n");
        $serial->sendMessage("AT \r\n");                                                                             //Активация прослушивания команд в модеме
        $serial->sendMessage("AT+CMGF=1 \r\n");                                                                      //Установка текстового режима передачи сообщений
        $serial->sendMessage("AT+CMGS=\"$phone_number\"\r\n");                                                       //Передача номера телефона модему
        $serial->sendMessage($translit_message . $CtrlZ . "\r\n");                                                   //Отправка сообщения модему
        $serial->deviceClose();
        //Закрытие COM-порта
    }
    */
}
