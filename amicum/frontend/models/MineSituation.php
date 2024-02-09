<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "mine_situation".
 *
 * @property int $id
 * @property string $title
 * @property int $object_id
 * @property int $danger_level_id
 * @property int $group_situation_id
 *
 * @property DangerLevel $dangerLevel
 * @property GroupSituation $groupSituation
 * @property Object $object
 * @property MineSituationEvent[] $mineSituationEvents
 * @property MineSituationFact[] $mineSituationFacts
 * @property Pla[] $plas
 */
class MineSituation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mine_situation';
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
            [['id', 'title', 'object_id', 'danger_level_id', 'group_situation_id'], 'required'],
            [['id', 'object_id', 'danger_level_id', 'group_situation_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['id'], 'unique'],
            [['danger_level_id'], 'exist', 'skipOnError' => true, 'targetClass' => DangerLevel::className(), 'targetAttribute' => ['danger_level_id' => 'id']],
            [['group_situation_id'], 'exist', 'skipOnError' => true, 'targetClass' => GroupSituation::className(), 'targetAttribute' => ['group_situation_id' => 'id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
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
            'object_id' => 'Object ID',
            'danger_level_id' => 'Danger Level ID',
            'group_situation_id' => 'Group Situation ID',
        ];
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
    public function getMineSituationEvents()
    {
        return $this->hasMany(MineSituationEvent::className(), ['mine_situation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationFacts()
    {
        return $this->hasMany(MineSituationFact::className(), ['mine_situation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlas()
    {
        return $this->hasMany(Pla::className(), ['mine_situation_id' => 'id']);
    }
}
