<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "expertise_attachment".
 *
 * @property int $id
 * @property int $attachment_id Внешний ключ вложения
 * @property int $expertise_id Внешний ключ экспертизы
 *
 * @property Attachment $attachment
 * @property Expertise $expertise
 */
class ExpertiseAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'expertise_attachment';
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
            [['attachment_id', 'expertise_id'], 'required'],
            [['attachment_id', 'expertise_id'], 'integer'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['expertise_id'], 'exist', 'skipOnError' => true, 'targetClass' => Expertise::className(), 'targetAttribute' => ['expertise_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'attachment_id' => 'Внешний ключ вложения',
            'expertise_id' => 'Внешний ключ экспертизы',
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
}
