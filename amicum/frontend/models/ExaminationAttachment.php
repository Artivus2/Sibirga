<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "examination_attachment".
 *
 * @property int $id
 * @property int $examination_id
 * @property int $attachment_id
 *
 * @property Examination $examination
 * @property Attachment $attachment
 */
class ExaminationAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'examination_attachment';
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
            [['examination_id', 'attachment_id'], 'required'],
            [['examination_id', 'attachment_id'], 'integer'],
            [['examination_id'], 'exist', 'skipOnError' => true, 'targetClass' => Examination::className(), 'targetAttribute' => ['examination_id' => 'id']],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'examination_id' => 'Examination ID',
            'attachment_id' => 'Attachment ID',
        ];
    }

    /**
     * Gets query for [[Examination]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExamination()
    {
        return $this->hasOne(Examination::className(), ['id' => 'examination_id']);
    }

    /**
     * Gets query for [[Attachment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id']);
    }
}
