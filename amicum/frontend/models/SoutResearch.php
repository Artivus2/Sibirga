<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sout_research".
 *
 * @property int $id
 * @property int $sout_id Внешний идентификатор СОУТ/ПК
 * @property int $research_id Внешний идентификатор показателей
 * @property int $status_id Внешний идентификатор статуса показателя СОУТ/ПК
 *
 * @property ResearchIndex $research
 * @property Sout $sout
 * @property Status $status
 */
class SoutResearch extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sout_research';
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
            [['sout_id', 'research_id', 'status_id'], 'required'],
            [['sout_id', 'research_id', 'status_id'], 'integer'],
            [['research_id'], 'exist', 'skipOnError' => true, 'targetClass' => ResearchIndex::className(), 'targetAttribute' => ['research_id' => 'id']],
            [['sout_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sout::className(), 'targetAttribute' => ['sout_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sout_id' => 'Внешний идентификатор СОУТ/ПК',
            'research_id' => 'Внешний идентификатор показателей',
            'status_id' => 'Внешний идентификатор статуса показателя СОУТ/ПК',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResearch()
    {
        return $this->hasOne(ResearchIndex::className(), ['id' => 'research_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSout()
    {
        return $this->hasOne(Sout::className(), ['id' => 'sout_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
