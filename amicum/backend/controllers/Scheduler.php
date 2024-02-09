<?php
/**
 * Created by PhpStorm.
 * User: sircevalex
 * Date: 13.11.2018
 * Time: 10:44
 */

namespace backend\controllers;

use frontend\models\Queue;
use Yii;
use yii\web\Controller;
use yii\web\Response;

// TODO Продумать алгоритм перемещения скрипта из начала очереди в конец при его выполнении из метода perform

// TODO Продумать возможность реализации разного интервала выполнения скриптов

//TODO Продумать принцип функции удаления скриптов из очереди
// (полюбому нужно для скриптов поставленных на бесконечное выполнение)

// TODO Реализовать метод получения задачи из очереди.

// TODO Убрать логику выполнения задач из методов класса
/**
 * Для работы планировщика на сервере должен быть настроен cron
 * * * * * * /usr/bin/php /var/www/html/myrepo/yii scheduler/run-task-queue > /dev/null 2>&1
 *
 * Class Scheduler
 * @package app\controllers
 */
class Scheduler extends Controller
{
    /**
     * Метод для занесения в очередь нового элемента.
     * Структура $item:
     *  0 элемент, это название метода, например:
     *  app\controllers\CacheControl::PrintCache
     *         ^~~~~~Пространство имен указывать обязательно!
     * Остальные элементы, это аргументы, передаваемые в метод.
     * Не передавайте аргументы типа объектов класса! Поведение метода в данном
     * случае непредсказуемо.
     *
     * Примеры:
     * Scheduler::enqueue(array("app\controllers\SomeController::yourMethod"));
     * Добавить в очередь метод yourMethod контроллера SomeController без
     * параметров.
     * Скрипт выполнится 1 раз и удалится из очереди.
     *
     * Scheduler::enqueue(array("app\controllers\CacheControl::PrintCache", 'test2'), 5);
     * Добавить в очередь метод PrintCache контроллера CacheControl с параметром
     * test2.
     * Скрипт выполнится 5 раз и удалится из очереди.
     *
     * $task = array("app\controllers\StrataJobController::saveSelectedSensorParameterDB",
     *               '543',
     *               '12:04',
     *               '3',
     *               '1'
     * );
     * Scheduler::enqueue($task, 0);
     * Добавить в очередь метод saveSelectedSensorParameterDB контроллера
     * StrataJobController с параметрами '543', '12:04', '3', '1'.
     * Скрипт будет выполняться бесконечно.
     *
     * @param array $item           -   метод, заносимый в очередь планировщика
     * @param int $repeat_count     -   количество выполнений скрипта
     */
    public static function enqueue(array $item, $repeat_count = 1)
    {
        $new_task = new Queue();                                                // Создание экземпляра модели Queue
        $new_task->queue_func = json_encode($item);                             // Записываем метод и его параметры в JSON формате
        $new_task->queue_time_alive = (string)$repeat_count;                    // Записываем количество повторов выполнения метода
        $new_task->queue_parent = debug_backtrace()[1]['function'];             // Записываем функцию, в которой произведено внесение метода в очередь
        $new_task->save();                                                      // Сохраняем запись в базу данных
    }

