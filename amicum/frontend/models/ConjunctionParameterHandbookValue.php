<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "conjunction_parameter_handbook_value".
 *
 * @property int $id
 * @property int $conjunction_parameter_id
 * @property string $date_time DATETIME(6)v
 * @property string $value f
 * @property int $status_id
 *
 * @property ConjunctionParameter $conjunctionParameter
 * @property Status $status
 */
class ConjunctionParameterHandbookValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conjunction_parameter_handbook_value';
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
            [['conjunction_parameter_id', 'date_time', 'value', 'status_id'], 'required'],
            [['conjunction_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['conjunction_parameter_id', 'date_time'], 'unique', 'targetAttribute' => ['conjunction_parameter_id', 'date_time']],
            [['conjunction_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => ConjunctionParameter::className(), 'targetAttribute' => ['conjunction_parameter_id' => 'id']],
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
            'conjunction_parameter_id' => 'Conjunction Parameter ID',
            'date_time' => 'DATETIME(6)v',
            'value' => 'f',
            'status_id' => 'Status ID',
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
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
