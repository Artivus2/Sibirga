<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "user_access_log".
 *
 * @property int $id
 * @property string $date_time
 * @property string $session_amicum
 * @property string $tabel_number
 * @property int $access_status
 */
class UserAccessLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_access_log';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_amicum_log');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date_time'], 'safe'],
            [['session_amicum'], 'string'],
            [['access_status'], 'integer'],
            [['tabel_number'], 'string', 'max' => 255],
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
            'session_amicum' => 'Session Amicum',
            'tabel_number' => 'Tabel Number',
            'access_status' => 'Access Status',
        ];
    }
}
