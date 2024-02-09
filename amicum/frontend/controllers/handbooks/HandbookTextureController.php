<?php

namespace frontend\controllers\handbooks;
//ob_start();

//классы и контроллеры yii2
use frontend\models\AccessCheck;
use frontend\models\UnityTexture;
use Yii;
use yii\db\Query;

//модели из БД
//модели без БД

class HandbookTextureController extends \yii\web\Controller
{
    //Функция отображения массива данных модели UnityTexture
    public function actionIndex()
    {
        $texturesList = $this->getTextures();
        return $this->render('index', ['texturesList' => $texturesList]);
    }
    //Функция получения массива данных модели UnityTexture
    public function getTextures( $search = "")
    {
        $sql_filter = "";
        if ($search) {
            $sql_filter .=
                "title like '%" . $search . "%' or " .
                "texture like '%" . $search . "%' or " .
                "description like '%" . $search . "%'";
        }
        $textures = (new Query())
            ->select([
                'id',
                'texture',
                'title',
                'description'
            ])
            ->from('unity_texture')
            ->where($sql_filter)
            ->orderBy('title')
            ->all();
        if ($search) {
            $lowerSearch = mb_strtolower($search);
            $texture_array = array();
            $i = 0;
            foreach ($textures as $texture) {
                $texture_array[$i]['id'] = $texture['id'];
                $texture_array[$i]['texture'] = $this->markSearched($search, $lowerSearch, $texture['texture']);
                $texture_array[$i]['title'] = $this->markSearched($search, $lowerSearch, $texture['title']);
                $texture_array[$i]['description'] = $this->markSearched($search, $lowerSearch, $texture['description']);
                $i++;
            }
            $textures = $texture_array;
        }
        return $textures;
    }

    /**
     * Функция добавления новой текстуры (UnityTexture)
     * @param $texture текстура в  Unity
     * @param $title название текстуры
     * @param $description комментарии
     */
    public function actionAddTexture()
    {
        $session = Yii::$app->session;
        $session->open();
        $errors = array();                                                                                              //пустой массив для сохранения ошибок
        $texture_list = array();                                                                                        // Пустой массив для хранения нового списка текстур

        if (isset($session['sessionLogin'])) {
            if (AccessCheck::checkAccess($session['sessionLogin'], 72)) {
                $post = \Yii::$app->request->post();                                                                            //получение данных от ajax-запроса
                $texture = UnityTexture::findOne(['title' => $post['title']]);                                                  // проверка полученной названии в БД
                if ($post['title'])                                                                                              // если был отправлен запрос на добавления текстуры
                {
                    if (!$texture)                                                                                               // если такой текстуры  c таким названием нет в БД в модели UnityTexture
                    {
                        $texture = new UnityTexture();                                                                          // объект экземпляр модели
                        $texture->texture = $post['texture'];                                                                   // добавление текстуры
                        $texture->title = $post['title'];                                                                       // добавление названия
                        $texture->description = $post['description'];
                        if ($texture->save())                                                                                    // Если данные были добавлены
                        {                                                                                                       // Получаем новый список текстур и сохраним в новый массив
                            if (isset($post['search'])) {
                                $texture_list = $this->getTextures($post['search']);
                            } else {
                                $texture_list = $this->getTextures();
                            }
                        } else                                                                                                    // Если данные не были добавлены
                        {
                            $errors[] = "Ошибка добавления новой текстуры";                                                     // Выводим ошибку добавления данных
                            $texture_list = $this->getTextures();
                        }
                    } else                                                                                                        // Если текстура уже су3ществует в БД в таблице UnityTexture
                    {
                        $errors[] = "Текстура с таким названим уже существует";                                                // Выводим ошибку о том, что текстура уже существует в БД
                        $texture_list = $this->getTextures();
                    }
                } else {
                    $errors[] = "Не был получен запрос на добвления новой текстуры";                                            // Выводим ошибку, что не был получен запрос на добавления новой текстуры
                    $texture_list = $this->getTextures();
                }
            } else {
                $errors[] = 'У вас недостаточно прав для выполнения этого действия';
                $texture_list = $this->getTextures();
            }
        } else {
            $errors[] = 'Сессия неактивна';
        }
        $result = array(['errors' => $errors, 'texture_list' => $texture_list]);                                        // сохраним в массив список ошибок и новый список текстур
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                  // формат json
        \Yii::$app->response->data = $result;                                                                         // отправляем обратно ввиде ajax формат
    }

