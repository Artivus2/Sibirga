<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "operation_parameters".
 *
 * @property int $id
 * @property int $operation_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property NominalOperationParameter[] $nominalOperationParameters
 * @property OperationParameterHandbookValue[] $operationParameterHandbookValues
 * @property OperationParameterValue[] $operationParameterValues
 * @property Operation $operation
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 */
class OperationParameters extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'operation_parameters';
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
            [['operation_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['operation_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['parameter_type_id', 'parameter_id', 'operation_id'], 'unique', 'targetAttribute' => ['parameter_type_id', 'parameter_id', 'operation_id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
            [['parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['parameter_id' => 'id']],
            [['parameter_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParameterType::className(), 'targetAttribute' => ['parameter_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'operation_id' => 'Operation ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalOperationParameters()
    {
        return $this->hasMany(NominalOperationParameter::className(), ['operation_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationParameterHandbookValues()
    {
        return $this->hasMany(OperationParameterHandbookValue::className(), ['operation_parameters_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationParameterValues()
    {
        return $this->hasMany(OperationParameterValue::className(), ['operation_parameters_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParameter()
    {
        return $this->hasOne(Parameter::className(), ['id' => 'parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParameterType()
    {
        return $this->hasOne(ParameterType::className(), ['id' => 'parameter_type_id']);
    }
}
