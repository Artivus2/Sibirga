<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "dashboard_config".
 *
 * @property int $id
 * @property int $user_id ключ пользователя системы Амикум
 * @property string $date_time Дата и время сохранения конфигурации
 * @property string|null $config_json конфиг интерактивного рабочего стола
 */
class DashboardConfig extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dashboard_config';
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
            [['user_id', 'date_time'], 'required'],
            [['user_id'], 'integer'],
            [['date_time'], 'safe'],
            [['config_json'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'date_time' => 'Date Time',
            'config_json' => 'Config Json',
        ];
    }
}
