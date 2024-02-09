<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "pla".
 *
 * @property int $id
 * @property string $title
 * @property int $mine_situation_id
 * @property int $object_id
 *
 * @property MineSituation $mineSituation
 * @property Object $object
 * @property PlaActivity[] $plaActivities
 * @property PlaFact[] $plaFacts
 */
class Pla extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pla';
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
            [['id', 'title', 'mine_situation_id', 'object_id'], 'required'],
            [['id', 'mine_situation_id', 'object_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['id'], 'unique'],
            [['mine_situation_id'], 'exist', 'skipOnError' => true, 'targetClass' => MineSituation::className(), 'targetAttribute' => ['mine_situation_id' => 'id']],
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
            'mine_situation_id' => 'Mine Situation ID',
            'object_id' => 'Object ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituation()
    {
        return $this->hasOne(MineSituation::className(), ['id' => 'mine_situation_id']);
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
    public function getPlaActivities()
    {
        return $this->hasMany(PlaActivity::className(), ['pla_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaFacts()
    {
        return $this->hasMany(PlaFact::className(), ['pla_id' => 'id']);
    }
}
