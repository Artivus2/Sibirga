<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "event_pb".
 *
 * @property int $id
 * @property string|null $date_time_event дата и время события/несчастного случая
 * @property string|null $place_name место события
 * @property int|null $assessment_place_id ключ специальной оценки места
 * @property int|null $case_pb_id
 * @property string|null $description_event_pb описание несчастного случая при котором произошел несчастный случай, 
 * @property string|null $description_correct_measure корректирующие мероприятия - принятые меры по устранению причин несчастного случая
 * @property int $company_department_id ключ структурного подразделения в котором произошло событие
 * @property int|null $inquiry_pb_id ключ акта протокола расследования со всеми документами
 * @property int|null $kind_crash_id ключ вида аварии
 * @property int|null $kind_incident_id ключ вида инцидента
 * @property string|null $kind_miscellaneous вид события прочее
 * @property string|null $description_incident Причины инцидента
 * @property string|null $description_committee мероприятия предложенные комиссией
 * @property string|null $description_crash описание возникновения, развития, ликвидации аварии, какие пункты действующих правил были нарушены
 * @property float|null $economic_damage Экономический ущерб в тыс.руб
 * @property float|null $lost_energy Недоопуск энергии, кВт*ч
 * @property float|null $duration_stop ПРодолжительность простоя в ч.
 * @property int|null $exist_victim наличие пострадавших да /нет
 * @property int|null $kind_mishap_id
 * @property int|null $status_id
 * @property string|null $status_date_time
 * @property int|null $mine_id ключ щахты
 * @property string|null $date_time_direction_to_cop дата направления в прокуратуру
 * @property string|null $description_measure описание мер воздействия (наказание)
 *
 * @property CasePb $casePb
 * @property Status $status
 * @property InquiryPb $inquiryPb
 * @property KindCrash $kindCrash
 * @property KindIncident $kindIncident
 * @property KindAccident $kindMishap
 * @property EventPbWorker[] $eventPbWorkers
 * @property Worker[] $workers
 */
class EventPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_pb';
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
            [['date_time_event', 'status_date_time', 'date_time_direction_to_cop'], 'safe'],
            [['assessment_place_id', 'case_pb_id', 'company_department_id', 'inquiry_pb_id', 'kind_crash_id', 'kind_incident_id', 'exist_victim', 'kind_mishap_id', 'status_id', 'mine_id'], 'integer'],
            [['description_event_pb', 'description_correct_measure', 'kind_miscellaneous', 'description_incident', 'description_committee', 'description_crash', 'description_measure'], 'string'],
            [['company_department_id'], 'required'],
            [['economic_damage', 'lost_energy', 'duration_stop'], 'number'],
            [['place_name'], 'string', 'max' => 900],
            [['case_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => CasePb::className(), 'targetAttribute' => ['case_pb_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['inquiry_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => InquiryPb::className(), 'targetAttribute' => ['inquiry_pb_id' => 'id']],
            [['kind_crash_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindCrash::className(), 'targetAttribute' => ['kind_crash_id' => 'id']],
            [['kind_incident_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindIncident::className(), 'targetAttribute' => ['kind_incident_id' => 'id']],
            [['kind_mishap_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindAccident::className(), 'targetAttribute' => ['kind_mishap_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_time_event' => 'Date Time Event',
            'place_name' => 'Place Name',
            'assessment_place_id' => 'Assessment Place ID',
            'case_pb_id' => 'Case Pb ID',
            'description_event_pb' => 'Description Event Pb',
            'description_correct_measure' => 'Description Correct Measure',
            'company_department_id' => 'Company Department ID',
            'inquiry_pb_id' => 'Inquiry Pb ID',
            'kind_crash_id' => 'Kind Crash ID',
            'kind_incident_id' => 'Kind Incident ID',
            'kind_miscellaneous' => 'Kind Miscellaneous',
            'description_incident' => 'Description Incident',
            'description_committee' => 'Description Committee',
            'description_crash' => 'Description Crash',
            'economic_damage' => 'Economic Damage',
            'lost_energy' => 'Lost Energy',
            'duration_stop' => 'Duration Stop',
            'exist_victim' => 'Exist Victim',
            'kind_mishap_id' => 'Kind Mishap ID',
            'status_id' => 'Status ID',
            'status_date_time' => 'Status Date Time',
            'mine_id' => 'Mine ID',
            'date_time_direction_to_cop' => 'Date Time Direction To Cop',
            'description_measure' => 'Description Measure',
        ];
    }

    /**
     * Gets query for [[CasePb]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCasePb()
    {
        return $this->hasOne(CasePb::className(), ['id' => 'case_pb_id']);
    }

    /**
     * Gets query for [[Status]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * Gets query for [[InquiryPb]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInquiryPb()
    {
        return $this->hasOne(InquiryPb::className(), ['id' => 'inquiry_pb_id']);
    }

    /**
     * Gets query for [[KindCrash]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getKindCrash()
    {
        return $this->hasOne(KindCrash::className(), ['id' => 'kind_crash_id']);
    }

    /**
     * Gets query for [[KindIncident]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getKindIncident()
    {
        return $this->hasOne(KindIncident::className(), ['id' => 'kind_incident_id']);
    }

    /**
     * Gets query for [[KindMishap]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getKindMishap()
    {
        return $this->hasOne(KindAccident::className(), ['id' => 'kind_mishap_id']);
    }

    /**
     * Gets query for [[EventPbWorkers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventPbWorkers()
    {
        return $this->hasMany(EventPbWorker::className(), ['event_pb_id' => 'id']);
    }

    /**
     * Gets query for [[Workers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkers()
    {
        return $this->hasMany(Worker::className(), ['id' => 'worker_id'])->viaTable('event_pb_worker', ['event_pb_id' => 'id']);
    }
}
