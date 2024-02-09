<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "situation_journal_send_status".
 *
 * @property int $id ключ отправки ситуации
 * @property int $situation_journal_id ключ журнала ситуаций
 * @property int $status_id ключ статуса (оптравлено/не отправлено)
 * @property string $date_time время отправки сообщения
 * @property int $xml_send_type_id ключ типа отправки сообщения
 *
 * @property SituationJournal $situationJournal
 * @property Status $status
 * @property XmlSendType $xmlSendType
 */
class SituationJournalSendStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'situation_journal_send_status';
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
            [['situation_journal_id', 'status_id', 'date_time', 'xml_send_type_id'], 'required'],
            [['situation_journal_id', 'status_id', 'xml_send_type_id'], 'integer'],
            [['date_time'], 'safe'],
            [['situation_journal_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationJournal::className(), 'targetAttribute' => ['situation_journal_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['xml_send_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => XmlSendType::className(), 'targetAttribute' => ['xml_send_type_id' => 'id']],
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
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
            'xml_send_type_id' => 'Xml Send Type ID',
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
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getXmlSendType()
    {
        return $this->hasOne(XmlSendType::className(), ['id' => 'xml_send_type_id']);
    }
}
