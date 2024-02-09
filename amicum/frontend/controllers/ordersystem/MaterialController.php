<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\ordersystem;


use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Material;
use frontend\models\Storage;
use Throwable;
use Yii;
use yii\web\Controller;

class MaterialController extends Controller
{
    // внешние методы
    //      HandbookUnitController\GetUnitList - справочник единиц измерения - массив

    // внутренние методы:
    //      getMaterial                     - Получение списка материалов
    //      GetMaterialFavorite             - Метод получения справочника избранных материалов
    //      getStorage                      - Получение остатков/движения материалов
    //      delMaterial                     - удалить материал
    //      saveMaterial                    - сохранить материал
    //      saveMaterials                   - Метод сохранения материалов
    //      getBalanceStorage               - Получить остаток материала на складе на заданную дату
    //      getBalanceStorageByShift        - Получить остаток материала на складе на конец смены


    public function actionIndex(): string
    {
        return $this->render('index');
    }

    /**
     * Метод getStorage() - Получение списка материалов
     * @param null $data_post
     * $shift_id                              - ключ смены
     * $date_time_start                       - дата начала выборки
     * $date_time_end                         - дата окончания выборки
     * $company_department_id                 - департамент по которому получаем список материалов
     *
     * @return array
     * //      {kind_direction_store_id}
     * //              kind_direction_store_id:        - направления движения материала (списание/принятие)
     * //              places:                         - список мест
     * //                  {place_id}
     * //                      place_id:                           - ключ места хранения материала
     * //                      place_title:                        - наименование места хранения материала
     * //                      materials:                          - список материала
     * //                          {storage_id}
     * //                              storage_id:                     - ключ склада материала
     * //                              material_id                     - ключ материала
     * //                              material_title                  - наименование материала
     * //                              unit_id                         - ключ единицы измерения
     * //                              unit_title_short                - наименование сокращенное единиц измерения
     * //                              nomenclature_value              - количество номенклатур (материала)
     * //                              company_department_id           - ключ подразделения
     * //                              cost_nomenclature               - стоимость номенклатуры
     * //                              worker_id                       - ключ работника выполнившего операцию (берется из сессии)
     * //                              shift_id                        - ключ смены
     * //                              date_time                       - время выполнения операции приход расход
     * //                              place_id                        - ключ места
     * //                              place_title                     - наименование места
     * //                              kind_direction_store_id         - направление получения/списания материалов
     * //                              description                     - причина списания
     * @package frontend\controllers\prostoi
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Material&method=getStorage&subscribe=&data={"company_department_id":4029293,"shift_id":"1","date_time_start":"2019-01-01","date_time_end":"2021-01-31"}
     * http://127.0.0.1/read-manager-amicum?controller=ordersystem\Material&method=getStorage&subscribe=&data={"company_department_id":4029293,"shift_id":null,"date_time_start":"2019-01-01","date_time_end":"2021-01-31"}
     *
     * @author Якимов М.Н,
     * Created date: on 30.12.2019 9:19
     */
    public static function getStorage($data_post = NULL): array
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'getStorage';
        $result = array();                                                                                        // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'shift_id') ||
                !property_exists($post_dec, 'date_time_start') ||
                !property_exists($post_dec, 'date_time_end'))                                                            // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $shift_id = $post_dec->shift_id;
            $date_time_start = $post_dec->date_time_start;
            $date_time_end = $post_dec->date_time_end;


            // получаем список материалов
            $storage = Storage::find()
                ->joinWith('material.unit')
                ->joinWith('material.nomenclature')
                ->joinWith('place')
                ->where(['company_department_id' => $company_department_id])
                ->andWhere(['or',
                    ['between', 'storage.date_time', $date_time_start, $date_time_end],
                    ['storage.date_work' => date("Y-m-d", strtotime($date_time_start)), 'shift_id' => $shift_id]
                ])
                ->andFilterWhere(['shift_id' => $shift_id])
                ->asArray()
                ->all();

            // обрабатываем список материалов
            //      {kind_direction_store_id}
            //              kind_direction_store_id:        - направления движения материала (списание/принятие)
            //              places:                         - список мест
            //                  {place_id}
            //                      place_id:                           - ключ места хранения материала
            //                      place_title:                        - наименование места хранения материала
            //                      materials:                          - список материала
            //                          {storage_id}
            //                              storage_id:                     - ключ склада материала
            //                              material_id                     - ключ материала
            //                              material_title                  - наименование материала
            //                              unit_id                         - ключ единицы измерения
            //                              unit_title_short                - наименование сокращенное единиц измерения
            //                              nomenclature_value              - количество номенклатур (материала)
            //                              company_department_id           - ключ подразделения
            //                              cost_nomenclature               - стоимость номенклатуры
            //                              worker_id                       - ключ работника выполнившего операцию (берется из сессии)
            //                              shift_id                        - ключ смены
            //                              date_time                       - время выполнения операции приход расход
            //                              place_id                        - ключ места
            //                              place_title                     - наименование места
            //                              kind_direction_store_id         - направление получения/списания материалов
            //                              description                     - причина списания материала
            foreach ($storage as $material) {
                $kind_dir_store_id = $material['kind_direction_store_id'];
                $material_result[$kind_dir_store_id]['kind_direction_store_id'] = $kind_dir_store_id;
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['place_id'] = $material['place_id'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['place_title'] = $material['place']['title'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['storage_id'] = $material['id'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['material_id'] = $material['material_id'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['material_title'] = $material['material']['nomenclature']['title'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['unit_id'] = $material['material']['unit_id'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['unit_title_short'] = $material['material']['unit']['short'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['nomenclature_value'] = $material['nomenclature_value'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['company_department_id'] = $material['company_department_id'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['cost_nomenclature'] = $material['cost_nomenclature'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['worker_id'] = $material['worker_id'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['shift_id'] = $material['shift_id'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['date_time'] = date('d.m.Y H:i:s', strtotime($material['date_time']));
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['date_work'] = date('d.m.Y H:i:s', strtotime($material['date_work']));
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['description'] = $material['description'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['place_id'] = $material['place_id'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['place_title'] = $material['place']['title'];
                $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['kind_direction_store_id'] = $kind_dir_store_id;
                if ($material['date_time']) {
                    $material_result[$kind_dir_store_id]['places'][$material['place_id']]['materials'][$material['id']]['date_time_format'] = date('d.m.Y H:i:s', strtotime($material['date_time']));
                }
            }


            if (!isset($material_result)) {
                $result['storage'] = (object)array();
            } else {
                $result['storage'] = $material_result;
            }


        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод getBalanceStorage() - Получить остаток материала на складе на заданную дату
     * @param null $data_post
     * $date_time                       - дата на которую получаем остаток
     * $company_department_id           - департамент по которому получаем список материалов
     *
     * @return array
     * //     []
     * //         material_id                     - ключ материала
     * //         material_title                  - наименование материала
     * //         unit_id                         - ключ единицы измерения
     * //         unit_title_short                - наименование сокращенное единиц измерения
     * //         nomenclature_value              - количество номенклатур (материала)
     * @package frontend\controllers\prostoi
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Material&method=getBalanceStorage&subscribe=&data={"company_department_id":4029293,"date_time":"2019-01-01"}
     *
     * @author Якимов М.Н,
     * Created date: on 30.12.2019 9:19
     */
    public static function getBalanceStorage($data_post = NULL): array
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'getBalanceStorage';
        $result = array();                                                                                        // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'date_time'))                                                      // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $date_time = $post_dec->date_time;

            if (!$date_time) {
                $date_time = date("Y-m-d");
            }
            // получаем список материалов
            $storage = Storage::find()
                ->select('
                storage.material_id as material_id,
                nomenclature.title as material_title,
                material.unit_id,
                unit.short as unit_title_short,
                sum(storage.nomenclature_value) as sum_nomenclature_value,
                storage.kind_direction_store_id as kind_direction_store_id,
                ')
                ->joinWith('material.unit')
                ->joinWith('material.nomenclature')
                ->where(['company_department_id' => $company_department_id])
                ->andWhere(['<=', 'storage.date_time', $date_time])
                ->groupBy('kind_direction_store_id, material_id, unit_id, material_title, unit_title_short')
                ->asArray()
                ->all();

            if ($storage) {
                foreach ($storage as $material) {
                    $material_group[$material['material_id']]['material_id'] = $material['material_id'];
                    $material_group[$material['material_id']]['material_title'] = $material['material_title'];
                    $material_group[$material['material_id']]['unit_id'] = $material['unit_id'];
                    $material_group[$material['material_id']]['unit_title_short'] = $material['unit_title_short'];
                    $material_group[$material['material_id']]['directions'][$material['kind_direction_store_id']]['sum_nomenclature_value'] = $material['sum_nomenclature_value'];
                }
                unset($storage);
                unset($material);

                // 1 - списание
                // 2 - принятие
                //     []
                //         material_id                     - ключ материала
                //         material_title                  - наименование материала
                //         unit_id                         - ключ единицы измерения
                //         unit_title_short                - наименование сокращенное единиц измерения
                //         nomenclature_value              - количество номенклатур (материала)
                foreach ($material_group as $material) {
                    if (!isset($material['directions'][1])) {
                        $material['directions'][1]['sum_nomenclature_value'] = 0;
                    }
                    if (!isset($material['directions'][2])) {
                        $material['directions'][2]['sum_nomenclature_value'] = 0;
                    }
                    $material_result[] = array(
                        'material_id' => $material['material_id'],
                        'material_title' => $material['material_title'],
                        'unit_id' => $material['unit_id'],
                        'unit_title_short' => $material['unit_title_short'],
                        'nomenclature_value' => ($material['directions'][2]['sum_nomenclature_value'] - $material['directions'][1]['sum_nomenclature_value']),
                    );
                }
            }

            if (!isset($material_result)) {
                $result['storage'] = array();
            } else {
                $result['storage'] = $material_result;
            }


        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод delMaterial() - удалить материал
     * @param null $data_post
     * storage_id                              - ключ склада материалов
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Material&method=delMaterial&subscribe=&data={"storage_id":1}
     *
     * @author Якимов М.Н.
     * Created date: on 30.12.2019 9:19
     */
    public static function delMaterial($data_post = NULL): array
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'delMaterial';
        $result = array();                                                                                        // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'storage_id')
            )                                                            // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $storage_id = $post_dec->storage_id;

            // Удаляем простой
            $result = Storage::deleteAll(['id' => $storage_id]);

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод saveMaterial() - сохранить материал
     * @param null $data_post
     * //                          storage:
     * //                              storage_id:                     - ключ склада материала
     * //                              material_id                     - ключ материала
     * //                              material_title                  - наименование материала
     * //                              unit_id                         - ключ единицы измерения
     * //                              unit_title_short                - наименование сокращенное единиц измерения
     * //                              nomenclature_value              - количество номенклатур (материала)
     * //                              company_department_id           - ключ подразделения
     * //                              cost_nomenclature               - стоимость номенклатуры
     * //                              worker_id                       - ключ работника выполнившего операцию (берется из сессии)
     * //                              shift_id                        - ключ смены
     * //                              date_time                       - время выполнения операции приход расход
     * //                              date_work                       - производственная дата списания
     * //                              place_id                        - ключ места
     * //                              place_title                     - наименование места
     * //                              kind_direction_store_id         - направление получения/списания материалов
     * //                              description                     - причина списания
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Material&method=saveMaterial&subscribe=&data={"storage":{}}
     *
     * @author Якимов М.Н.
     * Created date: on 30.12.2019 9:19
     */
    public static function saveMaterial($data_post = NULL): array
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'saveMaterial';
        $storage = array();                                                                                        // Промежуточный результирующий массив
        $session = Yii::$app->session;
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'storage')
            )                                                            // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $storage = $post_dec->storage;
            $storage_id = $storage->storage_id;

            if (property_exists($storage, 'date_work')) {
                $date_work = date('Y-m-d', strtotime($storage->date_work));
            } else {
                $date_work = Assistant::GetShiftByDateTime($storage->date_time)['date_work'];
            }

            $save_storage = Storage::findOne(['id' => $storage_id]);

            if (!$save_storage) {
                $save_storage = new Storage();
            }

            $shift_id = $storage->shift_id ? $storage->shift_id : 5;

            $save_storage->worker_id = $session['worker_id'];
            $save_storage->company_department_id = $storage->company_department_id;
            $save_storage->material_id = $storage->material_id;
            $save_storage->nomenclature_value = $storage->nomenclature_value;
            $save_storage->kind_direction_store_id = $storage->kind_direction_store_id;
            $save_storage->cost_nomenclature = $storage->cost_nomenclature;
            $save_storage->date_time = date('Y-m-d H:i:s', strtotime($storage->date_time));
            $save_storage->place_id = $storage->place_id;
            $save_storage->date_work = $date_work;
            $save_storage->shift_id = $shift_id;
            $save_storage->description = $storage->description;

            if ($save_storage->save()) {
                $save_storage->refresh();
                $storage->storage_id = $save_storage->id;
            } else {
                $errors[] = $save_storage->errors;
                throw new Exception($method_name . '. Ошибка сохранения модели склад материалов Storage');
            }

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $storage, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод getMaterial() - сохранить справочник материала
     * @param null $data_post
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Material&method=getMaterial&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 30.12.2019 9:19
     */
    public static function getMaterial($data_post = NULL)
    {
        $log = new LogAmicumFront("getMaterial");

        $result = array();                                                                                              // Массив ошибок

        try {
            $log->addLog("Начал выполнение метода");
            $materials = Material::find()
                ->joinWith('unit')
                ->joinWith('nomenclature')
                ->asArray()
                ->all();

            foreach ($materials as $material) {
                $materials_result[$material['id']]['material_id'] = $material['id'];
                $materials_result[$material['id']]['unit_id'] = $material['unit_id'];
                $materials_result[$material['id']]['unit_short_title'] = $material['unit']['short'];
                $materials_result[$material['id']]['material_title'] = $material['nomenclature']['title'];
            }

            if (isset($materials_result)) {
                $result = $materials_result;
            } else {
                $result = (object)array();
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод GetMaterialFavorite() - Метод получения справочника избранных материалов
     * Входные параметры:
     *      company_department_id   - ключ подразделения, на который получаем список избранных материалов
     * Выходные параметры:
     *      {material_id}           - ключ материала
     *          material_id             - ключ материала
     *          unit_id                 - ключ ед.измерения материала
     *          unit_short_title        - краткое название ед.измерения
     *          material_title          - название номенклатуры
     * @param null $data_post
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Material&method=GetMaterialFavorite&subscribe=&data={%22company_department_id%22:60002522}
     *
     * @author Якимов М.Н.
     * Created date: on 30.12.2019 9:19
     */
    public static function GetMaterialFavorite($data_post = NULL)
    {
        $log = new LogAmicumFront("GetMaterialFavorite");

        $result = array();                                                                                              // Массив ошибок

        try {
            $log->addLog("Начал выполнение метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $company_department_id = $post_dec->company_department_id;
            $date_time = date("Y-m-d", strtotime(Assistant::GetDateTimeNow() . '-14days'));

            $materials = Material::find()
                ->joinWith('unit')
                ->joinWith('nomenclature')
                ->joinWith('storages')
                ->where(['company_department_id' => $company_department_id])
                ->andWhere('date_time>"' . $date_time . '"')
                ->asArray()
                ->all();

            foreach ($materials as $material) {
                $materials_result[$material['id']]['material_id'] = $material['id'];
                $materials_result[$material['id']]['unit_id'] = $material['unit_id'];
                $materials_result[$material['id']]['unit_short_title'] = $material['unit']['short'];
                $materials_result[$material['id']]['material_title'] = $material['nomenclature']['title'];
            }

            if (isset($materials_result)) {
                $result = $materials_result;
            } else {
                $result = (object)array();
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод getBalanceStorageByShift() - Получить остаток материала на складе на конец смены
     * @param null $data_post
     * $date                            - дата, на которую получаем остаток
     * $shift_id                        - ключ смены
     * $company_department_id           - департамент, по которому получаем список материалов
     *
     * @return array
     * //     []
     * //         material_id                     - ключ материала
     * //         material_title                  - наименование материала
     * //         unit_id                         - ключ единицы измерения
     * //         unit_title_short                - наименование сокращенное единиц измерения
     * //         nomenclature_value              - количество номенклатур (материала)
     * @package frontend\controllers\prostoi
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Material&method=getBalanceStorageByShift&subscribe=&data={"company_department_id":4029293,"shift_id":"2","date":"2019-01-01"}
     *
     * @author Якимов М.Н,
     * Created date: on 30.12.2019 9:19
     */
    public static function getBalanceStorageByShift($data_post = NULL): array
    {
        $log = new LogAmicumFront("getBalanceStorageByShift");
        $result = array();                                                                                        // Промежуточный результирующий массив
        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'shift_id') ||
                !property_exists($post_dec, 'date'))                                                      // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $company_department_id = $post_dec->company_department_id;
            $date = $post_dec->date;
            $shift_id = $post_dec->shift_id;

            $date_time = Assistant::GetDateTimeByShift($date, $shift_id)['date_time_end'];

            $log->addData($date_time, '$date_time', __LINE__);

            // получаем список материалов
            $storage = Storage::find()
                ->select('
                storage.material_id as material_id,
                nomenclature.title as material_title,
                material.unit_id,
                unit.short as unit_title_short,
                sum(storage.nomenclature_value) as sum_nomenclature_value,
                storage.kind_direction_store_id as kind_direction_store_id,
                ')
                ->joinWith('material.unit')
                ->joinWith('material.nomenclature')
                ->where(['company_department_id' => $company_department_id])
                ->andWhere(['<=', 'storage.date_time', $date_time])
                ->groupBy('kind_direction_store_id, material_id, unit_id, material_title, unit_title_short')
                ->asArray()
                ->all();

            if ($storage) {
                foreach ($storage as $material) {
                    $material_group[$material['material_id']]['material_id'] = $material['material_id'];
                    $material_group[$material['material_id']]['material_title'] = $material['material_title'];
                    $material_group[$material['material_id']]['unit_id'] = $material['unit_id'];
                    $material_group[$material['material_id']]['unit_title_short'] = $material['unit_title_short'];
                    $material_group[$material['material_id']]['directions'][$material['kind_direction_store_id']]['sum_nomenclature_value'] = $material['sum_nomenclature_value'];
                }
                unset($storage);
                unset($material);

                // 1 - списание
                // 2 - принятие
                //     []
                //         material_id                     - ключ материала
                //         material_title                  - наименование материала
                //         unit_id                         - ключ единицы измерения
                //         unit_title_short                - наименование сокращенное единиц измерения
                //         nomenclature_value              - количество номенклатур (материала)
                foreach ($material_group as $material) {
                    if (!isset($material['directions'][1])) {
                        $material['directions'][1]['sum_nomenclature_value'] = 0;
                    }
                    if (!isset($material['directions'][2])) {
                        $material['directions'][2]['sum_nomenclature_value'] = 0;
                    }
                    $material_result[] = array(
                        'material_id' => $material['material_id'],
                        'material_title' => $material['material_title'],
                        'unit_id' => $material['unit_id'],
                        'unit_title_short' => $material['unit_title_short'],
                        'nomenclature_value' => ($material['directions'][2]['sum_nomenclature_value'] - $material['directions'][1]['sum_nomenclature_value']),
                    );
                }
            }

            if (!isset($material_result)) {
                $result['storage'] = array();
            } else {
                $result['storage'] = $material_result;
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * saveMaterials() - Метод сохранения материалов
     * @param null $data_post
     *     materials:
     *          {material_id}               - ключ материала
     *              storage_id:                     - ключ склада материала
     *              material_id                     - ключ материала
     *              material_title                  - наименование материала
     *              unit_id                         - ключ единицы измерения
     *              unit_title_short                - наименование сокращенное единиц измерения
     *              nomenclature_value              - количество номенклатур (материала)
     *              company_department_id           - ключ подразделения
     *              cost_nomenclature               - стоимость номенклатуры
     *              worker_id                       - ключ работника выполнившего операцию (берется из сессии)
     *              shift_id                        - ключ смены
     *              date_time                       - время выполнения операции приход расход
     *              place_id                        - ключ места
     *              place_title                     - наименование места
     *              kind_direction_store_id         - направление получения/списания материалов
     *              description                     - причина списания
     *              status_del                      - признак удаления (null/any)
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Material&method=saveMaterials&subscribe=&data={"materials":{}}
     *
     * @author Якимов М.Н.
     * Created date: on 30.12.2019 9:19
     */
    public static function saveMaterials($data_post = NULL): array
    {
        $post_dec = null;                                                                                        // Промежуточный результирующий массив
        $session = Yii::$app->session;

        $log = new LogAmicumFront("saveMaterials");
        $result = array();                                                                                        // Промежуточный результирующий массив
        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'materials')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $materials = $post_dec->materials;

            foreach ($materials as $key => $material) {
                $storage_id = $material->storage_id;
                $material_id = $material->material_id;
                $company_department_id = $material->company_department_id;

                if (property_exists($material, 'date_work')) {
                    $date_work = date('Y-m-d', strtotime($material->date_work));
                } else {
                    $date_work = Assistant::GetShiftByDateTime($material->date_time)['date_work'];
                }

                if (property_exists($material, 'status_del') and $material->status_del) {
                    Storage::deleteAll(['id' => $storage_id, 'material_id' => $material_id, 'company_department_id' => $company_department_id]);
                    unset($post_dec->materials->{$key});
                } else {
                    $save_storage = Storage::findOne(['id' => $storage_id]);

                    if (!$save_storage) {
                        $save_storage = new Storage();
                    }

                    $save_storage->worker_id = $session['worker_id'];
                    $save_storage->company_department_id = $company_department_id;
                    $save_storage->material_id = $material_id;
                    $save_storage->nomenclature_value = $material->nomenclature_value;
                    $save_storage->kind_direction_store_id = $material->kind_direction_store_id;
                    $save_storage->cost_nomenclature = $material->cost_nomenclature;
                    $save_storage->date_time = date('Y-m-d H:i:s', strtotime($material->date_time));
                    $save_storage->date_work = $date_work;
                    $save_storage->place_id = $material->place_id;
                    $save_storage->shift_id = $material->shift_id ?: 5;
                    $save_storage->description = $material->description;

                    if (!$save_storage->save()) {
                        $log->addData($save_storage->errors, '$save_storage->errors', __LINE__);
                        throw new Exception('Ошибка сохранения модели склад материалов Storage');
                    }

                    $save_storage->refresh();
                    $post_dec->materials->{$key}->storage_id = $save_storage->id;
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $post_dec], $log->getLogAll());
    }
}
