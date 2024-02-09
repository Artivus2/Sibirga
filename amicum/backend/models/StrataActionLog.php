<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "strata_action_log".
 *
 * @property int $id
 * @property string|null $date_time
 * @property string $metod_amicum
 * @property float|null $duration
 * @property string|null $post
 * @property string|null $errors
 * @property string|null $table_number
 * @property string|null $result
 */
class StrataActionLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'strata_action_log';
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
            [['post', 'errors', 'result'], 'string'],
            [['metod_amicum'], 'string', 'max' => 255],
            [['table_number'], 'string', 'max' => 45],
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
