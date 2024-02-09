<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "ws_log".
 *
 * @property int $id
 * @property string|null $error_string
 * @property string|null $date_time
 */
class WsLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ws_log';
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
            [['error_string'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'error_string' => 'Error String',
            'date_time' => 'Date Time',
        ];
    }
}
