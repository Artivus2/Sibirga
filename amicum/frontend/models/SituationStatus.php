<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "situation_status".
 *
 * @property int $id ключ истории
 * @property int $situation_journal_id ключ журнала событий
 * @property int $status_id ключ события
 * @property string $date_time дата и всремя изменения статуса события (6)
 * @property int|null $kind_reason_id причина события
 * @property string|null $description описание причины события
 * @property int|null $worker_id ключ работника
 *
 * @property KindReason $kindReason
 * @property SituationJournal $situationJournal
 * @property Status $status
 * @property Status $status0
 */
class SituationStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'situation_status';
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
            [['situation_journal_id', 'status_id', 'date_time'], 'required'],
            [['situation_journal_id', 'status_id', 'kind_reason_id', 'worker_id'], 'integer'],
            [['date_time'], 'safe'],
            [['description'], 'string', 'max' => 255],
            [['kind_reason_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindReason::className(), 'targetAttribute' => ['kind_reason_id' => 'id']],
            [['situation_journal_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationJournal::className(), 'targetAttribute' => ['situation_journal_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
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
            'situation_journal_id' => 'Situation Journal ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
            'kind_reason_id' => 'Kind Reason ID',
            'description' => 'Description',
            'worker_id' => 'Worker ID',
        ];
    }

    /**
     * Gets query for [[KindReason]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getKindReason()
    {
        return $this->hasOne(KindReason::className(), ['id' => 'kind_reason_id']);
    }

    /**
     * Gets query for [[SituationJournal]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournal()
    {
        return $this->hasOne(SituationJournal::className(), ['id' => 'situation_journal_id']);
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
     * Gets query for [[Status0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus0()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
