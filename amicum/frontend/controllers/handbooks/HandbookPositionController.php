<?php

namespace frontend\controllers\handbooks;
//ob_start();

//классы и контроллеры yii2
use frontend\controllers\Assistant;
use frontend\models\AccessCheck;
use frontend\models\Position;
use Yii;
use yii\web\Controller;
use yii\web\Response;

//модели без БД
// Базовые модели


class HandbookPositionController extends Controller
{

    // GetPositions             - получить список должностей как массив
    // savePosition             - метод сохранения/редактирования должности в бд
    // deletePosition           - метод удаления должности из бд


    /**
     * Название метода: actionIndex()
     * Метод который вызывается по умолчанию приложением если не указан конкретный action в контроллере
     *
     * @return string
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 16.05.2019 14:53
     * @since ver
     */
    public function actionIndex()
    {
        $model['model'] = PositionController::GetPositionDB();
        // TODO: JS не принимает warning, qualification, error
        return $this->render('index', [
            'model' => $model['model']
        ]);
    }

    /**
     * Название метода: actionSearchPosition()
     * Метод поиска должностей
     *
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 17.05.2019 10:43
     * @since ver
     */
    public function actionSearchPosition()
    {
        $model = ["errors" => [], "warnings" => [], "model" => []];                                                  // Пустой массив для хранения нового списка должностей
        $post = Yii::$app->request->post();                                                                             // Переменная для получения post запросов
        if (isset($post['search']) && !empty($post['search']))                                                                                     // Если передано условие поиска, находим долности по условию поиска
        {
            $model['model'] = PositionController::GetPositionDB($post['search']);
        }
        else
        {
            $model['model'] = PositionController::GetPositionDB();                                                            // Получаем список должностей
        }
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // Формат json
        Yii::$app->response->data = $model;                                                                          // Отправляем обратно
    }





    /**
     * Название метода: actionAddPosition()
     * Метод добавления новой должности
     *
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 17.05.2019 15:47
     * @since ver
     */
    public function actionAddPosition()
    {
        $post = Assistant::GetServerMethod();
        $model = [];
        $session = Yii::$app->session;                                                                                  // Старт сессии
        if (isset($session['sessionLogin'])) {                                                                          // Если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 75))                                        // Если пользователю разрешен доступ к функции
            {
                // TODO: В базе еще есть qualification
                if(isset($post['title']))                                                                               // Если передан с фронта новое наименование
                {
                    $status = PositionController::AddPositionDB($post['title']);                                        // Вызываем метод добавлеия должности в базу дыннах
                    $positions = (isset($post['search'])) ? PositionController::GetPositionDB($post['search']) : PositionController::GetPositionDB();
                    $model['model'] =  $positions;                                                                      // Формируем результирующий массив
                    $model['errors'] =  $status['errors'];
                    $model['warnings'] =  $status['warnings'];
                }
                else
                {
                    $errors[] = 'ActionAddPosition. Данные из запроса не получены';
                }
            }
            else
            {
                $errors[] = 'ActionAddPosition. У вас недостаточно прав для выполнения этого действия';
            }
        }
        else
        {
            $errors[] = 'ActionAddPosition. Сессия неактивна';
        }
        Yii::$app->response->format = Response::FORMAT_JSON;                                                  // формат json
        Yii::$app->response->data = $model;
    }

    /**
     * Функция редактирования должности в БД
     */
    public function actionEditPosition()
    {
        $errors = array();
        $model = array();
        $session = Yii::$app->session;                                                                                  // Старт сессии
        if (isset($session['sessionLogin'])) {                                                                          // Если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 76))                                        // Если пользователю разрешен доступ к функции
            {
                $post = Yii::$app->request->post();                                                                     // Получаем данные из POST запроса
                if (isset($post['id'], $post['title']))
                {                                                                                                       // Если переданы параметры
                    $status = PositionController::EditPositionDB($post['id'], $post['title']);         // Вызываем метод обновления должностей
                    $positions = (isset($post['search'])) ? PositionController::GetPositionDB($post['search']) : PositionController::GetPositionDB();
                    $model['model'] =  $positions;                                                                      // Формируем результирующий массив
                    $model['errors'] =  $status['errors'];
                    $model['warnings'] =  $status['warnings'];
                }
                else
                {
                    $errors[] = 'Данные не переданы';
                }
            } else
            {
                $errors[] = 'У вас недостаточно прав для выполнения этого действия';
            }
        }
        else
        {
            $errors[] = 'Сессия неактивна';
        }
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // Формат json
        Yii::$app->response->data = array('errors' => $errors, 'model' => $model);
    }

