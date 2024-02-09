<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "occupational_illness_attachment".
 *
 * @property int $id
 * @property int $occupational_illness_id Внешний идентификатор проф заболевания
 * @property int $attachment_id Внешний идентификатор вложения
 *
 * @property Attachment $attachment
 * @property OccupationalIllness $occupationalIllness
 */
class OccupationalIllnessAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'occupational_illness_attachment';
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
            [['occupational_illness_id', 'attachment_id'], 'required'],
            [['occupational_illness_id', 'attachment_id'], 'integer'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['occupational_illness_id'], 'exist', 'skipOnError' => true, 'targetClass' => OccupationalIllness::className(), 'targetAttribute' => ['occupational_illness_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'occupational_illness_id' => 'Внешний идентификатор проф заболевания',
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
    public function getOccupationalIllness()
    {
        return $this->hasOne(OccupationalIllness::className(), ['id' => 'occupational_illness_id']);
    }
}
