<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "regulation".
 *
 * @property int $id
 * @property string $title
 * @property int $situation_id
 * @property int $object_id
 *
 * @property ActivityRegulation[] $activityRegulations
 * @property Object $object
 * @property Situation $situation
 * @property RegulationAction[] $regulationActions
 * @property RegulationFact[] $regulationFacts
 */
class Regulation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'regulation';
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
            [['title', 'situation_id', 'object_id'], 'required'],
            [['situation_id', 'object_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
            [['situation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Situation::className(), 'targetAttribute' => ['situation_id' => 'id']],
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
            'situation_id' => 'Situation ID',
            'object_id' => 'Object ID',
        ];
    }

    /**
     * Gets query for [[ActivityRegulations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getActivityRegulations()
    {
        return $this->hasMany(ActivityRegulation::className(), ['regulation_id' => 'id']);
    }

    /**
     * Gets query for [[Object]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
    }

    /**
     * Gets query for [[Situation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituation()
    {
        return $this->hasOne(Situation::className(), ['id' => 'situation_id']);
    }

    /**
     * Gets query for [[RegulationActions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegulationActions()
    {
        return $this->hasMany(RegulationAction::className(), ['regulation_id' => 'id']);
    }

    /**
     * Gets query for [[RegulationFacts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegulationFacts()
    {
        return $this->hasMany(RegulationFact::className(), ['regulation_id' => 'id']);
    }
}
