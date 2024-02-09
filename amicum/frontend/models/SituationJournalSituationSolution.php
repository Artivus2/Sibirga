<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "situation_journal_situation_solution".
 *
 * @property int $id ключ связи фактической ситуации и решения
 * @property int $situation_journal_id
 * @property int $situation_solution_id
 *
 * @property SituationJournal $situationJournal
 * @property SituationSolution $situationSolution
 */
class SituationJournalSituationSolution extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'situation_journal_situation_solution';
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
            [['situation_journal_id', 'situation_solution_id'], 'required'],
            [['situation_journal_id', 'situation_solution_id'], 'integer'],
            [['situation_journal_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationJournal::className(), 'targetAttribute' => ['situation_journal_id' => 'id']],
            [['situation_solution_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationSolution::className(), 'targetAttribute' => ['situation_solution_id' => 'id']],
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
            'situation_solution_id' => 'Situation Solution ID',
        ];
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
     * Gets query for [[SituationSolution]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationSolution()
    {
        return $this->hasOne(SituationSolution::className(), ['id' => 'situation_solution_id']);
    }
}
