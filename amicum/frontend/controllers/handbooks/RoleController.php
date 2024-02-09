<?php

namespace frontend\controllers\handbooks;

use frontend\controllers\Assistant;
use frontend\models\Role;
use Yii;


class RoleController extends \yii\web\Controller
{
    // GetListRoleWorkers - получить список ролей работников
    // GetListRoleWorkersSearch - получить список ролей работников c учетом фильтра
    // addRole      - метод сохранения названия роли в бд
    // updateRole   - метод обновления названия роли в бд
    // delRole      - метод удаления роли из бд
    // deleteRole   - метод удаления роли из бд по ключу без поиска
    // saveRole     - метод сохранения/редактирования роли в бд

    public function actionIndex()
    {
        $role_array = self::GetListRoleWorkers();
        $this->view->registerJsVar('role_array', $role_array);
        return $this->render('index');
    }

    // GetListRoleWorkers - получить список ролей работников
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Role&method=GetListRoleWorkers&subscribe=login&data={}
    public static function GetListRoleWorkers($data_post = null)
    {
        $status = 1;                                                                                                    //флаг успешного выполнения метода
        $warnings = array();                                                                                            // массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $result = array();

        try {
            $role_list = Role::find()
                ->limit(2000)
                ->asArray()
                ->all();
            if (!$role_list)
                $warnings[] = "GetListRoleWorkers. Список ролей пуст";
        } catch (\Exception $e) {
            $status = 0;
            $errors[] = "GetListRoleWorkers. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $result_main = array('Items' => $role_list, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetListRoleWorkersSearch - получить список ролей работников c учетом фильтра
    // входные данные:
    //  search_query - фильтр поиска
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Role&method=GetListRoleWorkersSearch&subscribe=login&data={"search_query":""}
    public static function GetListRoleWorkersSearch($data_post = null)
    {
        $session_amicum = Yii::$app->session;                                                                           //получаем текущую сессию пользователя
        $session_id = null;                                                                                             //уникальный идентификатор сессии
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        if (!is_null($data_post) and $data_post != "") {
            $warnings[] = "GetListRoleWorkersSearch. данные успешно переданы";
            $warnings[] = "GetListRoleWorkersSearch. Входной массив данных" . $data_post;
            try {
                $role = json_decode($data_post);                                                                      //декодируем входной массив данных
                $warnings[] = "GetListRoleWorkersSearch. декодировал входные параметры";
                if (property_exists($role, 'search_query'))   // и проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = "GetListRoleWorkersSearch. Проверил входные данные";
                    $search_query = $role->search_query;

                    $role_list = Role::find()
                        ->limit(2000)
                        ->where(['like', 'title', $search_query])
                        ->asArray()
                        ->all();
                    if (!$role_list) {
                        $warnings[] = "GetListRoleWorkersSearch. Cписок ролей пуст";
                        $status *= 1;
                    } else {
//                        $result = $role_list;
                        $i = 0;
                        foreach ($role_list as $role) {
                            $result[$i]['id'] = $role['id'];
                            $result[$i]['title'] = Assistant::MarkSearched($search_query, $role['title']);
                            $result[$i]['type'] = $role['type'];
                            $result[$i]['weight'] = $role['weight'];
                            $i++;
                        }
                        $warnings[] = "GetListRoleWorkersSearch. Cписок ролей получен";
                        $status *= 1;
                    }
                } else {
                    $errors[] = "GetListRoleWorkersSearch. Ошибка в наименование параметра во входных данных";
                    $status = 0;
                }
            } catch (\Exception $e) {
                $status = 0;
                $errors[] = "GetListRoleWorkersSearch. Исключение: ";
                $errors[] = $e->getMessage();
                $errors[] = $e->getLine();
            }
        } else {
            $errors[] = "GetListRoleWorkersSearch. Входной массив обязательных данных пуст. Имя пользователя не передано.";
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // addRole - метод сохранения названия роли в бд
    // входной массив данных:
    //  role_title - назавние роли
    //  search_query - фильтр поиска
    // выходной массив:
    //  стандартный набор данных
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Role&method=addRole&subscribe=login&data={"role_title":"тест","role_weight":1,"role_type":5,"search_query":""}
    //
    public static function addRole($data_post = null)
    {
        $session_amicum = Yii::$app->session;                                                                           //получаем текущую сессию пользователя
        $session_id = null;                                                                                             //уникальный идентификатор сессии
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $role_model_id = null;
        if (!is_null($data_post) and $data_post != "") {
            $warnings[] = "addRole. данные успешно переданы";
            $warnings[] = "addRole. Входной массив данных" . $data_post;
            try {
                $role = json_decode($data_post);                                                                        //декодируем входной массив данных
                $warnings[] = "addRole. декодировал входные параметры";
                if (
                    property_exists($role, 'role_title') &&
                    property_exists($role, 'search_query')
                )                                                                                                       // и проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = "addRole. Проверил входные данные";
                    $role_title = $role->role_title;

                    // Тип роли, используется для разделения справочника на несколько подсправочников. Шахтный (type=1),
                    // участковый (ИТР) (type=2), участковый (рабочие) (type=3), прочие (type=4).
                    if (property_exists($role, 'role_type')) {
                        $role_type = $role->role_type;
                    } else {
                        $role_type = null;
                    }

                    if (property_exists($role, 'surface_underground')) {
                        $surface_underground = $role->surface_underground;
                    } else {
                        $surface_underground = null;
                    }
                    // Уровень роли в иерархии. Нужен в ряде случаев для сортировки сотрудников в порядке убывания уровня ролей
                    if (property_exists($role, 'role_weight')) {
                        $role_weight = $role->role_weight;
                    } else {
                        $role_weight = null;
                    }

                    $search_query = $role->search_query;

                    $role_model = Role::find()->where([
                        'title' => $role_title
                    ])->one();
                    if (!$role_model) {
                        $role_model = new Role();
                        $role_model->title = $role_title;                                                               //сохранить id предприятия
                        $role_model->weight = $role_weight;
                        $role_model->type = $role_type;
                        $role_model->surface_underground = $surface_underground;
                        //сохранить тип подразделения
                        if ($role_model->save())   // и проверяем наличие в нем нужных нам полей
                        {
                            $status *= 1;
                            $role_model_id = $role_model->id;
                            $warnings[] = "addRole. Значение успешно сохранено. Новый id значение: " . $role_model_id;
                        } else {
                            $errors[] = "addRole. Ошибка сохранения модели Role";
                            $errors[] = $role_model->errors;
                            $status = 0;
                        }
                    } else {
                        $status *= 1;
                        $role_model_id = $role_model->id;
                        $warnings[] = "addRole. Такая роль уже существовала. Сохранение не осуществлялось: " . $role_model_id;
                    }

                    $list_role = self::GetListRoleWorkersSearch(json_encode(array('search_query' => $search_query)));
                    $result = $list_role['Items'];
                    $status *= $list_role['status'];
                    $warnings[] = $list_role['warnings'];
                    $errors[] = $list_role['errors'];
                } else {
                    $errors[] = "addRole. Ошибка в наименование параметра во входных данных";
                    $status = 0;
                }
            } catch (\Exception $e) {
                $status = 0;
                $errors[] = "addRole. Исключение: ";
                $errors[] = $e->getMessage();
                $errors[] = $e->getLine();
            }
        } else {
            $errors[] = "addRole. Входной массив обязательных данных пуст. Имя пользователя не передано.";
            $status = 0;
        }

        $result_main = array('Items' => $result, 'id' => $role_model_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // saveRole - метод сохранения/редактирования роли в бд
    // входной массив данных:
    // role:
    //  id                      - ключ роли
    //  title                   - название роли
    //  type                    - тип роли
    //  weight                  - позиция в сортировке роли
    //  surface_underground     - подземный поверхностный
    // выходной массив:
    //  стандартный набор данных
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Role&method=saveRole&subscribe=login&data={"role":{}}
    //
    public static function saveRole($data_post = null)
    {
        $method_name = "saveRole";                                                                                      //уникальный идентификатор сессии
        $status = 1;                                                                                                    //флаг успешного выполнения метода
        $warnings = array();                                                                                            // массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $result = array();                                                                                              // промежуточный результирующий массив
        $role_model_id = null;
        try {
            if (!is_null($data_post) and $data_post != "") {
                $warnings[] = "saveRole. данные успешно переданы";
                $warnings[] = "saveRole. Входной массив данных" . $data_post;

                $role = json_decode($data_post);                                                                        //декодируем входной массив данных
                $warnings[] = "saveRole. декодировал входные параметры";
            } else {
                throw new \Exception($method_name . '. Входной массив обязательных данных пуст.');
            }
            if (
            !property_exists($role, 'role')
            ) {                                                                                                       // и проверяем наличие в нем нужных нам полей
                throw new \Exception($method_name . '.  Ошибка в наименование параметра во входных данных');
            }
            $warnings[] = "saveRole. Проверил входные данные";
            $role = $role->role;

            $role_model = Role::find()->where(['id' => $role->id])->one();
            if (!$role_model) {
                $role_model = new Role();
            }
            $role_model->title = $role->title;                                                               //сохранить id предприятия
            $role_model->weight = $role->weight;
            $role_model->type = $role->type;
            $role_model->surface_underground = $role->surface_underground;
            //сохранить тип подразделения
            if ($role_model->save())   // и проверяем наличие в нем нужных нам полей
            {
                $role_model->refresh();
                $role_model_id = $role_model->id;
                $role->id = $role_model_id;
                $warnings[] = "saveRole. Значение успешно сохранено. Новый id значение: " . $role_model_id;
            } else {
                $errors[] = $role_model->errors;
                throw new \Exception($method_name . '.  Ошибка сохранения модели Role');
            }
            $result = $role;

        } catch (\Exception $e) {
            $status = 0;
            $errors[] = "saveRole. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'id' => $role_model_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // updateRole - метод обновления названия роли в бд
    //
    //  role_id - ключ роли
    //  role_title - назавние роли
    //  search_query - фильтр поиска
    // выходной массив:
    //  стандартный набор данных
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Role&method=updateRole&subscribe=login&data={"role_id":"1","role_title":"тест","search_query":""}
    //
    public static function updateRole($data_post = null)
    {
        $session_amicum = Yii::$app->session;                                                                           //получаем текущую сессию пользователя
        $session_id = null;                                                                                             //уникальный идентификатор сессии
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $role_model_id = null;
        if (!is_null($data_post) and $data_post != "") {
            $warnings[] = "updateRole. данные успешно переданы";
            $warnings[] = "updateRole. Входной массив данных" . $data_post;
            try {
                $role = json_decode($data_post);                                                                      //декодируем входной массив данных
                $warnings[] = "updateRole. декодировал входные параметры";
                if (property_exists($role, 'role_title') &&
                    property_exists($role, 'role_id') &&
                    property_exists($role, 'search_query')
                )   // и проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = "updateRole. Проверил входные данные";
                    $role_id = $role->role_id;
                    $role_title = $role->role_title;
                    $search_query = $role->search_query;
                    // Тип роли, используется для разделения справочника на несколько подсправочников. Шахтный (type=1),
                    // участковый (ИТР) (type=2), участковый (рабочие) (type=3), прочие (type=4).
                    if (property_exists($role, 'role_type')) {
                        $role_type = $role->role_type;
                    }
                    // Уровень роли в иерархии. Нужен в ряде случаев для сортировки сотрудников в порядке убывания уровня ролей
                    if (property_exists($role, 'role_weight')) {
                        $role_weight = $role->role_weight;
                    }
                    // Уровень роли в иерархии. Нужен в ряде случаев для сортировки сотрудников в порядке убывания уровня ролей
                    if (property_exists($role, 'surface_underground')) {
                        $surface_underground = $role->surface_underground;
                    }

                    $role_model = Role::find()->where([
                        'id' => $role_id
                    ])->one();
                    if ($role_model) {
                        $role_model->title = $role_title;                                                               // Cохранить id предприятия
                        if (property_exists($role, 'role_weight')) {
                            $role_model->weight = $role_weight;                                                             // Уровень роли в иерархии
                        }
                        if (property_exists($role, 'role_type')) {
                            $role_model->type = $role_type;                                                                 // Шахтный (type=1), участковый (ИТР) (type=2), участковый (рабочие) (type=3), прочие (type=4)
                        }
                        if (property_exists($role, 'surface_underground')) {
                            $role_model->surface_underground = $surface_underground;                                                                 // Шахтный (type=1), участковый (ИТР) (type=2), участковый (рабочие) (type=3), прочие (type=4)
                        }
                        //сохранить тип подразделения
                        if ($role_model->save())   // и проверяем наличие в нем нужных нам полей
                        {
                            $status *= 1;
                            $role_title = $role_model->title;
                            $warnings[] = "updateRole. Значение успешно сохранено. Новый title значение: " . $role_title;
                        } else {
                            $errors[] = "updateRole. Ошибка сохранения модели Role";
                            $errors[] = $role_model->errors;
                            $status = 0;
                        }
                    } else {
                        $status = 0;
                        $errors[] = "updateRole. Такого айди в базе не существует: " . $role_id;
                    }

                    $list_role = self::GetListRoleWorkersSearch(json_encode(array('search_query' => $search_query)));
                    $result = $list_role['Items'];
                    $status *= $list_role['status'];
                    $warnings[] = $list_role['warnings'];
                    $errors[] = $list_role['errors'];
                } else {
                    $errors[] = "updateRole. Ошибка в наименование параметра во входных данных";
                    $status = 0;
                }
            } catch (\Exception $e) {
                $status = 0;
                $errors[] = "updateRole. Исключение: ";
                $errors[] = $e->getMessage();
                $errors[] = $e->getLine();
            }
        } else {
            $errors[] = "updateRole. Входной массив обязательных данных пуст. Имя пользователя не передано.";
            $status = 0;
        }

        $result_main = array('Items' => $result, 'id' => $role_model_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // delRole - метод удаления роли из бд
    //
    //  role_id - ключ роли
    //  search_query - фильтр поиска
    // выходной массив:
    //  стандартный набор данных
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Role&method=delRole&subscribe=login&data={"role_id":"1","search_query":""}
    //
    public static function delRole($data_post = null)
    {
        $session_amicum = Yii::$app->session;                                                                           //получаем текущую сессию пользователя
        $session_id = null;                                                                                             //уникальный идентификатор сессии
        $method_name = "delRole";                                                                                             //уникальный идентификатор сессии
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $role_model_id = null;
        try {
            if (!is_null($data_post) and $data_post != "") {
                $warnings[] = "delRole. данные успешно переданы";
                $warnings[] = "delRole. Входной массив данных" . $data_post;
            } else {
                throw new \Exception($method_name . '.  Ошибка в наименование параметра во входных данных');
            }

            $role = json_decode($data_post);                                                                      //декодируем входной массив данных
            $warnings[] = "delRole. декодировал входные параметры";
            if (!property_exists($role, 'role_id') or
                !property_exists($role, 'search_query'))   // и проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '.  Ошибка в наименование параметра во входных данных');
            }

            $warnings[] = "delRole. Проверил входные данные";

            $role_id = $role->role_id;
            $search_query = $role->search_query;

            $role_model = Role::find()->where(['id' => $role_id])->one();

            if ($role_model) {
                //сохранить тип подразделения
                if ($role_model->delete())   // и проверяем наличие в нем нужных нам полей
                {
                    $status *= 1;
                    $warnings[] = "delRole. Значение успешно удалено.";
                } else {
                    $errors[] = "delRole. Ошибка удаления модели Role";
                    $errors[] = $role_model->errors;
                    $status = 0;
                }
            } else {
                $status = 0;
                $errors[] = "delRole. Такого айди в базе не существует: " . $role_id;
            }

            $list_role = self::GetListRoleWorkersSearch(json_encode(array('search_query' => $search_query)));
            $result = $list_role['Items'];
            $status *= $list_role['status'];
            $warnings[] = $list_role['warnings'];
            $errors[] = $list_role['errors'];

        } catch (\Exception $e) {
            $status = 0;
            $errors[] = "delRole. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }


        $result_main = array('Items' => $result, 'id' => $role_model_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // deleteRole - метод удаления роли из бд
    // входные параметры:
    //  role_id - ключ роли
    // выходной массив:
    //  стандартный набор данных
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Role&method=deleteRole&subscribe=login&data={"role_id":"1"}
    //
    public static function deleteRole($data_post = null)
    {
        $method_name = "deleteRole";                                                                                             //уникальный идентификатор сессии
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        try {
            if (!is_null($data_post) and $data_post != "") {
                $warnings[] = "deleteRole. данные успешно переданы";
                $warnings[] = "deleteRole. Входной массив данных" . $data_post;
            } else {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $role = json_decode($data_post);                                                                      //декодируем входной массив данных
            $warnings[] = "deleteRole. декодировал входные параметры";
            if (!property_exists($role, 'role_id')) {
                throw new \Exception($method_name . '.  Ошибка в наименование параметра во входных данных');
            }

            $warnings[] = "deleteRole. Проверил входные данные";
            $role_id = $role->role_id;

            $result = Role::deleteAll(['id' => $role_id]);

        } catch (\Exception $e) {
            $status = 0;
            $errors[] = "deleteRole. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
