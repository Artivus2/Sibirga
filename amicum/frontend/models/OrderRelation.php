<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_relation".
 *
 * @property int $id
 * @property int $place_id
 * @property int $main_id
 * @property int $main_rel_id
 *
 * @property Main $main
 * @property Place $place
 * @property OrderRelationStatus[] $orderRelationStatuses
 */
class OrderRelation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_relation';
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
            [['place_id', 'main_id', 'main_rel_id'], 'required'],
            [['place_id', 'main_id', 'main_rel_id'], 'integer'],
            [['main_id'], 'exist', 'skipOnError' => true, 'targetClass' => Main::className(), 'targetAttribute' => ['main_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'place_id' => 'Place ID',
            'main_id' => 'Main ID',
            'main_rel_id' => 'Main Rel ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMain()
    {
        return $this->hasOne(Main::className(), ['id' => 'main_id']);
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
    public function getOrderRelationStatuses()
    {
        return $this->hasMany(OrderRelationStatus::className(), ['order_relation_id' => 'id']);
    }
}
