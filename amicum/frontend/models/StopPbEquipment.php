<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "stop_pb_equipment".
 *
 * @property int $id
 * @property int $stop_pb_id Внешний ключ простоев
 * @property int $equipment_id Внешний ключ оборудований которые были остановлены
 *
 * @property Equipment $equipment
 * @property StopPb $stopPb
 */
class StopPbEquipment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stop_pb_equipment';
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
            [['stop_pb_id', 'equipment_id'], 'required'],
            [['stop_pb_id', 'equipment_id'], 'integer'],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['stop_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => StopPb::className(), 'targetAttribute' => ['stop_pb_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stop_pb_id' => 'Внешний ключ простоев',
            'equipment_id' => 'Внешний ключ оборудований которые были остановлены',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStopPb()
    {
        return $this->hasOne(StopPb::className(), ['id' => 'stop_pb_id']);
    }
}
