<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "situation_journal_zone".
 *
 * @property int $id ключ зоны опасной ситуации
 * @property int $situation_journal_id ключ журнала ситуаций
 * @property int $edge_id ключ выработки которая попала в опасную зону
 *
 * @property SituationJournal $situationJournal
 * @property Edge $edge
 */
class SituationJournalZone extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'situation_journal_zone';
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
            [['situation_journal_id', 'edge_id'], 'required'],
            [['situation_journal_id', 'edge_id'], 'integer'],
            [['situation_journal_id', 'edge_id'], 'unique', 'targetAttribute' => ['situation_journal_id', 'edge_id']],
            [['situation_journal_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationJournal::className(), 'targetAttribute' => ['situation_journal_id' => 'id']],
            [['edge_id'], 'exist', 'skipOnError' => true, 'targetClass' => Edge::className(), 'targetAttribute' => ['edge_id' => 'id']],
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
            'edge_id' => 'Edge ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournal()
    {
        return $this->hasOne(SituationJournal::className(), ['id' => 'situation_journal_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdge()
    {
        return $this->hasOne(Edge::className(), ['id' => 'edge_id']);
    }
}
