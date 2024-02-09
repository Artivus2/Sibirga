<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_place_path".
 *
 * @property int $id
 * @property int $order_place_id
 * @property int $path_id
 *
 * @property OrderPlace $orderPlace
 * @property Path $path
 */
class OrderPlacePath extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_place_path';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_place_id', 'path_id'], 'required'],
            [['order_place_id', 'path_id'], 'integer'],
            [['order_place_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderPlace::className(), 'targetAttribute' => ['order_place_id' => 'id']],
            [['path_id'], 'exist', 'skipOnError' => true, 'targetClass' => Path::className(), 'targetAttribute' => ['path_id' => 'id']],
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
            'path_id' => 'Path ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlace()
    {
        return $this->hasOne(OrderPlace::className(), ['id' => 'order_place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPath()
    {
        return $this->hasOne(Path::className(), ['id' => 'path_id']);
    }
}
