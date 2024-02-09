<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_template_operation".
 *
 * @property int $id Ключ таблицы плановых объемов работ по наряду
 * @property int $order_template_place_id внешний ключ привязки шаблона наряда к месту операции
 * @property int $operation_id внешний ключ справочника операций
 * @property string $operation_value_plan Плановое значение объема работы, которго должен выполнить работник
 * @property string $operation_value_fact Фактическоезначение объема работы, которго должен выполнить работник
 * @property int $status_id Внешний ключ спарвчоника статусов операции
 * @property string $description Описание операции (заполняется в отчёте)
 * @property int $equipment_id
 * @property string $coordinate координаты
 * @property int $edge_id
 *
 * @property Status $status
 * @property Operation $operation
 * @property OrderTemplatePlace $orderTemplatePlace
 */
class OrderTemplateOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_template_operation';
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
            [['order_template_place_id', 'operation_id', 'status_id', 'equipment_id'], 'required'],
            [['order_template_place_id', 'operation_id', 'status_id', 'equipment_id', 'edge_id'], 'integer'],
            [['operation_value_plan', 'operation_value_fact'], 'string', 'max' => 45],
            [['description'], 'string', 'max' => 255],
            [['coordinate'], 'string', 'max' => 50],
            [['order_template_place_id', 'operation_id', 'equipment_id'], 'unique', 'targetAttribute' => ['order_template_place_id', 'operation_id', 'equipment_id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
            [['order_template_place_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderTemplatePlace::className(), 'targetAttribute' => ['order_template_place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Ключ таблицы плановых объемов работ по наряду',
            'order_template_place_id' => 'внешний ключ привязки шаблона наряда к месту операции',
            'operation_id' => 'внешний ключ справочника операций',
            'operation_value_plan' => 'Плановое значение объема работы, которго должен выполнить работник',
            'operation_value_fact' => 'Фактическоезначение объема работы, которго должен выполнить работник',
            'status_id' => 'Внешний ключ спарвчоника статусов операции',
            'description' => 'Описание операции (заполняется в отчёте)',
            'equipment_id' => 'Equipment ID',
            'coordinate' => 'координаты',
            'edge_id' => 'Edge ID',
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
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplatePlace()
    {
        return $this->hasOne(OrderTemplatePlace::className(), ['id' => 'order_template_place_id']);
    }
}
