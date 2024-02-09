<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "conjunction_parameter_sensor".
 *
 * @property int $id
 * @property int $conjunction_parameter_id
 * @property int $sensor_id
 * @property string $date_time
 *
 * @property ConjunctionParameter $conjunctionParameter
 * @property Sensor $sensor
 */
class ConjunctionParameterSensor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conjunction_parameter_sensor';
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
            [['conjunction_parameter_id', 'sensor_id', 'date_time'], 'required'],
            [['conjunction_parameter_id', 'sensor_id'], 'integer'],
            [['date_time'], 'safe'],
            [['conjunction_parameter_id', 'sensor_id', 'date_time'], 'unique', 'targetAttribute' => ['conjunction_parameter_id', 'sensor_id', 'date_time']],
            [['conjunction_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => ConjunctionParameter::className(), 'targetAttribute' => ['conjunction_parameter_id' => 'id']],
            [['sensor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sensor::className(), 'targetAttribute' => ['sensor_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'conjunction_parameter_id' => 'Conjunction Parameter ID',
            'sensor_id' => 'Sensor ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctionParameter()
    {
        return $this->hasOne(ConjunctionParameter::className(), ['id' => 'conjunction_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensor()
    {
        return $this->hasOne(Sensor::className(), ['id' => 'sensor_id']);
    }
}
