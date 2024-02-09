<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "place_parameter_sensor".
 *
 * @property int $id
 * @property int $place_parameter_id
 * @property int $sensor_id
 * @property string $date_time
 *
 * @property PlaceParameter $placeParameter
 */
class PlaceParameterSensor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'place_parameter_sensor';
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
            [['place_parameter_id', 'sensor_id', 'date_time'], 'required'],
            [['place_parameter_id', 'sensor_id'], 'integer'],
            [['date_time'], 'safe'],
            [['place_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlaceParameter::className(), 'targetAttribute' => ['place_parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'place_parameter_id' => 'Place Parameter ID',
            'sensor_id' => 'Sensor ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceParameter()
    {
        return $this->hasOne(PlaceParameter::className(), ['id' => 'place_parameter_id']);
    }
}
