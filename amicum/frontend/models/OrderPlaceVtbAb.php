<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_place_vtb_ab".
 *
 * @property int $id Идентификатор таблицы (автоинкрементный)
 * @property int $order_vtb_ab_id Внешний инеднтификатор наряда ВТБ АБ
 * @property int $place_id Внешний идентификатор места, на которое был выдан наряд
 * @property string description комментарий в наряде ВТБ
 *
 * @property OrderOperationPlaceVtbAb[] $orderOperationPlaceVtbAbs
 * @property OrderVtbAb $orderVtbAb
 * @property Place $place
 * @property OrderPlaceVtbAbReason[] $orderPlaceVtbAbReasons
 */
class OrderPlaceVtbAb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_place_vtb_ab';
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
            [['order_vtb_ab_id', 'place_id'], 'required'],
            [['order_vtb_ab_id', 'place_id'], 'integer'],
            [['description'], 'string', 'max' => 955],
            [['order_vtb_ab_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderVtbAb::className(), 'targetAttribute' => ['order_vtb_ab_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы (автоинкрементный)',
            'order_vtb_ab_id' => 'Внешний инеднтификатор наряда ВТБ АБ',
            'place_id' => 'Внешний идентификатор места, на которое был выдан наряд',
            'description' => 'комментарий в наряде ВТБ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationPlaceVtbAbs()
    {
        return $this->hasMany(OrderOperationPlaceVtbAb::className(), ['order_place_vtb_ab_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderVtbAb()
    {
        return $this->hasOne(OrderVtbAb::className(), ['id' => 'order_vtb_ab_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlaceVtbAbReasons()
    {
        return $this->hasOne(OrderPlaceVtbAbReason::className(), ['order_place_vtb_ab_id' => 'id']);
    }
}
