<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_reason".
 *
 * @property int $id ключ причины отказа/события
 * @property string $title Название причины отказа/события
 *
 * @property EventJournalStatus[] $eventJournalStatuses
 * @property EventStatus[] $eventStatuses
 */
class KindReason extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_reason';
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
            'id' => 'ключ причины отказа/события',
            'title' => 'Название причины отказа/события',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventJournalStatuses()
    {
        return $this->hasMany(EventJournalStatus::className(), ['kind_reason_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventStatuses()
    {
        return $this->hasMany(EventStatus::className(), ['kind_reason_id' => 'id']);
    }
}
