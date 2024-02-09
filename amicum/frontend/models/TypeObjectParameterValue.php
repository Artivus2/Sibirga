<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_object_parameter_value".
 *
 * @property int $id
 * @property int $type_object_parameter_id
 * @property string $date_time DATETIME(3)DATETIME(6)f
 * @property string $value
 * @property int $status_id
 *
 * @property Status $status
 * @property TypeObjectParameter $typeObjectParameter
 */
class TypeObjectParameterValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_object_parameter_value';
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
            [['type_object_parameter_id', 'date_time', 'value', 'status_id'], 'required'],
            [['type_object_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['type_object_parameter_id', 'date_time'], 'unique', 'targetAttribute' => ['type_object_parameter_id', 'date_time']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'date_time' => 'Date Time',
            'value' => 'Value',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectParameter()
    {
        return $this->hasOne(TypeObjectParameter::className(), ['id' => 'type_object_parameter_id']);
    }
}
