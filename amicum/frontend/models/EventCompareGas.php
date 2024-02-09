<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event_compare_gas".
 *
 * @property int $id ключ журнала
 * @property int $event_id Само событие
 * @property string $date_time Дата и время добавления параметра
 * @property int|null $static_edge_id ветвь на котором находится стационарный сенсор
 * @property string|null $static_value Значение параметра стационарного сенсора
 * @property int|null $static_sensor_id ключ сенсора стационарного
 * @property string|null $static_xyz Значение параметра координат стационарного сенсора
 * @property int|null $static_status_id статус стационарного сенсора в момент сравнения показаний
 * @property int|null $static_parameter_id параметр значения стационарного сенсора
 * @property int|null $static_object_id ключ типового объекта стационарного сенсора
 * @property int|null $static_mine_id ключ шахты стационарного сенсора
 * @property string|null $static_object_title название стационарного сенсора
 * @property string|null $static_object_table таблица в которой находится стационарный сенсор
 * @property int $lamp_sensor_id ключ лампы
 * @property int|null $lamp_edge_id ключ ветви лампы, в которой было зарегистрировано событие
 * @property string|null $lamp_value Значение параметра лампы
 * @property string|null $lamp_xyz Значение параметра координат лампы
 * @property int|null $lamp_status_id статус лампы в момент регистрации события
 * @property int|null $lamp_parameter_id параметр значения лампы 
 * @property int|null $lamp_object_id ключ типового объекта лампы
 * @property int|null $lamp_mine_id ключ шахты лампы в которой было зарегистрировано событие
 * @property string|null $lamp_object_title название лампы
 * @property string|null $lamp_object_table название таблицы в которой находится лампа
 * @property int|null $static_event_journal_id ключ из журнала событий по стационарному датчику / если был
 * @property int|null $lamp_event_journal_id ключ из журнала событий по лампе
 *
 * @property Edge $lampEdge
 * @property Edge $staticEdge
 * @property Mine $lampMine
 * @property Mine $staticMine
 * @property Parameter $lampParameter
 * @property Parameter $staticParameter
 */
class EventCompareGas extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_compare_gas';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_amicum2');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['event_id', 'date_time', 'lamp_sensor_id'], 'required'],
            [['event_id', 'static_edge_id', 'static_sensor_id', 'static_status_id', 'static_parameter_id', 'static_object_id', 'static_mine_id', 'lamp_sensor_id', 'lamp_edge_id', 'lamp_status_id', 'lamp_parameter_id', 'lamp_object_id', 'lamp_mine_id', 'static_event_journal_id', 'lamp_event_journal_id'], 'integer'],
            [['date_time'], 'safe'],
            [['static_value', 'static_object_table', 'lamp_value', 'lamp_object_table'], 'string', 'max' => 45],
            [['static_xyz', 'lamp_xyz'], 'string', 'max' => 55],
            [['static_object_title', 'lamp_object_title'], 'string', 'max' => 61],
            [['lamp_edge_id'], 'exist', 'skipOnError' => true, 'targetClass' => Edge::className(), 'targetAttribute' => ['lamp_edge_id' => 'id']],
            [['static_edge_id'], 'exist', 'skipOnError' => true, 'targetClass' => Edge::className(), 'targetAttribute' => ['static_edge_id' => 'id']],
            [['lamp_mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['lamp_mine_id' => 'id']],
            [['static_mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['static_mine_id' => 'id']],
            [['lamp_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['lamp_parameter_id' => 'id']],
            [['static_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['static_parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'event_id' => 'Event ID',
            'date_time' => 'Date Time',
            'static_edge_id' => 'Static Edge ID',
            'static_value' => 'Static Value',
            'static_sensor_id' => 'Static Sensor ID',
            'static_xyz' => 'Static Xyz',
            'static_status_id' => 'Static Status ID',
            'static_parameter_id' => 'Static Parameter ID',
            'static_object_id' => 'Static Object ID',
            'static_mine_id' => 'Static Mine ID',
            'static_object_title' => 'Static Object Title',
            'static_object_table' => 'Static Object Table',
            'lamp_sensor_id' => 'Lamp Sensor ID',
            'lamp_edge_id' => 'Lamp Edge ID',
            'lamp_value' => 'Lamp Value',
            'lamp_xyz' => 'Lamp Xyz',
            'lamp_status_id' => 'Lamp Status ID',
            'lamp_parameter_id' => 'Lamp Parameter ID',
            'lamp_object_id' => 'Lamp Object ID',
            'lamp_mine_id' => 'Lamp Mine ID',
            'lamp_object_title' => 'Lamp Object Title',
            'lamp_object_table' => 'Lamp Object Table',
            'static_event_journal_id' => 'Static Event Journal ID',
            'lamp_event_journal_id' => 'Lamp Event Journal ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLampEdge()
    {
        return $this->hasOne(Edge::className(), ['id' => 'lamp_edge_id'])->alias('lamp_edge');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaticEdge()
    {
        return $this->hasOne(Edge::className(), ['id' => 'static_edge_id'])->alias('static_edge');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLampMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'lamp_mine_id'])->alias('lamp_mine');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaticMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'static_mine_id'])->alias('static_mine');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLampParameter()
    {
        return $this->hasOne(Parameter::className(), ['id' => 'lamp_parameter_id'])->alias('lamp_parameter');
    }

    /**

     */
    public function getStaticParameter()
    {
        return $this->hasOne(Parameter::className(), ['id' => 'static_parameter_id'])->alias('static_parameter');
    }
}