    /**
     * Функция редактирования указанной текстуры по ID в модели UnityTexture
     */
    public function actionUpdateTexture()
    {
        $session = Yii::$app->session;
        $session->open();
        $errors = array();
        $texture_list = array();                                                                                        // Пустой массив для хранения нового списка текстур
        if (isset($session['sessionLogin'])) {
            if (AccessCheck::checkAccess($session['sessionLogin'], 73)) {
                $post = \Yii::$app->request->post();                                                                            // Переменная для получения post запросов
                if ($post['id'])                                                                                                 // Если был отправлен запрос на редактирования текстуры
                {
                    $texture_update = UnityTexture::findOne(['id' => $post['id']]);                                             // Находим текстуру по ID  в БД в модели UnityTexture
                    if ($texture_update)                                                                                         // Если такая текстуры с указанный идентификатором существует в таблице UnityTexture
                    {
                        $texture_update->texture = $post['texture'];                                                            // Редактируем текстуру
                        $texture_update->title = $post['title'];                                                                // Добавим новое название
                        $texture_update->description = $post['description'];                                                    // Добавим новые комментарии
                        if ($texture_update->save())                                                                             // Если данные были обновлены
                        {                                                                                                       // Получаем новый список текстур и сохраним в новый массив
                            if (isset($post['search'])) {
                                $texture_list = $this->getTextures($post['search']);
                            } else {
                                $texture_list = $this->getTextures();
                            }
                        } else                                                                                                    // Если данные не были редатированы
                        {
                            $errors[] = "Ошибка редактирования текстуры";                                                       // Выводим ошибку редатировния текстуры
                            $texture_list = $this->getTextures();
                        }
                    } else                                                                                                        // Если текстура по указанному ID не была найдена
                    {
                        $errors[] = "Нет такой текстуры";                                                                       // Выводим ошибку отсутствия текстуры
                        $texture_list = $this->getTextures();
                    }
                } else                                                                                                            // Если НЕ был отправлен запрос на редактирования текстуры
                {
                    $errors[] = "Запрос не был получен";                                                                        // Выводим ошибку отсутствия запроса на редактирования текстуры
                    $texture_list = $this->getTextures();
                }
            } else {
                $errors[] = 'У вас недостаточно прав для выполнения этого действия';
                $texture_list = $this->getTextures();
            }
        } else {
            $errors[] = 'Сессия неактивна';
        }
        $result = array('errors' => $errors, 'texture_list' => $texture_list);                                           // сохраним в массив список ошибок и новый список текстур
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                  // формат json
        \Yii::$app->response->data = $result;                                                                         // отправляем обратно ввиде ajax формат
    }

    /**
     * Метод удаления указанной тектуры по ID (индектификтор) в БД, в модели UnityTexture
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDeleteTexture()
    {
        $session = Yii::$app->session;
        $session->open();
        $errors = array();
        $texture_list = array();                                                                                        // Пустой массив для хранения нового списка текстур
        if (isset($session['sessionLogin'])) {
            if (AccessCheck::checkAccess($session['sessionLogin'], 74)) {
                $post = \Yii::$app->request->post();                                                                            // Переменная для получения post запросов
                if ($post['id'])                                                                                                 // Если был отправлен запрос на удаления текстуры
                {
                    $texture_delete = UnityTexture::findOne(['id' => $post['id']]);                                             // Находим текстуру по ID  в БД в модели UnityTexture
                    if ($texture_delete)                                                                                         // Если такая текстуры с указанный идентификатором существует в таблице UnityTexture
                    {
                        if ($texture_delete->delete())                                                                           // Если текстура была удалена
                        {                                                                                                       // Получаем новый список текстур и сохраним в новый массив
                            if (isset($post['search'])) {
                                $texture_list = $this->getTextures($post['search']);
                            } else {
                                $texture_list = $this->getTextures();
                            }
                        } else                                                                                                    // Если данные не были редатированы
                        {
                            $errors[] = "Ошибка удаления текстуры";                                                             // Выводим ошибку удаления текстуры
                            $texture_list = $this->getTextures();
                        }
                    } else                                                                                                        // Если текстура по указанному ID не была найдена
                    {
                        $errors[] = "Нет такой текстуры";                                                                       // Выводим ошибку отсутствия текстуры
                        $texture_list = $this->getTextures();
                    }
                } else                                                                                                            // Если НЕ был отправлен запрос на удаления текстуры
                {
                    $errors[] = "Запрос не был получен";                                                                        // Выводим ошибку отсутствия запроса на удаления- текстуры
                    $texture_list = $this->getTextures();
                }
            } else {
                $errors[] = 'У вас недостаточно прав для выполнения этого действия';
                $texture_list = $this->getTextures();
            }
        } else {
            $errors[] = 'Сессия неактивна';
        }
        $result = array(['errors' => $errors, 'texture_list' => $texture_list]);                                           // сохраним в массив список ошибок и новый список текстур
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                  // формат json
        \Yii::$app->response->data = $result;                                                                         // отправляем обратно ввиде ajax формат
    }

    /*Функция поиска текстуры*/
    public function actionSearchTexture()
    {
        $errors = array();
        $texture_list = array();                                                                                        // Пустой массив для хранения нового списка текстур
        $post = \Yii::$app->request->post();                                                                            // Переменная для получения post запросов
        if (isset($post['search'])) {
            $texture_list = $this->getTextures($post['search']);
        } else {
            $texture_list = $this->getTextures();
        }
        $result = array(['errors' => $errors, 'texture_list' => $texture_list]);                                           // сохраним в массив список ошибок и новый список текстур
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                   // формат json
        \Yii::$app->response->data = $result;                                                                          // отправляем обратно ввиде ajax формат
    }

    public function markSearched($search, $lowerSearch, $titleSearched)
    {
        $title = "";
        if ($search != "") {
            // echo $search;
            $titleParts = explode($lowerSearch, mb_strtolower($titleSearched));
            $titleCt = count($titleParts);
            $startIndex = 0;
            $title .= substr($titleSearched, $startIndex, strlen($titleParts[0]));
            $startIndex += strlen($titleParts[0] . $search);
            for ($j = 1; $j < $titleCt; $j++) {
                $title .= "<span class='searched'>" .
                    substr($titleSearched, $startIndex - strlen($search), strlen
                    ($search)) . "</span>" .
                    substr($titleSearched, $startIndex, strlen
                    ($titleParts[$j]));
                $startIndex += strlen($titleParts[$j] . $search);
            }
        } else {
            $title .= $titleSearched;
        }
        return $title;
    }
}