    /**
     * Метод для выполнения задачи и удаления её из очереди.
     *
     * Пример:
     * $task = array("app\controllers\StrataJobController::saveSelectedSensorParameterDB",
     *               '543',
     *               '12:04',
     *               '3',
     *               '1'
     * );
     * Scheduler::enqueue($task);
     * Scheduler::perform();
     * Добавить метод в очередь и вызвать его выполнение.
     *
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function perform()
    {
        $task = Queue::find()->limit(1)->one();                                 // Выборка задачи из очереди в базе данных
        if ($task) {                                                            // Если задача получена из базы данных
            $method = json_decode($task->queue_func, true);                     // Десериализуем строку, содержащую имя метода и список его параметров

            $task_settings_count = count($method);                              // Подсчет аргументов задачи (кол-во аргументов = имя метода + параметры метода)

            $method_name = $method[0];                                          // Сохраняем для удобства имя метода

            // Проверка на существование функции
            if (!is_callable($method_name)) {                                   // Если метод не является вызываемым, то есть такого метода не существует
                $task->delete();                                                // Удаляем задачу из очереди
                return false;                                                   // Возвращаем false
            }

            // Проверка на корректное количество необходимых аргументов
            $r = new \ReflectionMethod($method_name);                           // Создаем экземпляр рефлектора для метода
            if ($r->getNumberOfRequiredParameters() > $task_settings_count - 1) {   // Если не было передано необходимое количество обязательных аргументов
                $task->delete();                                                // Удаляем задачу из очереди
                return false;                                                   // Возвращаем false
            }

            // TODO обработка случая, когда функция выбрасывает исключение
            // Заполнение массива аргументов метода
            $method_parameters = array();                                       // Объявляем массив для хранения аргументов метода
            for ($i = 1; $i < $task_settings_count; $i++) {                     // В цикле для каждого аргумента метода
                $method_parameters[] = $method[$i];                             // Сохраняем в массив аргумент метода
            }
            call_user_func_array($method_name, $method_parameters);             // Вызываем метод с требуемыми аргументами

            $task->queue_repeat_value++;                                        // Увеличиваем счетчик количества выполнений задачи
            
            // Проверка на необходимость удаления задачи из списка
            if ($task->queue_time_alive == $task->queue_repeat_value) {         // Если количество выполнений задачи равно её максимальному количеству повторений
                $task->delete();                                                // Удаляем задачу из очереди
            } else {                                                            // Иначе
                $task->save();                                                  // Сохраняем задачу в базе данных с обновленным счетчиком выполнений
            }
            return true;                                                        // Возвращаем true
        }
        return false;                                                           // Возвращаем false
    }

    //TODO Проверить работу механизма блокировки, если потребуется, то задать
    // время жизни блокировки (открытия файла)
    /**
     * Метод для выполнения всех задач и очищения очереди.
     * @param bool $lock Указывает, нужно ли использовать блокировку (проверка запущен ли уже этот метод)
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @deprecated Не рекомендуется к использованию, может замедлять работу
     * очереди и вызывать коллизии.
     */
    public static function performAll($lock = false)
    {
        $fh='';                                                                 //объявляем переменную для работы с файлом блокировки
        if ($lock) {                                                            //внешний параметр для запуска принудительного выполнения очереди - нужно для остановки очереди от крона
            $fh = fopen('queue.lock', 'ab+');                                   //создаем или открываем файл блокировки запуска очереди
            if (flock($fh, LOCK_EX)) {                                          //ожидаем блокировки файла и проверяем его блокировку на запись
                ftruncate($fh, 0);                                              //очищаем файл перед записью
                fwrite($fh, date('Y-m-d H:i:s'));                               //записываем текущую дату, как подтверждение блокировки из вне при принудительном запуске вычисления очереди
            } else {
                exit('Already running');
            }
        }

        // Выборка задач из очереди
        $tasks = Queue::find()->limit(2000)->all();

        foreach ($tasks as $task) {
            if ($task) {
                $method = json_decode($task->queue_func, true);

                // Подсчет аргументов задачи (кол-во аргументов = имя метода + параметры метода)
                $task_settings_count = count($method);

                // Проверка на наличие параметров у выполняемой функции. Если
                // $task_settings_count == 1, то метод вызывается без параметров
                // TODO обработка случая, когда функцию выполнить не удалось (нужны какие-нибудь логи)
                if ($task_settings_count == 1) {
                    $method_name = $method[0];
                    //call_user_func($method_name);
                    $method_name();
                } else {
                    $method_name = $method[0];
                    $method_parameters = array();
                    for ($i = 1; $i < $task_settings_count; $i++) {
                        $method_parameters[] = $method[$i];
                    }
                    call_user_func_array($method_name, $method_parameters);
                }

                // Увеличение счетчика количества выполнений
                $task->queue_repeat_value++;

                // Проверка на необходимость удаления задачи из списка
                if ($task->queue_time_alive == $task->queue_repeat_value) {
                    $task->delete();
                } else {
                    $task->save();
                }
            }
        }

        if ($lock) {                                                //если файл блокировки был использован
            ftruncate($fh, 0);                                      //очищаем файл перед записью
            flock($fh, LOCK_UN);
            fclose($fh);                                            //закрываем файл и таким образом снимаем блокировку
        }
    }

