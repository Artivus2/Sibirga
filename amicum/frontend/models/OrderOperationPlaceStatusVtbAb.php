<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_operation_place_status_vtb_ab".
 *
 * @property int $id Идентификатор таблицы (автоинкрементный)
 * @property int $order_operation_place_vtb_ab_id Внешний идентификатор операций на место
 * @property int $status_id Внешний идентификатор статусов операций в нарядах на места
 * @property string $date_time
 *
 * @property OrderOperationPlaceVtbAb $orderOperationPlaceVtbAb
 * @property Status $status
 */
class OrderOperationPlaceStatusVtbAb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_operation_place_status_vtb_ab';
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
            [['order_operation_place_vtb_ab_id', 'status_id', 'date_time'], 'required'],
            [['order_operation_place_vtb_ab_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['order_operation_place_vtb_ab_id', 'status_id', 'date_time'], 'unique', 'targetAttribute' => ['order_operation_place_vtb_ab_id', 'status_id', 'date_time']],
            [['order_operation_place_vtb_ab_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderOperationPlaceVtbAb::className(), 'targetAttribute' => ['order_operation_place_vtb_ab_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы (автоинкрементный)',
            'order_operation_place_vtb_ab_id' => 'Внешний идентификатор операций на место',
            'status_id' => 'Внешний идентификатор статусов операций в нарядах на места',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationPlaceVtbAb()
    {
        return $this->hasOne(OrderOperationPlaceVtbAb::className(), ['id' => 'order_operation_place_vtb_ab_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
