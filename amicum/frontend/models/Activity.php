<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "activity".
 *
 * @property int $id
 * @property string $title
 *
 * @property ActivityRegulation[] $activityRegulations
 * @property PlaActivity[] $plaActivities
 * @property PlaActivityFact[] $plaActivityFacts
 */
class Activity extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'activity';
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
            [['id', 'title'], 'required'],
            [['id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['id'], 'unique'],
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
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActivityRegulations()
    {
        return $this->hasMany(ActivityRegulation::className(), ['activity_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaActivities()
    {
        return $this->hasMany(PlaActivity::className(), ['activity_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaActivityFacts()
    {
        return $this->hasMany(PlaActivityFact::className(), ['activity_id' => 'id']);
    }
}
