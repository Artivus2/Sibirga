<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_permit".
 *
 * @property int $id уникальный индекс таблицы
 * @property string $title Название наряда - может быть любым
 * @property int $company_department_id ключ подразделения на который выдается наряд
 * @property int $object_id классификация наряда как типового наряда
 * @property string $date_time_create Дата и время на которые создан наряд
 * @property int $shift_id внешний ключ справочника смен
 * @property int|null $status_id текущий статус наряда (выдан сдан в работе и т.д.)
 * @property int|null $place_id место ведения работ по наряд допуску
 * @property string|null $date_time_start Дата выдачи наряд допуска
 * @property string|null $date_time_end дата и время окончания работ (закрытие наряд допуска)
 * @property int|null $order_status_done Наряд допуск сдан да нет
 * @property int|null $worker_id ключ работника выдавшего наряд допуск
 * @property string|null $description
 * @property string|null $number_order номер наряд допуска
 *
 * @property CompanyDepartment $companyDepartment
 * @property TypicalObject $object
 * @property Shift $shift
 * @property Place $place
 * @property Status $status
 * @property OrderPermitAttachment[] $orderPermitAttachments
 * @property OrderPermitOperation[] $orderPermitOperations
 * @property OrderPermitStatus[] $orderPermitStatuses
 * @property OrderPermitWorker[] $orderPermitWorkers
 */
class OrderPermit extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_permit';
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
            [['title', 'company_department_id', 'object_id', 'date_time_create', 'shift_id'], 'required'],
            [['company_department_id', 'object_id', 'shift_id', 'status_id', 'place_id', 'order_status_done', 'worker_id'], 'integer'],
            [['date_time_create', 'date_time_start', 'date_time_end'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 500],
            [['number_order'], 'string', 'max' => 20],
            [['company_department_id', 'date_time_create', 'shift_id'], 'unique', 'targetAttribute' => ['company_department_id', 'date_time_create', 'shift_id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'object_id' => 'TypicalObject ID',
            'date_time_create' => 'Date Time Create',
            'shift_id' => 'Shift ID',
            'status_id' => 'Status ID',
            'place_id' => 'Place ID',
            'date_time_start' => 'Date Time Start',
            'date_time_end' => 'Date Time End',
            'order_status_done' => 'Order Status Done',
            'worker_id' => 'Worker ID',
            'description' => 'Description',
            'number_order' => 'Number Order',
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
    public function getTypicalObject()
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
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
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
    public function getOrderPermitAttachments()
    {
        return $this->hasMany(OrderPermitAttachment::className(), ['order_permit_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPermitOperations()
    {
        return $this->hasMany(OrderPermitOperation::className(), ['order_permit_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPermitStatuses()
    {
        return $this->hasMany(OrderPermitStatus::className(), ['order_permit_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPermitWorkers()
    {
        return $this->hasMany(OrderPermitWorker::className(), ['order_permit_id' => 'id']);
    }
}
