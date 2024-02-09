<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "planogramma".
 *
 * @property int $id Идентификатор таблицы планограммы (автоинкрементный)
 * @property int $order_id Внешний идентификатор наряда
 * @property string $date_time_start Дата начала планограммы
 * @property string $date_time_end Дата окончания планограммы
 * @property int $cyclegramm_type_id Тип планограммы
 * @property int $equipment_id Внешний идентификатор оборудования
 *
 * @property PlanogrammOperation[] $planogrammOperations
 * @property CyclegrammType $cyclegrammType
 * @property Equipment $equipment
 * @property Order $order
 */
class Planogramma extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'planogramma';
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
            [['order_id', 'date_time_start', 'date_time_end', 'cyclegramm_type_id', 'equipment_id'], 'required'],
            [['order_id', 'cyclegramm_type_id', 'equipment_id'], 'integer'],
            [['date_time_start', 'date_time_end'], 'safe'],
            [['cyclegramm_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => CyclegrammType::className(), 'targetAttribute' => ['cyclegramm_type_id' => 'id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'date_time_start' => 'Date Time Start',
            'date_time_end' => 'Date Time End',
            'cyclegramm_type_id' => 'Cyclegramm Type ID',
            'equipment_id' => 'Equipment ID',
        ];
    }

    /**
     * Gets query for [[PlanogrammOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlanogrammOperations()
    {
        return $this->hasMany(PlanogrammOperation::className(), ['planogramma_id' => 'id']);
    }

    /**
     * Gets query for [[CyclegrammType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCyclegrammType()
    {
        return $this->hasOne(CyclegrammType::className(), ['id' => 'cyclegramm_type_id']);
    }

    /**
     * Gets query for [[Equipment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
    }

    /**
     * Gets query for [[Order]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }
}
