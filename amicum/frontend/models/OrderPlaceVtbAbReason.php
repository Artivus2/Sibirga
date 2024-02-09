<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_place_vtb_ab_reason".
 *
 * @property int $id
 * @property int $order_place_vtb_ab_id Внешний ключ наряда на место ВТБ АБ
 * @property string $reason Причина невыполнения наряда на место ВТБ АБ
 *
 * @property OrderPlaceVtbAb $orderPlaceVtbAb
 */
class OrderPlaceVtbAbReason extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_place_vtb_ab_reason';
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
            [['order_place_vtb_ab_id', 'reason'], 'required'],
            [['order_place_vtb_ab_id'], 'integer'],
            [['reason'], 'string', 'max' => 255],
            [['order_place_vtb_ab_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderPlaceVtbAb::className(), 'targetAttribute' => ['order_place_vtb_ab_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_place_vtb_ab_id' => 'Внешний ключ наряда на место ВТБ АБ',
            'reason' => 'Причина невыполнения наряда на место ВТБ АБ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlaceVtbAb()
    {
        return $this->hasOne(OrderPlaceVtbAb::className(), ['id' => 'order_place_vtb_ab_id']);
    }
}
