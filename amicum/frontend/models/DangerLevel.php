<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "danger_level".
 *
 * @property int $id
 * @property string $title
 * @property int $number_of_level
 *
 * @property MineSituation[] $mineSituations
 * @property MineSituationFact[] $mineSituationFacts
 * @property Situation[] $situations
 * @property SituationJournal[] $situationJournals
 */
class DangerLevel extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'danger_level';
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
            [['title', 'number_of_level'], 'required'],
            [['number_of_level'], 'integer'],
            [['title'], 'string', 'max' => 45],
            [['title'], 'unique'],
            [['number_of_level'], 'unique'],
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
            'number_of_level' => 'Number Of Level',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituations()
    {
        return $this->hasMany(MineSituation::className(), ['danger_level_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationFacts()
    {
        return $this->hasMany(MineSituationFact::className(), ['danger_level_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituations()
    {
        return $this->hasMany(Situation::className(), ['danger_level_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournals()
    {
        return $this->hasMany(SituationJournal::className(), ['danger_level_id' => 'id']);
    }
}
