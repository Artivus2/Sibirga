<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "situation_journal_correct_measure".
 *
 * @property int $id ключ журнала ответственных за событие
 * @property int $situation_journal_id
 * @property int $operation_id
 *
 * @property SituationJournal $situationJournal
 * @property Operation $operation
 */
class SituationJournalCorrectMeasure extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'situation_journal_correct_measure';
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
            [['situation_journal_id', 'operation_id'], 'required'],
            [['situation_journal_id', 'operation_id'], 'integer'],
            [['situation_journal_id', 'operation_id'], 'unique', 'targetAttribute' => ['situation_journal_id', 'operation_id']],
            [['situation_journal_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationJournal::className(), 'targetAttribute' => ['situation_journal_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
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
            'operation_id' => 'Operation ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournal()
    {
        return $this->hasOne(SituationJournal::className(), ['id' => 'situation_journal_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }
}
