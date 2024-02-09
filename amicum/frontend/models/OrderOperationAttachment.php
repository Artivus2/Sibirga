<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_operation_attachment".
 *
 * @property int $id
 * @property int $order_operation_id
 * @property int $attachment_id
 *
 * @property OrderOperation $orderOperation
 * @property Attachment $attachment
 */
class OrderOperationAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_operation_attachment';
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
            [['order_operation_id', 'attachment_id'], 'required'],
            [['order_operation_id', 'attachment_id'], 'integer'],
            [['order_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderOperation::className(), 'targetAttribute' => ['order_operation_id' => 'id']],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_operation_id' => 'Order Operation ID',
            'attachment_id' => 'Attachment ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperation()
    {
        return $this->hasOne(OrderOperation::className(), ['id' => 'order_operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id']);
    }
}
