<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "situation".
 *
 * @property int $id
 * @property string $title
 * @property int $group_situation_id
 * @property int $danger_level_id
 * @property int $object_id
 *
 * @property EventSituation[] $eventSituations
 * @property MineSituationEvent[] $mineSituationEvents
 * @property Regulation[] $regulations
 * @property GroupSituation $groupSituation
 * @property Object $object
 * @property DangerLevel $dangerLevel
 * @property SituationJournal[] $situationJournals
 */
class Situation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'situation';
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
            [['id', 'title', 'group_situation_id', 'danger_level_id', 'object_id'], 'required'],
            [['id', 'group_situation_id', 'danger_level_id', 'object_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['id'], 'unique'],
            [['group_situation_id'], 'exist', 'skipOnError' => true, 'targetClass' => GroupSituation::className(), 'targetAttribute' => ['group_situation_id' => 'id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
            [['danger_level_id'], 'exist', 'skipOnError' => true, 'targetClass' => DangerLevel::className(), 'targetAttribute' => ['danger_level_id' => 'id']],
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
            'group_situation_id' => 'Group Situation ID',
            'danger_level_id' => 'Danger Level ID',
            'object_id' => 'Object ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventSituations()
    {
        return $this->hasMany(EventSituation::className(), ['situation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationEvents()
    {
        return $this->hasMany(MineSituationEvent::className(), ['situation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRegulations()
    {
        return $this->hasMany(Regulation::className(), ['situation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupSituation()
    {
        return $this->hasOne(GroupSituation::className(), ['id' => 'group_situation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
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
    public function getSituationJournals()
    {
        return $this->hasMany(SituationJournal::className(), ['situation_id' => 'id']);
    }

    //------РУЧНОЙ МЕТОД!!!!!
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvents()
    {
        return $this->hasMany(Event::className(), ['id' => 'event_id'])->via('eventSituations');
    }
}
