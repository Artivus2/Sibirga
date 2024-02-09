<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sout_attachment".
 *
 * @property int $id
 * @property int $sout_id Внешний идентификатор СОУТ
 * @property int $attachment_id Внешний идентификатор вложения
 *
 * @property Attachment $attachment
 * @property Sout $sout
 */
class SoutAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sout_attachment';
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
            [['sout_id', 'attachment_id'], 'required'],
            [['sout_id', 'attachment_id'], 'integer'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['sout_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sout::className(), 'targetAttribute' => ['sout_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sout_id' => 'Внешний идентификатор СОУТ',
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
    public function getSout()
    {
        return $this->hasOne(Sout::className(), ['id' => 'sout_id']);
    }
}
