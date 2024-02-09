<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_item_injunction".
 *
 * @property int $id
 * @property int $injunction_id ключ предписания
 * @property int $status_id ключ статуса предписания
 * @property int $order_history_id ключ истории сохранения наряда
 * @property string|null $injunctions_json объект предписания при сохранении
 *
 * @property Injunction $injunction
 * @property OrderHistory $orderHistory
 * @property Status $status
 */
class OrderItemInjunction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_item_injunction';
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
            [['injunction_id', 'status_id', 'order_history_id'], 'required'],
            [['injunction_id', 'status_id', 'order_history_id'], 'integer'],
            [['injunctions_json'], 'safe'],
            [['injunction_id', 'status_id', 'order_history_id'], 'unique', 'targetAttribute' => ['injunction_id', 'status_id', 'order_history_id']],
            [['injunction_id'], 'exist', 'skipOnError' => true, 'targetClass' => Injunction::className(), 'targetAttribute' => ['injunction_id' => 'id']],
            [['order_history_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderHistory::className(), 'targetAttribute' => ['order_history_id' => 'id']],
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
            'injunction_id' => 'Injunction ID',
            'status_id' => 'Status ID',
            'order_history_id' => 'Order History ID',
            'injunctions_json' => 'Injunctions Json',
        ];
    }

    /**
     * Gets query for [[Injunction]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInjunction()
    {
        return $this->hasOne(Injunction::className(), ['id' => 'injunction_id']);
    }

    /**
     * Gets query for [[OrderHistory]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderHistory()
    {
        return $this->hasOne(OrderHistory::className(), ['id' => 'order_history_id']);
    }

    /**
     * Gets query for [[Status]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
