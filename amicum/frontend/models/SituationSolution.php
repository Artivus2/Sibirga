<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "situation_solution".
 *
 * @property int $id ключ устранения ситуаций
 * @property int $regulation_time регламентное время общее (секунды)
 * @property string $solution_date_time_start начало выполенния регламента
 *
 * @property SituationJournalSituationSolution[] $situationJournalSituationSolutions
 * @property SituationSolutionHystory[] $situationSolutionHystories
 * @property SituationSolutionStatus[] $situationSolutionStatuses
 * @property SolutionCard[] $solutionCards
 */
class SituationSolution extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'situation_solution';
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
            [['regulation_time', 'solution_date_time_start'], 'required'],
            [['regulation_time'], 'integer'],
            [['solution_date_time_start'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'regulation_time' => 'Regulation Time',
            'solution_date_time_start' => 'Solution Date Time Start',
        ];
    }

    /**
     * Gets query for [[SituationJournalSituationSolutions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournalSituationSolutions()
    {
        return $this->hasMany(SituationJournalSituationSolution::className(), ['situation_solution_id' => 'id']);
    }

    /**
     * Gets query for [[SituationSolutionHystories]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationSolutionHystories()
    {
        return $this->hasMany(SituationSolutionHystory::className(), ['situation_solution_id' => 'id']);
    }

    /**
     * Gets query for [[SituationSolutionStatuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationSolutionStatuses()
    {
        return $this->hasMany(SituationSolutionStatus::className(), ['situation_solution_id' => 'id']);
    }

    /**
     * Gets query for [[SolutionCards]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSolutionCards()
    {
        return $this->hasMany(SolutionCard::className(), ['situation_solution_id' => 'id']);
    }

}
