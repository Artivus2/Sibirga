<?php
/**
 * Created by PhpStorm.
 * User: OzarOdilov
 * Date: 22.10.2018
 * Time: 9:20
 */

namespace frontend\controllers\positioningsystem;


use backend\controllers\Assistant;
use frontend\models\Main;
use yii\db\Query;
use yii\web\Controller;
use Yii;
class ObjectFunctions extends Controller
{
    /**
     * Метод добавления объектов.
     * Метод сначала создает объект в таблице Main
     * @param $object_table_name - название таблицы объекта
     * @param $object_columns - навания полей объекта (id не указывается, если идет добавление в Main)
     * @param $values - значения(количество значений должны совпадать с количеством полей объекта)
     * @return int|string - если успешно добавляется объект, то вернется id, иначе массив ошибок
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 22.10.2018 9:39
     */
    public static function AddObjectMain($object_table_name, $object_columns, $values, $table_address = 'no_table')
    {
        $flag_add = TRUE;
        $flag_id = false;
        $id_new = -1;
        /****************       СОЗДАНИЕ ОБЪЕКТА В ТАБЛИЦЕ Main     *******************/
        $id = self::AddEntryMain($table_address);
        if(is_array($id) === TRUE)                                                                                  //если вернулся массив ошибок, то вернем ошибку, иначе получаем id
        {
            $errors[] = "Ошибка создания Main";
            $errors['add-mine']  = $id;
            $flag_add = FALSE;
            return $errors;
        }
        else
        {
            $id_new = $id;
            $flag_id = true;
            $object_columns = "id, ".$object_columns;                                                               // если указано добавить mine, то необходимо id тоже добавить
            $values = $id.", ".$values;
        }
        /********************    Добавление объекта  ***********************************/
        if($flag_add === TRUE)                                                                                          // если main успешно был создан или же не был указан добавление в main
        {
            $id = self::InsertIntoTable($object_table_name, $object_columns, $values);
            if($flag_id == true)
            {
                return $id_new;
            }
            else return $id;
        }
    }

    /**
     * Метод для создания записи в таблице main
     * @param $tableAddress
     * @return array|int
     * Created by: Одилов О.У. on 22.10.2018 16:10
     */
    public static function AddEntryMain($tableAddress)
    {
        $errors = array();
        $model = new Main();//создаем новую запись в таблице main
        $model->table_address = $tableAddress;
        $model->db_address = "amicum2";
        if(!$model->save()){
            $errors[] = "Не удалось создать новый объект";
            $errors[] = $model->$errors;
            return $errors;
        }
        return (int) $model->id;
    }

    /**
     * Метод добавления параметров для конкретного объекта.
     * @param $object_table_name - название таблицы
     * @param $object_id - id конкретного объекта
     * @param $object_column_name - название колонки конкретного объекта (sensor_id, edge_id)
     * @param $parameter_id - id параметра
     * @param $parameter_type_id -  id типа параметра
     * @return array|int|string
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 23.10.2018 10:22
     */
    public static function AddObjectParameter($object_table_name, $object_id, $object_column_name, $parameter_id, $parameter_type_id)
    {
        $errors = array();
        $object_parameter_id = self::SearchObjectParameter($object_table_name, $object_id, $object_column_name, $parameter_id, $parameter_type_id);
        if($object_parameter_id == -1)                                                                                  //если не найден такой параметр у объекта, то добавим
        {
            $object_culumns_names = "$object_column_name, parameter_id, parameter_type_id";
            $values = "$object_id, $parameter_id, $parameter_type_id";
            $new_object_parameter_id = self::InsertIntoTable($object_table_name, $object_culumns_names, $values);
            return $new_object_parameter_id;
        }

        return $object_parameter_id;
    }

