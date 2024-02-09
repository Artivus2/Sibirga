<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order".
 *
 * @property int $id уникальный индекс таблицы
 * @property string $title Название наряда - может быть любым
 * @property int $company_department_id - Ключ подразделения
 * @property int $mine_id   Ключ шахты
 * @property int $object_id Классификация наряда как типового наряда
 * @property string $date_time_create Дата и время на которые создан наряд
 * @property int $shift_id Внешний ключ справочника смен
 * @property int|null $worker_value_outgoing фактическая выхождаемость работников в наряде
 * @property int|null $status_id
 * @property string|null $brigadeChaneWorker
 *
 * @property Cyclegramm[] $cyclegramms
 * @property CyclegrammType[] $cyclegrammTypes
 * @property CompanyDepartment $companyDepartment
 * @property TypicalObject $object
 * @property Shift $shift
 * @property Status $status
 * @property OrderInstructionPb[] $orderInstructionPbs
 * @property InstructionPb[] $instructionPbs
 * @property OrderItrDepartment[] $orderItrDepartments
 * @property OrderPlace[] $orderPlaces
 * @property OrderShiftFact[] $orderShiftFacts
 * @property OrderStatus[] $orderStatuses
 * @property OrderWorkerCoordinate[] $orderWorkerCoordinates
 * @property OrderWorkerVgk[] $orderWorkerVgks
 * @property Planogramma[] $planogrammas
 * @property Route[] $routes
 */
class Order extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order';
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
            [['title', 'company_department_id', 'object_id', 'date_time_create', 'shift_id', 'mine_id'], 'required'],
            [['company_department_id', 'object_id', 'shift_id', 'worker_value_outgoing', 'status_id', 'mine_id'], 'integer'],
            [['date_time_create'], 'safe'],
            [['brigadeChaneWorker'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['company_department_id', 'date_time_create', 'shift_id', 'mine_id'], 'unique', 'targetAttribute' => ['company_department_id', 'date_time_create', 'shift_id', 'mine_id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
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
            'company_department_id' => 'Company Department ID',
            'object_id' => 'Object ID',
            'date_time_create' => 'Date Time Create',
            'shift_id' => 'Shift ID',
            'mine_id' => 'Mine ID',
            'worker_value_outgoing' => 'Worker Value Outgoing',
            'status_id' => 'Status ID',
            'brigadeChaneWorker' => 'Brigade Chane Worker',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCyclegramms()
    {
        return $this->hasMany(Cyclegramm::className(), ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCyclegrammTypes()
    {
        return $this->hasMany(CyclegrammType::className(), ['id' => 'cyclegramm_type_id'])->viaTable('cyclegramm', ['order_id' => 'id']);
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
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
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
    public function getMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'mine_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderInstructionPbs()
    {
        return $this->hasMany(OrderInstructionPb::className(), ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructionPbs()
    {
        return $this->hasMany(InstructionPb::className(), ['id' => 'instruction_pb_id'])->viaTable('order_instruction_pb', ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItrDepartments()
    {
        return $this->hasMany(OrderItrDepartment::className(), ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlaces()
    {
        return $this->hasMany(OrderPlace::className(), ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderShiftFacts()
    {
        return $this->hasMany(OrderShiftFact::className(), ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderStatuses()
    {
        return $this->hasMany(OrderStatus::className(), ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderHistories()
    {
        return $this->hasMany(OrderHistory::className(), ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderWorkerCoordinates()
    {
        return $this->hasMany(OrderWorkerCoordinate::className(), ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderWorkerVgks()
    {
        return $this->hasMany(OrderWorkerVgk::className(), ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlanogrammas()
    {
        return $this->hasMany(Planogramma::className(), ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaces()
    {
        return $this->hasMany(Place::className(), ['id' => 'place_id'])->viaTable('order_place', ['order_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoutes()
    {
        return $this->hasMany(Route::className(), ['order_id' => 'id']);
    }

    public function getFirstOrderStatuses()
    {
        return $this->hasOne(OrderStatus::className(), ['order_id' => 'id'])->orderBy('order_status.date_time_create ASC')->limit(1);
    }

    public function getLastOrderStatuses()
    {
        return $this->hasOne(OrderStatus::className(), ['order_id' => 'id'])->orderBy('order_status.date_time_create DESC')->limit(1);
    }
    /******************** Связи созданные вручную получение последнего работника который согласова и того кто утвердил ********************/
    public function getLastAgreedOrderStatus()
    {
        return $this->hasOne(OrderStatus::className(), ['order_id' => 'id'])->where(['status_id'=> 4])->orderBy('order_status.date_time_create DESC');
    }

    public function getLastAcceptOrderStatus()
    {
        return $this->hasOne(OrderStatus::className(), ['order_id' => 'id'])->where(['status_id'=> 6])->orderBy('order_status.date_time_create DESC');
    }
    /** Связь с операциями в наряде, создана вручную */
    public function getOrderOperations()
    {
        return $this->hasMany(OrderOperation::className(), ['order_place_id' => 'id'])->via('orderPlaces');
    }

    public function getLastIssuedOrder()
    {
        return $this->hasOne(OrderStatus::className(), ['order_id' => 'id'])->where(['order_status.status_id'=> 50])->orderBy('order_status.date_time_create DESC');
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderJson()
    {
        return $this->hasOne(OrderJson::className(), ['order_id' => 'id']);
    }
}
