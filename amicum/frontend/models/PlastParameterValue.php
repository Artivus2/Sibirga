<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "plast_parameter_value".
 *
 * @property int $id
 * @property int $plast_parameter_id
 * @property string $value
 * @property string $date_time DATETIME(3)DATETIME(3)DATETIME(6)
 * @property int $status_id
 *
 * @property PlastParameter $plastParameter
 * @property Status $status
 */
class PlastParameterValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'plast_parameter_value';
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
            [['plast_parameter_id', 'value', 'date_time', 'status_id'], 'required'],
            [['plast_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['plast_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlastParameter::className(), 'targetAttribute' => ['plast_parameter_id' => 'id']],
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
            'plast_parameter_id' => 'Plast Parameter ID',
            'value' => 'Value',
            'date_time' => 'DATETIME(3)DATETIME(3)DATETIME(6)',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlastParameter()
    {
        return $this->hasOne(PlastParameter::className(), ['id' => 'plast_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
