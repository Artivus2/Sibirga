<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_cyclegramm_lava".
 *
 * @property int $id ключ таблицы циклограммы
 * @property int $order_place_id внешний ключ привязки наряда к месту
 * @property int $section_number Номер секции крепи на которой находится комбайн
 * @property string $date_time дата время нахождения комбайна на заданной секции
 * @property string $cyclegramm_type Тип циклограммы. Если п, то план. Если ф, то факт.
 * @property int $type_operation_id внешний ключ типов операций (план/факт)
 *
 * @property OrderPlace $orderPlace
 * @property TypeOperation $typeOperation
 */
class OrderCyclegrammLava extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_cyclegramm_lava';
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
            [['id', 'order_place_id', 'date_time', 'cyclegramm_type', 'type_operation_id'], 'required'],
            [['id', 'order_place_id', 'section_number', 'type_operation_id'], 'integer'],
            [['date_time'], 'safe'],
            [['cyclegramm_type'], 'string', 'max' => 1],
            [['id'], 'unique'],
            [['order_place_id', 'date_time', 'cyclegramm_type', 'type_operation_id'], 'unique', 'targetAttribute' => ['order_place_id', 'date_time', 'cyclegramm_type', 'type_operation_id']],
            [['order_place_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderPlace::className(), 'targetAttribute' => ['order_place_id' => 'id']],
            [['type_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypeOperation::className(), 'targetAttribute' => ['type_operation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ таблицы циклограммы',
            'order_place_id' => 'внешний ключ привязки наряда к месту',
            'section_number' => 'Номер секции крепи на которой находится комбайн',
            'date_time' => 'дата время нахождения комбайна на заданной секции',
            'cyclegramm_type' => 'Тип циклограммы. Если п, то план. Если ф, то факт.',
            'type_operation_id' => 'внешний ключ типов операций (план/факт)',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlace()
    {
        return $this->hasOne(OrderPlace::className(), ['id' => 'order_place_id']);
    }

    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id'])->via('orderPlace');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeOperation()
    {
        return $this->hasOne(TypeOperation::className(), ['id' => 'type_operation_id']);
    }
}
