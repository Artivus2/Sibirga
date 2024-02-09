<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "physical_schedule_attachment".
 *
 * @property int $id
 * @property int $physical_schedule_id внешний идентификатор графика медосмотров
 * @property int $attachment_id внешний идентификатор вложения
 *
 * @property Attachment $attachment
 * @property PhysicalSchedule $physicalSchedule
 */
class PhysicalScheduleAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'physical_schedule_attachment';
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
            [['physical_schedule_id', 'attachment_id'], 'required'],
            [['physical_schedule_id', 'attachment_id'], 'integer'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['physical_schedule_id'], 'exist', 'skipOnError' => true, 'targetClass' => PhysicalSchedule::className(), 'targetAttribute' => ['physical_schedule_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'physical_schedule_id' => 'внешний идентификатор графика медосмотров',
            'attachment_id' => 'внешний идентификатор вложения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalSchedule()
    {
        return $this->hasOne(PhysicalSchedule::className(), ['id' => 'physical_schedule_id']);
    }
}
