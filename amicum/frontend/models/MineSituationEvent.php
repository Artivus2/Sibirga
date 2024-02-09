<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "mine_situation_event".
 *
 * @property int $id
 * @property int $mine_situation_id
 * @property int $situation_id
 *
 * @property MineSituation $mineSituation
 * @property Situation $situation
 */
class MineSituationEvent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mine_situation_event';
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
            [['mine_situation_id', 'situation_id'], 'required'],
            [['mine_situation_id', 'situation_id'], 'integer'],
            [['mine_situation_id'], 'exist', 'skipOnError' => true, 'targetClass' => MineSituation::className(), 'targetAttribute' => ['mine_situation_id' => 'id']],
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
            'mine_situation_id' => 'Mine Situation ID',
            'situation_id' => 'Situation ID',
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
    public function getSituation()
    {
        return $this->hasOne(Situation::className(), ['id' => 'situation_id']);
    }
}
