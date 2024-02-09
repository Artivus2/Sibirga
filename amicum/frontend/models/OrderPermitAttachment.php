<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_permit_attachment".
 *
 * @property int $id
 * @property int $order_permit_id ключ наряд допуска
 * @property int $attachment_id ключ вложения
 *
 * @property Attachment $attachment
 * @property OrderPermit $orderPermit
 */
class OrderPermitAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_permit_attachment';
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
            [['order_permit_id', 'attachment_id'], 'required'],
            [['order_permit_id', 'attachment_id'], 'integer'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['order_permit_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderPermit::className(), 'targetAttribute' => ['order_permit_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_permit_id' => 'Order Permit ID',
            'attachment_id' => 'Attachment ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPermit()
    {
        return $this->hasOne(OrderPermit::className(), ['id' => 'order_permit_id']);
    }
}
