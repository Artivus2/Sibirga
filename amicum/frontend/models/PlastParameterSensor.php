<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "plast_parameter_sensor".
 *
 * @property int $id
 * @property int $plast_parameter_id
 * @property int $sensor_id
 * @property string $date_time
 *
 * @property PlastParameter $plastParameter
 */
class PlastParameterSensor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'plast_parameter_sensor';
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
            [['plast_parameter_id', 'sensor_id', 'date_time'], 'required'],
            [['plast_parameter_id', 'sensor_id'], 'integer'],
            [['date_time'], 'safe'],
            [['plast_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlastParameter::className(), 'targetAttribute' => ['plast_parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'plast_parameter_id' => 'Plast Parameter ID',
            'sensor_id' => 'Sensor ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlastParameter()
    {
        return $this->hasOne(PlastParameter::className(), ['id' => 'plast_parameter_id']);
    }
}
