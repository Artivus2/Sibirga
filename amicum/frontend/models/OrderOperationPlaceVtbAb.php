<?php

namespace frontend\models;

use backend\controllers\Assistant;
use Yii;


/**
 * This is the model class for table "order_operation_place_vtb_ab".
 *
 * @property int $id Идентификатор таблицы (автоинкрементный)
 * @property int $order_place_vtb_ab_id Внешний идентификатор наряда на место ВТБ АБ
 * @property int $operation_id Внешний индентификатор операции, которые производятся на этом месте
 * @property string $operation_value_plan
 * @property string $operation_value_fact
 * @property int $status_id Внешний идентификатор статуса наряда АБ ВТБ
 *
 * @property OrderOperationPlaceStatusVtbAb[] $orderOperationPlaceStatusVtbAbs
 * @property Operation $operation
 * @property OrderPlaceVtbAb $orderPlaceVtbAb
 * @property Status $status
 */
class OrderOperationPlaceVtbAb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_operation_place_vtb_ab';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_amicum2');
    }
    public $date_time;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_place_vtb_ab_id', 'operation_id', 'status_id'], 'required'],
            [['order_place_vtb_ab_id', 'operation_id', 'status_id'], 'integer'],
            [['operation_value_plan', 'operation_value_fact'], 'string', 'max' => 45],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
            [['order_place_vtb_ab_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderPlaceVtbAb::className(), 'targetAttribute' => ['order_place_vtb_ab_id' => 'id']],
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
            'order_place_vtb_ab_id' => 'Внешний идентификатор наряда на место ВТБ АБ',
            'operation_id' => 'Внешний индентификатор операции, которые производятся на этом месте',
            'operation_value_plan' => 'Operation Value Plan',
            'operation_value_fact' => 'Operation Value Fact',
            'status_id' => 'Внешний идентификатор статуса наряда АБ ВТБ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationPlaceStatusVtbAbs()
    {
        return $this->hasMany(OrderOperationPlaceStatusVtbAb::className(), ['order_operation_place_vtb_ab_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlaceVtbAb()
    {
        return $this->hasOne(OrderPlaceVtbAb::className(), ['id' => 'order_place_vtb_ab_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    // хз где это и зачем использовалось, но с ней не сохраняется наряд ВТБ т.к. там тоже идет тоже самое сохранение только массово
//    public function afterSave($insert, $changedAttributes)
//    {
//            parent::afterSave($insert, $changedAttributes);
//            if ($insert){
//                $opertation_status = new OrderOperationPlaceStatusVtbAb();
//                $opertation_status->order_operation_place_vtb_ab_id = $this->id;
//                $opertation_status->status_id = $this->status_id;
//                $this->date_time = date('Y-m-d H:i:s',strtotime(Assistant::GetDateNow()));
//                $opertation_status->date_time = $this->date_time;
//                $opertation_status->save();
//            }else{
//                OrderOperationPlaceStatusVtbAb::updateAll(array(
//                    'order_operation_place_vtb_ab_id'=>$this->id,
//                    'status_id'=>$this->status_id,
//                    'date_time'=>$this->date_time), 'order_operation_place_vtb_ab_id=:order_operation_place_vtb_ab_id', array(':order_operation_place_vtb_ab_id'=> $this->id));
//            }
//    }
}