    /**
     * Метод проверки параметр объекта.
     * Если параметр для конкретного объекта найдется, то вернется id параметра объекта
     * Если параметр ддля конкретного объекта не найдется, то вернет -1
     * @param $object_table_name  - название таблицы
     * @param $object_id - id конкретного объекта
     * @param $object_column_name - название колонки конкретного объекта (sensor_id, edge_id)
     * @param $parameter_id - id параметра
     * @param $parameter_type_id - id типа параметра
     * @return int id объекта если есть, иначе -1
     * Created by: Одилов О.У. on 22.10.2018 17:26
     */
    public static function SearchObjectParameter($object_table_name, $object_id, $object_column_name, $parameter_id, $parameter_type_id)
    {
        $sql_filter = "$object_column_name = $object_id and parameter_id = $parameter_id and parameter_type_id = $parameter_type_id"; // условие по которой
        $is_exists_object_parameter = (new Query())
            ->select('id')
            ->from($object_table_name)
            ->where($sql_filter)
            ->one();
        if(!$is_exists_object_parameter)
        {
            return -1;
        }
        else
        {
            $object_parameter_id = $is_exists_object_parameter['id'];
            return $object_parameter_id;
        }
    }


    /**
     * @param $object_table_name - название таблицы (полностью указать НЕЛЬЗЯ), метод сам добавляет ParameterHandbookValue
     * @param $object_parameter_id - id параметр конкретного объекта
     * @param $date_time - дата/время. Если указать 1, то автоматически став. тек дата и время
     * @param $value - значение
     * @param $status_id - статус
     * @return array|int|string если все успешно, то id, иначе массив ошибок
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 23.10.2018 10:33
     */
    public static function AddObjectParameterHandbookValue($object_table_name, $object_parameter_id, $date_time, $value, $status_id)
    {
        $object_column_name = $object_table_name."_parameter_id";
        $object_table_name .= "_parameter_handbook_value";
        if($date_time == 1)
        {
            $date_time = Assistant::GetDateNow();
        }
        $values = ("$object_parameter_id, '$date_time', '$value', $status_id");
        $object_columns = $object_column_name.", date_time, value, status_id";
        $id = self::InsertIntoTable($object_table_name, $object_columns, $values);
        return $id;
    }

    /**
     * Метод добавления несправочных значений для обьекта
     * @param $object_table_name - название таблицы (полностью указать НЕЛЬЗЯ), метод сам добавляет ParameterValue
     * @param $object_parameter_id - id параметр конкретного объекта
     * @param $date_time - дата/время. Если указать 1, то автоматически став. тек дата и время
     * @param $value - значение
     * @param $status_id - статус
     * @return array|int|string если все успешно, то id, иначе массив ошибок
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 23.10.2018 10:33
     */
    public static function AddObjectParameterValue($object_table_name, $object_parameter_id, $date_time, $value, $status_id)
    {
        $object_column_name = $object_table_name."_parameter_id";
        $object_table_name .= "_parameter_value";
        if($date_time == 1)
        {
            $date_time = date("Y-m-d H:i:s");
        }
        $values = ("$object_parameter_id, '$date_time', '$value', $status_id");
        $object_columns = $object_column_name.", date_time, value, status_id";
        $id = self::InsertIntoTable($object_table_name, $object_columns, $values);
        return $id;
    }

    /**
     * Метод добавления данных в БД
     * @param $object_table_name - название таблицы
     * @param $object_columns - поля таблицы
     * @param $values - значения через запятую (количесство значений должны совпадать с количество полей таблицы)
     * @return int|string - при успешном добавления, вернем добавленыый id, иначе ошибку
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 23.10.2018 10:06
     */
    public static function InsertIntoTable($object_table_name, $object_columns, $values)
    {
        try
        {
            $sql = "INSERT INTO $object_table_name ($object_columns) VALUES ($values)";
            $sql_query = Yii::$app->db->createCommand($sql)->execute();
            if($sql_query)
            {
//            $id = Yii::$app->db->getLastInsertId('');
                return Yii::$app->db->createCommand("SELECT max(id) FROM $object_table_name")->queryOne();
            }
            else
            {
                return -1;
            }
        }
        catch (\Throwable $ex)
        {
            return -1;
        }

    }

