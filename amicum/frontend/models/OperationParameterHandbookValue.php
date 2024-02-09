<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "operation_parameter_handbook_value".
 *
 * @property int $id
 * @property int $operation_parameters_id
 * @property string $date_time
 * @property string $value
 * @property int $status_id
 *
 * @property OperationParameters $operationParameters
 * @property Status $status
 */
class OperationParameterHandbookValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'operation_parameter_handbook_value';
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
            [['operation_parameters_id', 'date_time', 'value', 'status_id'], 'required'],
            [['operation_parameters_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['operation_parameters_id'], 'exist', 'skipOnError' => true, 'targetClass' => OperationParameters::className(), 'targetAttribute' => ['operation_parameters_id' => 'id']],
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
            'operation_parameters_id' => 'Operation Parameters ID',
            'date_time' => 'Date Time',
            'value' => 'Value',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationParameters()
    {
        return $this->hasOne(OperationParameters::className(), ['id' => 'operation_parameters_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
