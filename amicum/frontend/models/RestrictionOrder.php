<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "restriction_order".
 *
 * @property int $id
 * @property int $order_id Внешний идентификатор наряда
 * @property int $worker_id Внешний идентификатор работника
 * @property string $date Дата = дате из наряда
 * @property int $shift_id Внешний идентификатор смены = смене из наряда
 * @property int $company_department_id Внешний идентификатор участка = участку из наряда
 * @property string $restriction_json Ограничения
 *
 * @property CompanyDepartment $companyDepartment
 * @property Order $order
 * @property Shift $shift
 * @property Worker $worker
 */
class RestrictionOrder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'restriction_order';
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
            [['order_id', 'worker_id', 'date', 'shift_id', 'company_department_id'], 'required'],
            [['order_id', 'worker_id', 'shift_id', 'company_department_id'], 'integer'],
            [['date'], 'safe'],
            [['restriction_json'], 'string'],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
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
            'order_id' => 'Внешний идентификатор наряда',
            'worker_id' => 'Внешний идентификатор работника',
            'date' => 'Дата = дате из наряда',
            'shift_id' => 'Внешний идентификатор смены = смене из наряда',
            'company_department_id' => 'Внешний идентификатор участка = участку из наряда',
            'restriction_json' => 'Ограничения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
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
    public function getShift()
    {
        return $this->hasOne(Shift::className(), ['id' => 'shift_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
    /******************** Написано вручную ********************/
    /**
     * Получить последнюю запись где был статус 50 (наряд создан)
     */
    public function getLastIssuedOrder()
    {
        return $this->hasOne(OrderStatus::className(), ['order_id' => 'id'])->where(['order_status.status_id'=> 50])->orderBy('order_status.date_time_create DESC')->via('order');
    }
}
