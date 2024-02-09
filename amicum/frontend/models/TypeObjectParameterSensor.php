<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_object_parameter_sensor".
 *
 * @property int $id
 * @property int $type_object_parameter_id
 * @property int $sensor_id
 * @property string $date_time
 *
 * @property TypeObjectParameter $typeObjectParameter
 */
class TypeObjectParameterSensor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_object_parameter_sensor';
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
            [['type_object_parameter_id', 'sensor_id', 'date_time'], 'required'],
            [['type_object_parameter_id', 'sensor_id'], 'integer'],
            [['date_time'], 'safe'],
            [['type_object_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypeObjectParameter::className(), 'targetAttribute' => ['type_object_parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type_object_parameter_id' => 'Type Object Parameter ID',
            'sensor_id' => 'Sensor ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectParameter()
    {
        return $this->hasOne(TypeObjectParameter::className(), ['id' => 'type_object_parameter_id']);
    }
}
