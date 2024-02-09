<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "situation_journal".
 *
 * @property int $id ключ журнала ситуаций
 * @property int $situation_id ключ типа ситуации
 * @property string $date_time дата создания ситуации
 * @property int|null $main_id ключ главного объекта на котором случилось событие(забой)
 * @property int $status_id текущий статус ситуации
 * @property int $danger_level_id уровень опасности (риск - классификация)
 * @property int|null $company_department_id ключ подразделения на котором произошло событие
 * @property int|null $mine_id ключ шазтного поля, на котором произошло событие
 * @property string|null $date_time_start время начала ситуации
 * @property string|null $date_time_end время окончания ситуации
 *
 * @property EventJournalSituationJournal[] $eventJournalSituationJournals
 * @property MineSituationEventFact[] $mineSituationEventFacts
 * @property RegulationFact[] $regulationFacts
 * @property SituationFactParameter[] $situationFactParameters
 * @property CompanyDepartment $companyDepartment
 * @property DangerLevel $dangerLevel
 * @property Mine $mine
 * @property Situation $situation
 * @property Status $status
 * @property SituationJournalCorrectMeasure[] $situationJournalCorrectMeasures
 * @property Operation[] $operations
 * @property SituationJournalGilty[] $situationJournalGilties
 * @property Worker[] $workers
 * @property SituationJournalStatus[] $situationJournalStatuses
 * @property SituationJournalZone[] $situationJournalZones
 * @property Edge[] $edges
 * @property SituationStatus[] $situationStatuses
 */
class SituationJournal extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'situation_journal';
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
            [['situation_id', 'date_time', 'status_id', 'danger_level_id'], 'required'],
            [['situation_id', 'main_id', 'status_id', 'danger_level_id', 'company_department_id', 'mine_id'], 'integer'],
            [['date_time', 'date_time_start', 'date_time_end'], 'safe'],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['danger_level_id'], 'exist', 'skipOnError' => true, 'targetClass' => DangerLevel::className(), 'targetAttribute' => ['danger_level_id' => 'id']],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
            [['situation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Situation::className(), 'targetAttribute' => ['situation_id' => 'id']],
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
            'situation_id' => 'Situation ID',
            'date_time' => 'Date Time',
            'main_id' => 'Main ID',
            'status_id' => 'Status ID',
            'danger_level_id' => 'Danger Level ID',
            'company_department_id' => 'Company Department ID',
            'mine_id' => 'Mine ID',
            'date_time_start' => 'Date Time Start',
            'date_time_end' => 'Date Time End',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventJournalSituationJournals()
    {
        return $this->hasMany(EventJournalSituationJournal::className(), ['situation_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationEventFacts()
    {
        return $this->hasMany(MineSituationEventFact::className(), ['situation_fact_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRegulationFacts()
    {
        return $this->hasMany(RegulationFact::className(), ['situation_fact_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationFactParameters()
    {
        return $this->hasMany(SituationFactParameter::className(), ['situation_fact_id' => 'id']);
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
    public function getDangerLevel()
    {
        return $this->hasOne(DangerLevel::className(), ['id' => 'danger_level_id']);
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
    public function getSituation()
    {
        return $this->hasOne(Situation::className(), ['id' => 'situation_id']);
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
    public function getSituationJournalCorrectMeasures()
    {
        return $this->hasMany(SituationJournalCorrectMeasure::className(), ['situation_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournalSituationSolutions()
    {
        return $this->hasMany(SituationJournalSituationSolution::className(), ['situation_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperations()
    {
        return $this->hasMany(Operation::className(), ['id' => 'operation_id'])->viaTable('situation_journal_correct_measure', ['situation_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournalGilties()
    {
        return $this->hasMany(SituationJournalGilty::className(), ['situation_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkers()
    {
        return $this->hasMany(Worker::className(), ['id' => 'worker_id'])->viaTable('situation_journal_gilty', ['situation_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournalStatuses()
    {
        return $this->hasMany(SituationJournalStatus::className(), ['situation_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournalZones()
    {
        return $this->hasMany(SituationJournalZone::className(), ['situation_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdges()
    {
        return $this->hasMany(Edge::className(), ['id' => 'edge_id'])->viaTable('situation_journal_zone', ['situation_journal_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationStatuses()
    {
        return $this->hasMany(SituationStatus::className(), ['situation_journal_id' => 'id']);
    }
}
