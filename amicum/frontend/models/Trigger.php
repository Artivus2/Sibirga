<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "trigger".
 *
 * @property int $id
 * @property string $title
 * @property int $asmtp_id
 * @property int $connect_string_id
 *
 * @property OperationRegulation[] $operationRegulations
 * @property OperationRegulationFact[] $operationRegulationFacts
 * @property PlaActivityFact[] $plaActivityFacts
 * @property Asmtp $asmtp
 * @property ConnectString $connectString
 */
class Trigger extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'trigger';
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
            [['title', 'asmtp_id', 'connect_string_id'], 'required'],
            [['asmtp_id', 'connect_string_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['asmtp_id'], 'exist', 'skipOnError' => true, 'targetClass' => Asmtp::className(), 'targetAttribute' => ['asmtp_id' => 'id']],
            [['connect_string_id'], 'exist', 'skipOnError' => true, 'targetClass' => ConnectString::className(), 'targetAttribute' => ['connect_string_id' => 'id']],
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
            'asmtp_id' => 'Asmtp ID',
            'connect_string_id' => 'Connect String ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulations()
    {
        return $this->hasMany(OperationRegulation::className(), ['trigger_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationFacts()
    {
        return $this->hasMany(OperationRegulationFact::className(), ['trigger_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaActivityFacts()
    {
        return $this->hasMany(PlaActivityFact::className(), ['trigger_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAsmtp()
    {
        return $this->hasOne(Asmtp::className(), ['id' => 'asmtp_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConnectString()
    {
        return $this->hasOne(ConnectString::className(), ['id' => 'connect_string_id']);
    }
}
