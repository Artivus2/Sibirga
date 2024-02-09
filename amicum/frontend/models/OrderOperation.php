<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_operation".
 *
 * @property int $id Ключ таблицы плановых объемов работ по наряду
 * @property int $order_place_id внешний ключ привязки наряда к месту
 * @property int $operation_id внешний ключ справочника операций
 * @property string|null $operation_value_plan Плановое значение объема работы, которго должен выполнить работник
 * @property string|null $operation_value_fact Фактическоезначение объема работы, которго должен выполнить работник
 * @property int $status_id Внешний ключ спарвчоника статусов операции
 * @property string|null $description Описание операции (заполняется в отчёте)
 * @property int $equipment_id связка с оборудованием
 * @property int|null $order_operation_id_vtb ключ конкретной операции из наряда ВТБ
 * @property int|null $correct_measures_id ключ конкретной операции из предписания
 * @property int|null $order_place_id_vtb место в котором было выдан наряд ВТБ
 * @property string|null $coordinate координаты
 * @property int|null $edge_id
 * @property int|null $injunction_violation_id ключ привязки нарушенияк месту в наряде
 * @property int|null $injunction_id ключ привязки предписания к месту в наряде
 *
 * @property OperationWorker[] $operationWorkers
 * @property Status $status
 * @property Operation $operation
 * @property Equipment $equipment
 * @property OrderPlace $orderPlace
 * @property OrderOperationAttachment[] $orderOperationAttachments
 * @property OrderOperationImg[] $orderOperationImgs
 */
class OrderOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_operation';
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
            [['order_place_id', 'operation_id', 'status_id', 'equipment_id'], 'required'],
            [['order_place_id', 'operation_id', 'status_id', 'equipment_id', 'order_operation_id_vtb', 'correct_measures_id', 'order_place_id_vtb', 'edge_id', 'injunction_violation_id', 'injunction_id'], 'integer'],
            [['operation_value_plan', 'operation_value_fact'], 'string', 'max' => 45],
            [['description'], 'string', 'max' => 255],
            [['coordinate'], 'string', 'max' => 50],
            [['order_place_id', 'operation_id', 'equipment_id'], 'unique', 'targetAttribute' => ['order_place_id', 'operation_id', 'equipment_id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['order_place_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderPlace::className(), 'targetAttribute' => ['order_place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_place_id' => 'Order Place ID',
            'operation_id' => 'Operation ID',
            'operation_value_plan' => 'Operation Value Plan',
            'operation_value_fact' => 'Operation Value Fact',
            'status_id' => 'Status ID',
            'description' => 'Description',
            'equipment_id' => 'Equipment ID',
            'order_operation_id_vtb' => 'Order Operation Id Vtb',
            'correct_measures_id' => 'Correct Measures ID',
            'order_place_id_vtb' => 'Order Place Id Vtb',
            'coordinate' => 'Coordinate',
            'edge_id' => 'Edge ID',
            'injunction_violation_id' => 'Injunction Violation ID',
            'injunction_id' => 'Injunction ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationWorkers()
    {
        return $this->hasMany(OperationWorker::className(), ['order_operation_id' => 'id']);
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
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
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
    public function getOrderPlace()
    {
        return $this->hasOne(OrderPlace::className(), ['id' => 'order_place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationAttachments()
    {
        return $this->hasMany(OrderOperationAttachment::className(), ['order_operation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationImgs()
    {
        return $this->hasMany(OrderOperationImg::className(), ['order_operation_id' => 'id']);
    }
}
