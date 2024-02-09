<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "{{%bpd_package_info}}".
 *
 * @property int $id
 * @property string $ip IP-адрес
 * @property string $package Содержимое пакета
 * @property string $date_time Временная метка
 */
class BpdPackageInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%bpd_package_info}}';
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
            'ip' => 'IP-адрес',
            'package' => 'Содержимое пакета',
            'date_time' => 'Временная метка',
        ];
    }
}
