<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "update_archive_items".
 *
 * @property int $id
 * @property string $title текст пункта обновления
 * @property int $update_archive_id ключ обновления
 *
 * @property UpdateArchive $updateArchive
 */
class UpdateArchiveItems extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'update_archive_items';
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
            [['title', 'update_archive_id'], 'required'],
            [['update_archive_id'], 'integer'],
            [['title'], 'string', 'max' => 1255],
            [['update_archive_id'], 'exist', 'skipOnError' => true, 'targetClass' => UpdateArchive::className(), 'targetAttribute' => ['update_archive_id' => 'id']],
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
            'update_archive_id' => 'Update Archive ID',
        ];
    }

    /**
     * Gets query for [[UpdateArchive]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUpdateArchive()
    {
        return $this->hasOne(UpdateArchive::className(), ['id' => 'update_archive_id']);
    }
}
