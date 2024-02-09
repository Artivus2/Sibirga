<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "function_parameter".
 *
 * @property int $id
 * @property int $function_id
 * @property int $parameter_id
 * @property string $parameter_type in/out
 * @property int $ordinal_number
 * @property int $parameter_type_id
 *
 * @property Func $function
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 */
class FunctionParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'function_parameter';
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
            [['function_id', 'parameter_id', 'parameter_type', 'ordinal_number'], 'required'],
            [['function_id', 'parameter_id', 'ordinal_number', 'parameter_type_id'], 'integer'],
            [['parameter_type'], 'string', 'max' => 3],
            [['function_id'], 'exist', 'skipOnError' => true, 'targetClass' => Func::className(), 'targetAttribute' => ['function_id' => 'id']],
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
            'function_id' => 'Function ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type' => 'in/out',
            'ordinal_number' => 'Ordinal Number',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFunction()
    {
        return $this->hasOne(Func::className(), ['id' => 'function_id']);
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
