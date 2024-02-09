<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "update_archive".
 *
 * @property int $id
 * @property string $date_time Дата выхода обновления
 * @property string|null $title название обновления/релиз
 * @property string|null $release_number Номер релиза
 *
 * @property UpdateArchiveItems[] $updateArchiveItems
 * @property UpdateArchiveWorker[] $updateArchiveWorkers
 */
class UpdateArchive extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'update_archive';
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
            [['date_time'], 'required'],
            [['date_time'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['release_number'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_time' => 'Date Time',
            'title' => 'Title',
            'release_number' => 'Release Number',
        ];
    }

    /**
     * Gets query for [[UpdateArchiveItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUpdateArchiveItems()
    {
        return $this->hasMany(UpdateArchiveItems::className(), ['update_archive_id' => 'id']);
    }

    /**
     * Gets query for [[UpdateArchiveWorkers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUpdateArchiveWorkers()
    {
        return $this->hasMany(UpdateArchiveWorker::className(), ['update_archive_id' => 'id']);
    }
}
