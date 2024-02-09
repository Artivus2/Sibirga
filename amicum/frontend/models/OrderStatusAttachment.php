<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_status_attachment".
 *
 * @property int $id
 * @property int $order_status_id идентификатор статуса наряда
 * @property string $attachment_path путь вложения
 *
 * @property OrderStatus $orderStatus
 */
class OrderStatusAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_status_attachment';
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
            [['order_status_id', 'attachment_path'], 'required'],
            [['order_status_id'], 'integer'],
            [['attachment_path'], 'string', 'max' => 255],
            [['order_status_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderStatus::className(), 'targetAttribute' => ['order_status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_status_id' => 'Order Status ID',
            'attachment_path' => 'Attachment Path',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderStatus()
    {
        return $this->hasOne(OrderStatus::className(), ['id' => 'order_status_id']);
    }
}
