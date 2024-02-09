<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "expertise_history".
 *
 * @property int $id
 * @property int $expertise_id Внешний идентификатор экспертизы
 * @property string $date Дата проведения ЭПБ
 * @property int $status_id Внешний идентификатор статуса экспертизы ЭПБ
 * @property int $wear_period Срок действия ЭПБ
 * @property int $attachment_id Внешний идентификатор вложения
 *
 * @property Attachment $attachment
 * @property Expertise $expertise
 * @property Status $status
 */
class ExpertiseHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'expertise_history';
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
            [['expertise_id', 'date', 'status_id', 'wear_period'], 'required'],
            [['expertise_id', 'status_id', 'wear_period', 'attachment_id'], 'integer'],
            [['date'], 'safe'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['expertise_id'], 'exist', 'skipOnError' => true, 'targetClass' => Expertise::className(), 'targetAttribute' => ['expertise_id' => 'id']],
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
            'expertise_id' => 'Внешний идентификатор экспертизы',
            'date' => 'Дата проведения ЭПБ',
            'status_id' => 'Внешний идентификатор статуса экспертизы ЭПБ',
            'wear_period' => 'Срок действия ЭПБ',
            'attachment_id' => 'Внешний идентификатор вложения',
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
    public function getExpertise()
    {
        return $this->hasOne(Expertise::className(), ['id' => 'expertise_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
