<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "{{%strata_package_info}}".
 *
 * @property int $id
 * @property string $bytes
 * @property string $date_time DATETIME(6)
 * @property int $net_id
 * @property string $ip_gateway
 */
class StrataPackageInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%strata_package_info}}';
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
            [['bytes', 'date_time', 'net_id', 'ip_gateway'], 'required'],
            [['date_time'], 'safe'],
            [['net_id'], 'integer'],
            [['bytes'], 'string', 'max' => 255],
            [['ip_gateway'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'bytes' => 'Bytes',
            'date_time' => 'DATETIME(6)',
            'net_id' => 'Net ID',
            'ip_gateway' => 'Ip Gateway',
        ];
    }
}