    /**
     * Название метода: actionDeletePosition()
     * Метод удаления должности
     *
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 17.05.2019 15:34
     * @since ver
     */
    public function actionDeletePosition()
    {
        $errors = array();
        $model = array();
        $session = Yii::$app->session;                                                                                  // Старт сессии
        if (isset($session['sessionLogin'])) {                                                                          // Если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 77)) {                                      // Если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                if (isset($post['id'])) {                                                                               // Если переданы параметры
                    $status = PositionController::DeletePositionDB($post['id']);                                         // Вызываем метод удаления должностей
                    $positions = (isset($post['search'])) ? PositionController::GetPositionDB($post['search']) : PositionController::GetPositionDB();
                    $model['model'] =  $positions;                                                                      // Формируем результирующий массив
                    $model['errors'] =  $status['errors'];
                    $model['warnings'] =  $status['warnings'];

                } else {
                    $errors[] = "Данные не переданы.";
                }
            } else {
                $errors[] = 'У вас недостаточно прав для выполнения этого действия';
            }
        } else {
            $errors[] = 'Сессия неактивна';
        }
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $model;
    }

    // GetListPosition - получить список должностей
    // пример: http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPosition&method=GetListPosition&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListPosition($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $position_list = Position::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$position_list) {
                $warnings[] = 'GetListPosition. Справочник должностей';
                $result = (object)array();
            } else {
                $result = $position_list;
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'GetListPosition. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetPositions - получить список должностей как массив
    // пример: http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPosition&method=GetPositions&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetPositions($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $position_list = Position::find()
                ->limit(20000)
                ->asArray()
                ->all();

            if (!$position_list) {
                $warnings[] = 'GetPositions. Справочник должностей';
                $result = array();
            } else {
                $result = $position_list;
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'GetPositions. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    // savePosition - метод сохранения/редактирования должности в бд
    // входной массив данных:
    // position:
    //  id                      - ключ роли
    //  title                   - название роли
    //  qualification           - разряд
    //  short_title             - сокращенное название должности
    // выходной массив:
    //  стандартный набор данных
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Role&method=savePosition&subscribe=login&data={"position":{}}
    //
    public static function savePosition($data_post = null)
    {
        $method_name = "savePosition";                                                                                      //уникальный идентификатор сессии
        $status = 1;                                                                                                    //флаг успешного выполнения метода
        $warnings = array();                                                                                            // массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $result = array();// промежуточный результирующий массив
        $position_model_id = null;
        try {
            if (!is_null($data_post) and $data_post != "") {
                $warnings[] = "savePosition. данные успешно переданы";
                $warnings[] = "savePosition. Входной массив данных" . $data_post;

                $data_post = json_decode($data_post);                                                                        //декодируем входной массив данных
                $warnings[] = "savePosition. декодировал входные параметры";
            } else {
                throw new \Exception($method_name . '. Входной массив обязательных данных пуст.');
            }
            if (
            !property_exists($data_post, 'position')
            ) {                                                                                                       // и проверяем наличие в нем нужных нам полей
                throw new \Exception($method_name . '.  Ошибка в наименование параметра во входных данных');
            }
            $warnings[] = "savePosition. Проверил входные данные";
            $position = $data_post->position;

            $position_model = Position::find()->where(['id' => $position->id])->one();
            if (!$position_model) {
                $position_model = new Position();
            }
            $position_model->title = $position->title;                                                               //сохранить id предприятия
            $position_model->qualification = $position->qualification;
            $position_model->short_title = $position->short_title;
            //сохранить тип подразделения
            if ($position_model->save())   // и проверяем наличие в нем нужных нам полей
            {
                $position_model->refresh();
                $position_model_id = $position_model->id;
                $position->id = $position_model_id;
                $warnings[] = "savePosition. Значение успешно сохранено. Новый id значение: " . $position_model_id;
            } else {
                $errors[] = $position_model->errors;
                throw new \Exception($method_name . '.  Ошибка сохранения модели Role');
            }
            $result = $position;

        } catch (\Exception $e) {
            $status = 0;
            $errors[] = "savePosition. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'id' => $position_model_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // deletePosition - метод удаления должности из бд
    // входные параметры:
    //  position_id - ключ должности
    // выходной массив:
    //  стандартный набор данных
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Role&method=deletePosition&subscribe=login&data={"position_id":"1"}
    //
    public static function deletePosition($data_post = null)
    {
        $method_name = "deletePosition";                                                                                             //уникальный идентификатор сессии
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        try {
            if (!is_null($data_post) and $data_post != "") {
                $warnings[] = "deletePosition. данные успешно переданы";
                $warnings[] = "deletePosition. Входной массив данных" . $data_post;
            } else {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $data_post = json_decode($data_post);                                                                      //декодируем входной массив данных
            $warnings[] = "deletePosition. декодировал входные параметры";
            if (!property_exists($data_post, 'position_id')) {
                throw new \Exception($method_name . '.  Ошибка в наименование параметра во входных данных');
            }

            $warnings[] = "deletePosition. Проверил входные данные";
            $position_id = $data_post->position_id;

            $result = Position::deleteAll(['id' => $position_id]);

        } catch (\Exception $e) {
            $status = 0;
            $errors[] = "deletePosition. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
