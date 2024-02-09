<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "chat_member_config".
 *
 * @property int $id
 * @property string $status_title Статус/подпись работника в профиле
 * @property int $worker_id Ключ работника
 */
class ChatMemberConfig extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_member_config';
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
            [['id'], 'required'],
            [['id', 'worker_id'], 'integer'],
            [['status_title'], 'string', 'max' => 45],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'status_title' => 'Status Title',
            'worker_id' => 'Worker ID',
        ];
    }
}
