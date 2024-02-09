<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_by_chane".
 *
 * @property int $id
 * @property string $title
 * @property int $order_id
 * @property int $face_id
 * @property int $brigade_id
 * @property int $chainer_fact_id
 * @property int $object_id
 *
 * @property ChaneFact[] $chaneFacts
 * @property Brigade $brigade
 * @property Face $face
 * @property Object $object
 * @property Order $order
 * @property Worker $chainerFact
 * @property OrderByChaneEquipment[] $orderByChaneEquipments
 * @property OrderByChaneGroupOperation[] $orderByChaneGroupOperations
 */
class OrderByChane extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_by_chane';
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
            [['title', 'order_id', 'face_id', 'brigade_id', 'chainer_fact_id', 'object_id'], 'required'],
            [['order_id', 'face_id', 'brigade_id', 'chainer_fact_id', 'object_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['brigade_id'], 'exist', 'skipOnError' => true, 'targetClass' => Brigade::className(), 'targetAttribute' => ['brigade_id' => 'id']],
            [['face_id'], 'exist', 'skipOnError' => true, 'targetClass' => Face::className(), 'targetAttribute' => ['face_id' => 'id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
            [['chainer_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['chainer_fact_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'order_id' => 'Order ID',
            'face_id' => 'Face ID',
            'brigade_id' => 'Brigade ID',
            'chainer_fact_id' => 'Chainer Fact ID',
            'object_id' => 'Object ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChaneFacts()
    {
        return $this->hasMany(ChaneFact::className(), ['order_by_chane_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigade()
    {
        return $this->hasOne(Brigade::className(), ['id' => 'brigade_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFace()
    {
        return $this->hasOne(Face::className(), ['id' => 'face_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChainerFact()
    {
        return $this->hasOne(Worker::className(), ['id' => 'chainer_fact_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneEquipments()
    {
        return $this->hasMany(OrderByChaneEquipment::className(), ['order_by_chane_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneGroupOperations()
    {
        return $this->hasMany(OrderByChaneGroupOperation::className(), ['order_by_chane_id' => 'id']);
    }
}
