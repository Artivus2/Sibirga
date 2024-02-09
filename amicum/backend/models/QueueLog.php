<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%queue_log}}".
 *
 * @property int $id
 * @property string $request Какой метод был вызван
 * @property string $data Входные параметры метода 
 * @property string $queue_type Какой тип менеджера был выбран (sensor, edge, worker, equipment)
 * @property double $duration Время выполнения
 * @property string $errors Ошибки
 * @property string $request_result Результат выполнения метода
 * @property string $date_time Дата и время запуска
 */
class QueueLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%queue_log}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_amicum_log');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['request', 'queue_type', 'duration', 'errors'], 'required'],
            [['duration'], 'number'],
            [['request_result'], 'string'],
            [['date_time'], 'safe'],
            [['request', 'data', 'queue_type'], 'string', 'max' => 255],
            [['errors'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'request' => 'Какой метод был вызван',
            'data' => 'Входные параметры метода ',
            'queue_type' => 'Какой тип менеджера был выбран (sensor, edge, worker, equipment)',
            'duration' => 'Время выполнения',
            'errors' => 'Ошибки',
            'request_result' => 'Результат выполнения метода',
            'date_time' => 'Дата и время запуска',
        ];
    }

	/**
	 * Название метода: AddLog()
	 * Назначение метода: Метод добавления лог очередей в БД
	 * @param $controller - названеи контроллера с простраством имен
	 * @param $method  - метод
	 * @param $data - входные параметры
	 * @param $duration - длительность выполнения методов
	 * @param $result - результат выполенения метода
	 * @param $errors - ошибки возникщие при выполнении задачи
	 * @param $queue_type - тип менеджера очереди (edge, sensor, worker и тд.)

	 * @author Озармехр Одилов <ooy@pfsz.ru>
	 * Created date: on 21.05.2019 11:44
	 */
	public static function AddLog($controller, $method, $data,$duration, $result, $errors, $queue_type)
	{
		$queueLog = new self;
		$queueLog->request = $controller.$method;
		$queueLog->data = json_encode($data);
		$queueLog->duration = $duration;
		$queueLog->request_result = $result;
		$queueLog->queue_type = $queue_type;
		$queueLog->errors = json_encode($errors);
		$queueLog->save();
	}
}
