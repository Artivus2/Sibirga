<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "web_socket_sync".
 *
 * @property int $id
 * @property string $title
 * @property string $date_time
 */
class WebSocketSync extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'web_socket_sync';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'date_time'], 'required'],
            [['date_time'], 'safe'],
            [['title'], 'string', 'max' => 45],
            [['title', 'date_time'], 'unique', 'targetAttribute' => ['title', 'date_time']],
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
            'date_time' => 'Date Time',
        ];
    }

    /**
     * {@inheritdoc}
     * @return WebSocketSyncQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new WebSocketSyncQuery(get_called_class());
    }
}
