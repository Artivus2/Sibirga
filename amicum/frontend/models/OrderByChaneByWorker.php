<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_by_chane_by_worker".
 *
 * @property int $id
 * @property int $order_by_worker_id
 * @property int $order_by_chane_group_operation_id
 *
 * @property OrderByChaneGroupOperation $orderByChaneGroupOperation
 * @property OrderByWorker $orderByWorker
 */
class OrderByChaneByWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_by_chane_by_worker';
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
            [['order_by_worker_id', 'order_by_chane_group_operation_id'], 'required'],
            [['order_by_worker_id', 'order_by_chane_group_operation_id'], 'integer'],
            [['order_by_chane_group_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderByChaneGroupOperation::className(), 'targetAttribute' => ['order_by_chane_group_operation_id' => 'id']],
            [['order_by_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderByWorker::className(), 'targetAttribute' => ['order_by_worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_by_worker_id' => 'Order By Worker ID',
            'order_by_chane_group_operation_id' => 'Order By Chane Group Operation ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneGroupOperation()
    {
        return $this->hasOne(OrderByChaneGroupOperation::className(), ['id' => 'order_by_chane_group_operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByWorker()
    {
        return $this->hasOne(OrderByWorker::className(), ['id' => 'order_by_worker_id']);
    }
}
