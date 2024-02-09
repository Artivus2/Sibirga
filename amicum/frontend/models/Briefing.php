<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "briefing".
 *
 * @property int $id
 * @property string $date_time Дата проведения инструктажа
 * @property int $type_briefing_id Тип проведенного инструктажа
 * @property int $status_id Статус инструктажа
 * @property int $company_department_id Подразделение, на котором был инструктаж
 * @property int $attachment_id Документ с тесктом инструктажа
 * @property int $instructor_id Ключ инструктора
 * @property int $instructor_position_id ключ должности инструктора
 * @property string $briefing_reason причина внепланового инструктажа
 * @property int $kind_fire_prevention_id
 * @property int $document_id программа инструктажа
 * @property int briefing_reason_id причина проведения инструктажа
 *
 * @property Briefer[] $briefers
 * @property Worker[] $workers
 * @property Attachment $attachment
 * @property CompanyDepartment $companyDepartment
 * @property KindFirePreventionInstruction $kindFirePrevention
 * @property Status $status
 * @property TypeBriefing $typeBriefing
 * @property Worker $instructor
 */
class Briefing extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'briefing';
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
            [['date_time', 'type_briefing_id', 'status_id', 'company_department_id', 'instructor_id'], 'required'],
            [['date_time'], 'safe'],
            [['type_briefing_id', 'status_id', 'company_department_id', 'attachment_id', 'instructor_id', 'instructor_position_id', 'kind_fire_prevention_id', 'document_id', 'briefing_reason_id'], 'integer'],
            [['briefing_reason'], 'string', 'max' => 255],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['kind_fire_prevention_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindFirePreventionInstruction::className(), 'targetAttribute' => ['kind_fire_prevention_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['type_briefing_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypeBriefing::className(), 'targetAttribute' => ['type_briefing_id' => 'id']],
            [['instructor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['instructor_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_time' => 'Дата проведения инструктажа',
            'type_briefing_id' => 'Тип проведенного инструктажа',
            'status_id' => 'Статус инструктажа',
            'company_department_id' => 'Подразделение, на котором был инструктаж',
            'attachment_id' => 'Документ с тесктом инструктажа',
            'instructor_id' => 'Ключ инструктора',
            'instructor_position_id' => 'ключ должности инструктора',
            'briefing_reason' => 'причина внепланового инструктажа',
            'kind_fire_prevention_id' => 'Kind Fire Prevention ID',
            'document_id' => 'программа инструктажа',
            'briefing_reason_id' => 'причина проведения инструктажа',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBriefers()
    {
        return $this->hasMany(Briefer::className(), ['briefing_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkers()
    {
        return $this->hasMany(Worker::className(), ['id' => 'worker_id'])->viaTable('briefer', ['briefing_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id']);
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
    public function getKindFirePrevention()
    {
        return $this->hasOne(KindFirePreventionInstruction::className(), ['id' => 'kind_fire_prevention_id']);
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
    public function getTypeBriefing()
    {
        return $this->hasOne(TypeBriefing::className(), ['id' => 'type_briefing_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructor()
    {
        return $this->hasOne(Worker::className(), ['id' => 'instructor_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBriefingReason()
    {
        return $this->hasOne(BriefingReason::className(), ['id' => 'briefing_reason_id']);
    }
}
