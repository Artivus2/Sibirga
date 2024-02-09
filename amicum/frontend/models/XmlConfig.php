<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "xml_config".
 *
 * @property int $id
 * @property int $xml_model_id
 * @property int $xml_send_type_id
 * @property string $address
 * @property int $time_period
 * @property int $time_unit_id
 * @property string $date_start
 * @property string $date_end
 * @property int $event_id
 * @property int $group_alarm_id группа опопвещения
 * @property string $description описание кому принадлежит адрес
 * @property int $position число на котором срабатывает отправка смс данному абоненту
 *
 * @property XmlTimeUnit $timeUnit
 * @property XmlSendType $xmlSendType
 * @property XmlModel $xmlModel
 * @property GroupAlarm $groupAlarm
 * @property Event $event
 */
class XmlConfig extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'xml_config';
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
            [['xml_model_id', 'xml_send_type_id', 'time_period', 'time_unit_id', 'date_start', 'date_end', 'event_id'], 'required'],
            [['xml_model_id', 'xml_send_type_id', 'time_period', 'time_unit_id', 'event_id', 'group_alarm_id', 'position'], 'integer'],
            [['date_start', 'date_end'], 'safe'],
            [['address', 'description'], 'string', 'max' => 255],
            [['time_unit_id'], 'exist', 'skipOnError' => true, 'targetClass' => XmlTimeUnit::className(), 'targetAttribute' => ['time_unit_id' => 'id']],
            [['xml_send_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => XmlSendType::className(), 'targetAttribute' => ['xml_send_type_id' => 'id']],
            [['xml_model_id'], 'exist', 'skipOnError' => true, 'targetClass' => XmlModel::className(), 'targetAttribute' => ['xml_model_id' => 'id']],
            [['group_alarm_id'], 'exist', 'skipOnError' => true, 'targetClass' => GroupAlarm::className(), 'targetAttribute' => ['group_alarm_id' => 'id']],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Event::className(), 'targetAttribute' => ['event_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'xml_model_id' => 'Xml Model ID',
            'xml_send_type_id' => 'Xml Send Type ID',
            'address' => 'Address',
            'time_period' => 'Time Period',
            'time_unit_id' => 'Time Unit ID',
            'date_start' => 'Date Start',
            'date_end' => 'Date End',
            'event_id' => 'Event ID',
            'group_alarm_id' => 'группа опопвещения',
            'description' => 'описание кому принадлежит адрес',
            'position' => 'число на котором срабатывает отправка смс данному абоненту',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTimeUnit()
    {
        return $this->hasOne(XmlTimeUnit::className(), ['id' => 'time_unit_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getXmlSendType()
    {
        return $this->hasOne(XmlSendType::className(), ['id' => 'xml_send_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getXmlModel()
    {
        return $this->hasOne(XmlModel::className(), ['id' => 'xml_model_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupAlarm()
    {
        return $this->hasOne(GroupAlarm::className(), ['id' => 'group_alarm_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvent()
    {
        return $this->hasOne(Event::className(), ['id' => 'event_id']);
    }
}
