<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "passport_attachment".
 *
 * @property int $id ключ эскиза
 * @property int $passport_id внешний ключ справочника паспартов
 * @property int $attachment_id ключ во вложении
 * @property int $passport_section_id ключ раздел паспорта
 * @property string|null $title название документа вложения у паспорта
 *
 * @property Attachment $attachment
 * @property Passport $passport
 * @property PassportSection $passportSection
 */
class PassportAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'passport_attachment';
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
            [['passport_id', 'attachment_id', 'passport_section_id'], 'required'],
            [['passport_id', 'attachment_id', 'passport_section_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['passport_id'], 'exist', 'skipOnError' => true, 'targetClass' => Passport::className(), 'targetAttribute' => ['passport_id' => 'id']],
            [['passport_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => PassportSection::className(), 'targetAttribute' => ['passport_section_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'passport_id' => 'Passport ID',
            'attachment_id' => 'Attachment ID',
            'passport_section_id' => 'Passport Section ID',
            'title' => 'Title',
        ];
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

    /**
     * Gets query for [[Passport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassport()
    {
        return $this->hasOne(Passport::className(), ['id' => 'passport_id']);
    }

    /**
     * Gets query for [[PassportSection]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportSection()
    {
        return $this->hasOne(PassportSection::className(), ['id' => 'passport_section_id']);
    }
}
