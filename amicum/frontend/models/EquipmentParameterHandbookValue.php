<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "equipment_parameter_handbook_value".
 *
 * @property int $id
 * @property int $equipment_parameter_id
 * @property string $date_time DATETIME(3)DATETIME(6)r
 * @property string $value
 * @property int $status_id
 *
 * @property EquipmentParameter $equipmentParameter
 * @property Status $status
 */
class EquipmentParameterHandbookValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'equipment_parameter_handbook_value';
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
            [['equipment_parameter_id', 'date_time', 'value', 'status_id'], 'required'],
            [['equipment_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['equipment_parameter_id', 'date_time'], 'unique', 'targetAttribute' => ['equipment_parameter_id', 'date_time']],
            [['equipment_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => EquipmentParameter::className(), 'targetAttribute' => ['equipment_parameter_id' => 'id']],
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
            'equipment_parameter_id' => 'Equipment Parameter ID',
            'date_time' => 'DATETIME(3)DATETIME(6)r',
            'value' => 'Value',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentParameter()
    {
        return $this->hasOne(EquipmentParameter::className(), ['id' => 'equipment_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
