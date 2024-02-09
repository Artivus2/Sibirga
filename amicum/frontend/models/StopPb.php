<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "stop_pb".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)\\n
 * @property int|null $injunction_violation_id Внешний ключ предсписаения из списка предписаний
 * @property int $kind_stop_pb_id Внешни ключ вида простоя ПБ
 * @property int|null $kind_duration_id Внешний ключ вида длительности
 * @property int $place_id Внешний ключ места
 * @property string $date_time_start Дата начало простоя
 * @property string|null $date_time_end Дата окончания простоя
 * @property string|null $description описание причины простоя
 * @property int|null $worker_id создатель простоя в системе
 * @property int|null $type_operation_id тип операции (8 - простой)
 * @property string|null $xyz координата места простоя
 * @property int|null $company_department_id ключ департамента на котором случился простой
 * @property int|null $section Секция крепи где была приостановка работ (необходима для циклограмм)
 * @property int|null $operation_id Внешний идентификатор операции
 *
 * @property InjunctionViolation $injunctionViolation
 * @property KindDuration $kindDuration
 * @property KindStopPb $kindStopPb
 * @property Operation $operation
 * @property Place $place
 * @property StopPbEquipment[] $stopPbEquipments
 * @property StopPbEvent[] $stopPbEvents
 * @property StopPbStatus[] $stopPbStatuses
 */
class StopPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stop_pb';
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
            [['injunction_violation_id', 'kind_stop_pb_id', 'kind_duration_id', 'place_id', 'worker_id', 'type_operation_id', 'company_department_id', 'section', 'operation_id'], 'integer'],
            [['kind_stop_pb_id', 'place_id', 'date_time_start'], 'required'],
            [['date_time_start', 'date_time_end'], 'safe'],
            [['description'], 'string', 'max' => 500],
            [['xyz'], 'string', 'max' => 50],
            [['injunction_violation_id'], 'exist', 'skipOnError' => true, 'targetClass' => InjunctionViolation::className(), 'targetAttribute' => ['injunction_violation_id' => 'id']],
            [['kind_duration_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindDuration::className(), 'targetAttribute' => ['kind_duration_id' => 'id']],
            [['kind_stop_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindStopPb::className(), 'targetAttribute' => ['kind_stop_pb_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
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
            'injunction_violation_id' => 'Injunction Violation ID',
            'kind_stop_pb_id' => 'Kind Stop Pb ID',
            'kind_duration_id' => 'Kind Duration ID',
            'place_id' => 'Place ID',
            'date_time_start' => 'Date Time Start',
            'date_time_end' => 'Date Time End',
            'description' => 'Description',
            'worker_id' => 'Worker ID',
            'type_operation_id' => 'Type Operation ID',
            'xyz' => 'Xyz',
            'company_department_id' => 'Company Department ID',
            'section' => 'Section',
            'operation_id' => 'Operation ID',
        ];
    }

    /**
     * Gets query for [[InjunctionViolation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionViolation()
    {
        return $this->hasOne(InjunctionViolation::className(), ['id' => 'injunction_violation_id']);
    }

    /**
     * Gets query for [[KindDuration]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getKindDuration()
    {
        return $this->hasOne(KindDuration::className(), ['id' => 'kind_duration_id']);
    }

    /**
     * Gets query for [[KindStopPb]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getKindStopPb()
    {
        return $this->hasOne(KindStopPb::className(), ['id' => 'kind_stop_pb_id']);
    }

    /**
     * Gets query for [[Operation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * Gets query for [[Place]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * Gets query for [[StopPbEquipments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStopPbEquipments()
    {
        return $this->hasMany(StopPbEquipment::className(), ['stop_pb_id' => 'id']);
    }

    /**
     * Gets query for [[StopPbEquipments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * Gets query for [[StopPbEvents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStopPbEvents()
    {
        return $this->hasMany(StopPbEvent::className(), ['stop_pb_id' => 'id']);
    }

    /**
     * Gets query for [[StopPbStatuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStopPbStatuses()
    {
        return $this->hasMany(StopPbStatus::className(), ['stop_pb_id' => 'id']);
    }
}
