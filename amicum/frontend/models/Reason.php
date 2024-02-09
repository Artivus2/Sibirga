<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "reason".
 *
 * @property int $id Идентификатор самой таблицы (автоинкрементный)
 * @property int $downtime_id Уникальный идентификатор простоя
 * @property int $equipment_section_id Уникальный идентификатор секции оборудования
 * @property int $worker_guilty_id Уникальный идентификатор виновного работника
 *
 * @property Downtime $downtime
 * @property EquipmentSection $equipmentSection
 * @property Worker $workerGuilty
 */
class Reason extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'reason';
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
            [['downtime_id', 'equipment_section_id', 'worker_guilty_id'], 'required'],
            [['downtime_id', 'equipment_section_id', 'worker_guilty_id'], 'integer'],
            [['downtime_id'], 'exist', 'skipOnError' => true, 'targetClass' => Downtime::className(), 'targetAttribute' => ['downtime_id' => 'id']],
            [['equipment_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => EquipmentSection::className(), 'targetAttribute' => ['equipment_section_id' => 'id']],
            [['worker_guilty_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_guilty_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор самой таблицы (автоинкрементный)',
            'downtime_id' => 'Уникальный идентификатор простоя',
            'equipment_section_id' => 'Уникальный идентификатор секции оборудования',
            'worker_guilty_id' => 'Уникальный идентификатор виновного работника',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDowntime()
    {
        return $this->hasOne(Downtime::className(), ['id' => 'downtime_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentSection()
    {
        return $this->hasOne(EquipmentSection::className(), ['id' => 'equipment_section_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerGuilty()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_guilty_id']);
    }
}
