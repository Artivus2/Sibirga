<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "group_situation".
 *
 * @property int $id
 * @property string $title
 * @property int $kind_group_situation_id
 *
 * @property KindGroupSituation $kindGroupSituation
 * @property MineSituation[] $mineSituations
 * @property Situation[] $situations
 */
class GroupSituation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_situation';
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
            [['title', 'kind_group_situation_id'], 'required'],
            [['kind_group_situation_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['kind_group_situation_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindGroupSituation::className(), 'targetAttribute' => ['kind_group_situation_id' => 'id']],
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
            'kind_group_situation_id' => 'Kind Group Situation ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getKindGroupSituation()
    {
        return $this->hasOne(KindGroupSituation::className(), ['id' => 'kind_group_situation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituations()
    {
        return $this->hasMany(MineSituation::className(), ['group_situation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituations()
    {
        return $this->hasMany(Situation::className(), ['group_situation_id' => 'id']);
    }
}
