<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_object_parameter_function".
 *
 * @property int $id
 * @property int $type_object_parameter_id
 * @property int $function_id
 * @property string $date_time
 *
 * @property Func $function
 * @property TypeObjectParameter $typeObjectParameter
 */
class TypeObjectParameterFunction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_object_parameter_function';
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
            [['type_object_parameter_id', 'function_id', 'date_time'], 'required'],
            [['type_object_parameter_id', 'function_id'], 'integer'],
            [['date_time'], 'safe'],
            [['function_id'], 'exist', 'skipOnError' => true, 'targetClass' => Func::className(), 'targetAttribute' => ['function_id' => 'id']],
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
            'function_id' => 'Function ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * Gets query for [[Function]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFunction()
    {
        return $this->hasOne(Func::className(), ['id' => 'function_id']);
    }

    /**
     * Gets query for [[TypeObjectParameter]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectParameter()
    {
        return $this->hasOne(TypeObjectParameter::className(), ['id' => 'type_object_parameter_id']);
    }
}
