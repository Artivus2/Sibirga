<?php

namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\Response;

/** Страница Документы
 * url в браузере - http://host_name_or_ip/admin/file-uploader
 * Class FileUploaderController
 * @package backend\controllers
 */
class FileUploaderController extends Controller
{
    public function actionIndex()
    {
        $files = $this->actionGetFiles();                                                                               //получаем массив загруженных файлов из функции actionGetFiles
        return $this->render('index', [                                                                           //рендерится вьюшка index.php из папки backend/views/file-uploader/index.php
            'fileList' => $files                                                                                        //передаём на фронт во вьюшку массив файлов $fileList
        ]);
    }

    /** Функция получения списка файлов из файлового диска
     * @return $filesArray (array) - массив файлов
     * Created by: Курбанов И. С. on 01.10.2018
     */
    public function actionGetFiles()
    {
        $debug_flag = false;                                                                                            //объявляем переменную для включения режима отладки
        $root = '@app/files';                                                                                           //указываем путь до папки с файлами в виде строки
        $root = Yii::getAlias($root);                                                                                   //получаем алиас Yii пути
        $root = FileHelper::normalizePath($root);                                                                       //нормализуем путь как объект для класса FileHelper
        $filesArray = array();                                                                                           //объявляем массив для хранения файлов
        $arrayModels = FileHelper::findFiles($root);                                                                    //получаем списсок файлов, вызвав метод findFiles класса FileHelper
        $filesLength = count($arrayModels);                                                                             //объявляем переменную для хранения длины массива файлов
        for ($i = 0; $i < $filesLength; $i++) {                                                                         //перебираем массив в цикле и заполняем массив filesArray в удобном для фронтэнда виде
            $filesArray[$i]['file_name'] = basename($arrayModels[$i]);                                                  //записываем имя файла
            $filesArray[$i]['date_time'] = date('d.m.Y H:i:s', filemtime($arrayModels[$i]));                     //записываем дату загрузки файла
            $filesArray[$i]['url'] = 'files/' . basename($arrayModels[$i]);                                             //записываем путь к файлу для скачивания
            $filesArray[$i]['file_size'] = filesize($arrayModels[$i]);                                                  //записываем размер файла
        }
        ArrayHelper::multisort($filesArray, 'file_name', SORT_ASC);                                //сортируем массив $filesArray по алфавиту по возрастанию
        if ($debug_flag) {                                                                                              //если включен режим оладки
            Yii::$app->response->format = Response::FORMAT_JSON;                                                        //то передаем на фронт массив загруженных файлов
            Yii::$app->response->data = $filesArray;                                                                    //в формате JSON
        } else {                                                                                                        //если режим отладки отключен
            return $filesArray;                                                                                         //возвращаем массив загруженных файлов
        }

    }

    /** Функция загрузки файла на сервер
     * Created by: Курбанов И. С. on 07.10.2018
     */
    public function actionUploadFile()
    {
        $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
        $all_files = array();
        $errors = array();
        if (isset($post['file_name']) and $post['file_name'] != "" and $_FILES['file'] != "" and $_FILES['file'] != null) {
            $file_name = strval($post['file_name']);
            $file_name = str_replace(' ', '_', $file_name);
            $file = $_FILES['file'];
            $upload_dir = 'files/';
            $uploaded_file = $upload_dir . $file_name;


            if (!move_uploaded_file($file['tmp_name'], $uploaded_file)) {
                $errors[] = "Ошибка загрузки файла ".$file_name;
            }
        } else {
            $errors[] = "файл не передан";
        }
        $all_files = $this->actionGetFiles();
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('errors' => $errors, 'file_list' => $all_files);
    }

    /** Функция удаления файла на сервере
     * Created by: Курбанов И. С. on 07.10.2018
     */
    public function actionDeleteFile()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $root = '@app/web/files/';
        $root = Yii::getAlias($root);
        if (isset($post['file_name']) and $post['file_name'] != "") {
            $file_name = strval($post['file_name']);
            if (unlink($root.$file_name)) {
                $response = "Файл успешно удалён";
            } else {
                $errors[] = "Ошибка удаления файла ".$file_name;
            }
        } else {
            $errors[] = "Не передано имя файла";
        }
        $files = $this->actionGetFiles();
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('errors' => $errors, 'file_list' => $files);                                  //ToDo: передать на фронт response
    }

    /** Фукнция скачивания файла с сервера
     * ToDo: необходимо дописать
     * Created by: Курбанов И. С. on 14.05.2019
     */
    public function actionDownloadFile()
    {
        $post = Assistant::GetServerMethod();
        $errors = array();
        if (isset($post['file_name']) and $post['file_name'] != "") {
            $file_name = (string)$post['file_name'];
            $allFiles = self::actionGetFiles();
            foreach ($allFiles as $file) {
                if (basename($file['file_name']) === $file_name) {
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename=' . basename($file['file_name']));
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file));
                    readfile('files/'.$file['file_name']);
                }
            }
        } else {
            $errors[] = "Не был передан файл";
        }
    }
}
