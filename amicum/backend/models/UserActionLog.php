<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "user_action_log".
 *
 * @property int $id
 * @property string $date_time
 * @property string $metod_amicum
 * @property double $duration
 * @property string $post
 * @property string $errors
 * @property string $table_number
 * @property string $result
 */
class UserActionLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_action_log';
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
            [['metod_amicum'], 'required'],
            [['duration'], 'number'],
            [['errors', 'result'], 'string'],
            [['metod_amicum', 'table_number'], 'string', 'max' => 45],
            [['post'], 'string', 'max' => 255],
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
            'metod_amicum' => 'Metod Amicum',
            'duration' => 'Duration',
            'post' => 'Post',
            'errors' => 'Errors',
            'table_number' => 'Table Number',
            'result' => 'Result',
        ];
    }
}
