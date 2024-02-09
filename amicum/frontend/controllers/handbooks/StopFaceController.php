<?php

namespace frontend\controllers\handbooks;

use frontend\controllers\Assistant;
use frontend\models\StopFace;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;


class StopFaceController extends \yii\web\Controller
{
    /**
     * Структура передаваемых данных по простоям:
     *
     * {
     * "stop_face": {
     *     "title": "Простой 1",
     *     "description": "Для ремонта траковой цепи выехали комбайном вниз",
     *     "event_id": 7135,
     *     "date_time_start": "2019-06-27 22:42:00",
     *     "date_time_end": "2019-06-27 23:05:00",
     *     "stop_face_id": 2
     * }
     * }
     *
     */

    /**
     * Метод actionIndex() - тестовый для проверки работы методов
     * @param $stop_face            - данные по простою
     * @param $stop_face_id         - идентификатор простоя
     * @param $type_operation       - тип выполняемой операции: create - добавление, update - обновление, delete - удаление;
     * @return string
     * @package frontend\controllers\handbooks
     * @example http://web.amicum/handbooks/stop-face?type_operation=delete&stop_face_id=71&stop_face={%22stop_face%22:{%22title%22:%22%D0%9F%D1%80%D0%BE%D1%81%D1%82%D0%BE%D0%B9%201%22,%22description%22:%22%D0%94%D0%BB%D1%8F%20%D1%80%D0%B5%D0%BC%D0%BE%D0%BD%D1%82%D0%B0%20%D1%82%D1%80%D0%B0%D0%BA%D0%BE%D0%B2%D0%BE%D0%B9%20%D1%86%D0%B5%D0%BF%D0%B8%20%D0%B2%D1%8B%D0%B5%D1%85%D0%B0%D0%BB%D0%B8%20%D0%BA%D0%BE%D0%BC%D0%B1%D0%B0%D0%B9%D0%BD%D0%BE%D0%BC%20%D0%B2%D0%BD%D0%B8%D0%B7%22,%22event_id%22:7135,%22date_time_start%22:%222019-06-27%2022:42:00%22,%22date_time_end%22:%222019-06-27%2023:05:00%22,%22stop_face_id%22:2}}
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 09.07.2019 11:29
     */
    public function actionIndex($type_operation, $stop_face_id = NULL, $stop_face = NULL)
    {
        $decode_stop_face = json_decode($stop_face);
        $stop_face_array = ArrayHelper::toArray($decode_stop_face->stop_face);
        $errors = array();
        $warnings = array();
        $status = 1;
        switch ($type_operation)
        {
            case "create": $changed_stop_face = self::AddStopFace($stop_face_array); break;
            case "update": $changed_stop_face = self::UpdateStopFace($stop_face_id, $stop_face_array); break;
            case "delete": $changed_stop_face = self::DeleteStopFace($stop_face_id); break;
            default: $changed_stop_face = 0; Yii::$app->session->setFlash('danger', 'Не изместная операция');
        }
        $warnings = ArrayHelper::merge($changed_stop_face['warnings'], $warnings);
        $errors = ArrayHelper::merge($changed_stop_face['errors'], $errors);
        $status *= $changed_stop_face['status'];
        if($status === 0)
        {
            Yii::$app->session->setFlash('error', 'Ошибки:<br>'.serialize($errors).'<hr>Предупреждения:<br>'.serialize($warnings));
        }
        return $this->render('index');
    }


    /**
     * Метод AddStopFace()      - добавление простоя
     * @param $stop_face        - данные по простою согласно структуре представленной выше
     * @return array            - стандартный массив с данными предупреждений, ошибок, статусом
     * @package frontend\controllers\handbooks
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 09.07.2019 11:11
     */
    public static function AddStopFace($stop_face)
    {

        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                       // Массив ошибок
        $warnings[] = 'AddStopFace. Зашел в метод';
        $new_stop_face_id = -1;
        try {

            /******************** Добавление простоя ********************/
            $new_stop_face = new StopFace();

            $new_stop_face->attributes = $stop_face;
            $warnings[] = 'AddStopFace. Добавление простоя';
            if ($new_stop_face->save())                                                                                      // Валидируем полученные данные и сохраняем
            {
                $new_stop_face_id = $new_stop_face->id;
                $warnings[] = 'AddStopFace. Простой успешно добавлен в БД';
            } else {
                $errors[] = $new_stop_face->errors;
                throw new \Exception('AddStopFace. Возникла ошибка при сохранении простоя');
            }
        } catch (\Throwable $exception) {
            $errors[] = 'AddStopFace. Исключение ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'stop_face_id' => $new_stop_face_id);
        return $result_main;
    }

    /**
     * Метод UpdateStopFace()       - обновление данных простоя
     * @param null $stop_face       - данные по простоям
     * @param $stop_face_id         - идентификатор простоя который редактируется
     * @return array                - стандартный массив с данными предупреждений, ошибок, статусом
     * @package frontend\controllers\handbooks
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 09.07.2019 10:25
     */
    public static function UpdateStopFace($stop_face_id, $stop_face)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'UpdateStopFace. Зашел в метод';
        $updated_stop_face = StopFace::find()
            ->where(['id' => $stop_face_id])
            ->limit(1)
            ->one();
        if($updated_stop_face)                                                                                          // Если данные по простою получены, вносим изменения
        {
            $updated_stop_face->attributes = $stop_face;
            if($updated_stop_face->save())                                            // Валидируем полученные данные и сохраняем
            {
                $warnings[] = 'UpdateStopFace. Данные простоя успешно обновлены';
            }
            else
            {
                $status = 0;
                $errors[] = 'UpdateStopFace. Возникла ошибка при сохранении простоя';
            }
        }
        else
        {
            $status = 0;
            $errors[] = 'UpdateStopFace. Не найден простой по переданному id - '.$stop_face_id;
        }

        $result = $updated_stop_face;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteStopFace()   - удаление простоя
     * @param $stop_face_id     - идентификатор простоя который будет удаляться
     * @return array            - стандартный массив с данными предупреждений, ошибок, статусом
     * @package frontend\controllers\handbooks
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 09.07.2019 10:25
     */
    public static function DeleteStopFace($stop_face_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'DeleteStopFace. Зашел в метод';
        $deleted_stop_face = StopFace::find()
            ->where(['id' => $stop_face_id])
            ->limit(1)
            ->one();
        if($deleted_stop_face && $deleted_stop_face->delete())                                                          // Если данные по простою получены и удаление
        {
            $warnings[] = 'DeleteStopFace. Простой успешно удален из БД';
        }
        else
        {
            $status = 0;
            $errors[] = 'DeleteStopFace. Не найден простой по переданному id - '.$stop_face_id.' или удаление прошло с ошибкой';
        }
        $result = NULL;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