    /**
     * Метод удаления данных из БД
     * @param $table_name - название таблицы
     * @param $condition - условие по котору будуб удалены данные
     * @return int - результат выполнения
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 23.10.2018 13:46
     */
    public static function DeleteFromTable($table_name, $condition)
    {
        $sql = "DELETE FROM $table_name WHERE $condition";
        $delete_result = Yii::$app->db->createCommand($sql)->execute();
        return $delete_result;
    }

    /**
     * Метод добавления функции для объекта
     * @param $table_name
     * @param $object_id
     * @param $function_id
     * @return int - Возвращает id добавленной функции, если ошибка, то -1;
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 23.10.2018 14:55
     */
    public static function AddObjectFunction($table_name, $object_id, $function_id)
    {
        $object_column_name = $table_name."_id";
        $table_name .= "_function";
        $function_old_id = self::SearchOne($table_name, "$object_column_name = $object_id and function_id = $function_id");

        if($function_old_id == -1)                                                                                          // если данные не найдены, то добавим функцию
        {
            $insert_result = self::InsertIntoTable($table_name, "$object_column_name, function_id", "$object_id, $function_id");
            return $insert_result;
        }
        else
        {
            $function_old_id = $function_old_id['id'];
            return $function_old_id;
        }
    }

    /**
     * Метод поиска дынных по БД (поиск одной конкреной записи)
     * @param $table_name
     * @param $condition
     * @return int - массив данных найденного объекта, иначе -1;
     * Created by: Одилов О.У. on 23.10.2018 14:46
     */
    public static function SearchOne($table_name, $condition)
    {
        $sql = (new Query())
            ->select('*')
            ->from($table_name)
            ->where($condition)
            ->one();
        if($sql)
        {
            return $sql;
        }
        else
        {
            return -1;
        }
    }

    /**
     * Метод редактирования данных
     * @param $table_name - название таблицы
     * @param $set_values - поля и значения
     * @param $condition - условие
     * @return int - результат выполнения запроса
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 26.10.2018 15:26
     */
    public static function SqlUpdate($table_name, $set_values, $condition)
    {
        $sql = "UPDATE $table_name SET $set_values  WHERE $condition";
        $execute_result = Yii::$app->db->createCommand($sql)->execute();
        return $execute_result;
    }

    /**
     * Метод получения значения параметра конкретного объекта (Сенсор, работник, выработка) за определенный период времени
     * Возвращает данные параметра какого-то конкретного объекта в промежуток какого-то времени.
     * Получает данные из object_parameter_handbook_value или object_parameter_value в  зависимости от типа параметра.
     * Выборка идет через процедуру  GetObjectValues();
     * @param $specific_object_name - название объекта (только название (sensor, edge, worker и тд, а не worker_parameter_value)
     * @param $specific_object_id - id конкретного обекта
     * @param $parameter_id - параметр
     * @param $parameter_type_id - тип параметра
     * @param $date_start - дата начало
     * @param $date_end - дата конец
     * @return array - массив данных
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 28.11.2018 11:21
     */
    public static function GetObjectValues($specific_object_name, $specific_object_id, $parameter_id, $parameter_type_id, $date_start, $date_end)
    {
        $date_format = "Y-m-d H:i:s";
/*        $date_start = date($date_format, strtotime($date_start ." -1 days"));
        $date_end = date($date_format, strtotime($date_end ." +1 days"));*/
        $sql = "CALL GetObjectValues('$specific_object_name', $specific_object_id, $parameter_id, $parameter_type_id, '$date_start', '$date_end')";
        $object_values = Yii::$app->db->createCommand($sql)->queryAll();
        return $object_values;
    }
}