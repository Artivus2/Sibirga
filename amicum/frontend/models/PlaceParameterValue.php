<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "place_parameter_value".
 *
 * @property int $id
 * @property int $place_parameter_id
 * @property string $value
 * @property string $date_time DATETIME(3)DATETIME(3)DATETIME(6)
 * @property int $status_id
 *
 * @property PlaceParameter $placeParameter
 * @property Status $status
 */
class PlaceParameterValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'place_parameter_value';
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
            [['place_parameter_id', 'value', 'date_time', 'status_id'], 'required'],
            [['place_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['place_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlaceParameter::className(), 'targetAttribute' => ['place_parameter_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'value' => 'Value',
            'date_time' => 'DATETIME(3)DATETIME(3)DATETIME(6)',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceParameter()
    {
        return $this->hasOne(PlaceParameter::className(), ['id' => 'place_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
