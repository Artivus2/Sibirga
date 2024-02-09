<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "nominal_type_object_parameter".
 *
 * @property int $id
 * @property int $type_object_parameter_id
 * @property string $value_nominal
 * @property string $up_down "выше"/"ниже"/"равно"
 * @property int $status_id
 * @property int $event_id
 * @property string $date_nominal
 *
 * @property Event $event
 * @property Status $status
 * @property TypeObjectParameter $typeObjectParameter
 */
class NominalTypeObjectParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'nominal_type_object_parameter';
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
            [['type_object_parameter_id', 'value_nominal', 'up_down', 'status_id', 'event_id', 'date_nominal'], 'required'],
            [['type_object_parameter_id', 'status_id', 'event_id'], 'integer'],
            [['date_nominal'], 'safe'],
            [['value_nominal'], 'string', 'max' => 255],
            [['up_down'], 'string', 'max' => 10],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Event::className(), 'targetAttribute' => ['event_id' => 'id']],
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
            'value_nominal' => 'Value Nominal',
            'up_down' => '\"выше\"/\"ниже\"/\"равно\"',
            'status_id' => 'Status ID',
            'event_id' => 'Event ID',
            'date_nominal' => 'Date Nominal',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvent()
    {
        return $this->hasOne(Event::className(), ['id' => 'event_id']);
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
