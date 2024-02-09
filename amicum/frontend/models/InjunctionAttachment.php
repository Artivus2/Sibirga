<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "injunction_attachment".
 *
 * @property int $id
 * @property int $injunction_id Внешний ключ предписания
 * @property int $attachment_id Внешний ключ вложения
 *
 * @property Attachment $attachment
 * @property Injunction $injunction
 */
class InjunctionAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'injunction_attachment';
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
            [['injunction_id', 'attachment_id'], 'required'],
            [['injunction_id', 'attachment_id'], 'integer'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['injunction_id'], 'exist', 'skipOnError' => true, 'targetClass' => Injunction::className(), 'targetAttribute' => ['injunction_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'injunction_id' => 'Внешний ключ предписания',
            'attachment_id' => 'Внешний ключ вложения',
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
    public function getInjunction()
    {
        return $this->hasOne(Injunction::className(), ['id' => 'injunction_id']);
    }
}
