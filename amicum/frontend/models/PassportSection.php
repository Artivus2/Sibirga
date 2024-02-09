<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "passport_section".
 *
 * @property int $id ключ раздела пасспорт
 * @property string $title наименование раздела 
 *
 * @property PassportAttachment[] $passportAttachments
 */
class PassportSection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'passport_section';
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
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    /**
     * Gets query for [[PassportAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportAttachments()
    {
        return $this->hasMany(PassportAttachment::className(), ['passport_section_id' => 'id']);
    }
}
