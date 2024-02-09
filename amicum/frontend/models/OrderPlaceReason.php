<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_place_reason".
 *
 * @property int $id
 * @property int $order_place_id
 * @property string $reason
 *
 * @property OrderPlace $orderPlace
 */
class OrderPlaceReason extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_place_reason';
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
            [['order_place_id', 'reason'], 'required'],
            [['order_place_id'], 'integer'],
            [['reason'], 'string', 'max' => 255],
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
            'reason' => 'Reason',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlace()
    {
        return $this->hasOne(OrderPlace::className(), ['id' => 'order_place_id']);
    }
}
