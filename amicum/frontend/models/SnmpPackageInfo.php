<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "snmp_package_info".
 *
 * @property int $id
 * @property string $ip IP адрес коммутатора
 * @property string $package Содержимое пакета
 * @property string $date_time Время получения пакета с устройства
 */
class SnmpPackageInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'snmp_package_info';
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
            [['ip', 'package', 'date_time'], 'required'],
            [['date_time'], 'safe'],
            [['ip'], 'string', 'max' => 45],
            [['package'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'IP адрес коммутатора',
            'package' => 'Содержимое пакета',
            'date_time' => 'Время получения пакета с устройства',
        ];
    }
}
