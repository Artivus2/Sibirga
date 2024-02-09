<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_route_worker".
 *
 * @property int $id ключ привязки наряд путевки к работнику
 * @property int $order_place_id ключ привязки места к наряду
 * @property int $worker_id ключ работника
 * @property string|null $order_route_json наряд путевка горного мастера АБ/ВТБ
 * @property string|null $order_route_esp_json наряд путевка электрослесарей АБ
 *
 * @property OrderPlace $orderPlace
 * @property Worker $worker
 */
class OrderRouteWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_route_worker';
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
            [['order_place_id', 'worker_id'], 'required'],
            [['order_place_id', 'worker_id'], 'integer'],
            [['order_route_json'], 'string'],
            [['order_route_esp_json'], 'safe'],
            [['order_place_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderPlace::className(), 'targetAttribute' => ['order_place_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
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
            'worker_id' => 'Worker ID',
            'order_route_json' => 'Order Route Json',
            'order_route_esp_json' => 'Order Route Esp Json',
        ];
    }

    /**
     * Gets query for [[OrderPlace]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlace()
    {
        return $this->hasOne(OrderPlace::className(), ['id' => 'order_place_id']);
    }

    /**
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