    /**
     * Метод для проверки наличия задач в списке
     * @return bool
     */
    public static function isEmpty()
    {
        $task = Queue::find()->limit(1)->one();                                 // Выборка задачи из очереди в базе данных
        return $task ? true : false;                                            // Вернуть true, если задача найдена, иначе false
    }

    /**
     * Для дебага
     * Метод для вывода информации по всем задачам в списке
     */
    public static function debugPrintTasksInfoJson()
    {
        $tasks_info = array();                                                  // Обявляем массив для хранения информации по задачам

        if (!self::isEmpty()) {                                                 // Если очередь не пуста
            $tasks = Queue::find()->all();                                      // Выборка всех задач из очереди

            foreach ($tasks as $task) {                                         // Для каждой задачи из списка задач
                $method = json_decode($task->queue_func, true);                 // Десериализация имени и параметров метода
                $method_name = $method[0];                                      // Сохранение имени метода для удобства
                $method_parameters = array();                                   // Объявляем массив для хранения аргументов задачи

                $task_settings_count = count($method);                          // Подсчет аргументов задачи (кол-во аргументов = имя метода + параметры метода)
                if ($task_settings_count > 1) {                                 // Если кроме имени метода переданы аргументы
                    for ($i = 1; $i < $task_settings_count; $i++) {             // В цикле по количеству аргументов
                        $method_parameters[] = $method[$i];                     // Добавляем в массив аргументы метода
                    }
                }

                // Генерирование структуры задачи для вывода
                $data_task['Id'] = $task->queue_id;
                $data_task['Time_of_adding'] = $task->queue_date_time;
                $data_task['Method_name'] = $method_name;
                $data_task['Method_parameters'] = $method_parameters;
                $data_task['Max_number_of_execution'] = $task->queue_time_alive;
                $data_task['Current_number_of_execution'] = $task->queue_repeat_value;
                $data_task['Parent_Method'] = $task->queue_parent;

                $tasks_info[] = $data_task;                                     // Добавление в массив задач информации о задаче
            }
        }

        $result = array('Tasks' => $tasks_info);                                // Генерация результирующего массива

        Yii::$app->response->format = Response::FORMAT_JSON;                    // Указание формата вывода
        Yii::$app->response->data = $result;                                    // Указание данных для вывода
    }

    /**
     * Для дебага
     * Метод для вывода информации по всем задачам в списке
     */
    public static function debugConsolePrintTasksInfoJson()
    {
        $tasks_info = array();

        if (!self::isEmpty()) {
            // Выборка задач из очереди
            $tasks = Queue::find()->all();

            foreach ($tasks as $task) {
                // Десериализация имени и параметров метода
                $method = json_decode($task->queue_func, true);
                $method_name = $method[0];
                $method_parameters = array();

                $task_settings_count = count($method);
                if ($task_settings_count > 1) {
                    for ($i = 1; $i < $task_settings_count; $i++) {
                        $method_parameters[] = $method[$i];
                    }
                }

                // Генерирование структуры задачи для вывода
                $data_task['Id'] = $task->queue_id;
                $data_task['Time_of_adding'] = $task->queue_date_time;
                $data_task['Method_name'] = $method_name;
                $data_task['Method_parameters'] = $method_parameters;
                $data_task['Max_number_of_execution'] = $task->queue_time_alive;
                $data_task['Current_number_of_execution'] = $task->queue_repeat_value;

                $tasks_info[] = $data_task;
            }
        }

        $result = array('Tasks' => $tasks_info);

        echo '<pre>';
        var_dump($result);
        echo '</pre>';
    }
}