<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "strata_package_source".
 *
 * @property int $id
 * @property string|null $bytes
 * @property string|null $ip
 * @property string|null $date_time 6
 * @property int|null $mine_id ключ шахты
 */
class StrataPackageSource extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'strata_package_source';
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
            [['mine_id'], 'integer'],
            [['bytes'], 'string', 'max' => 19048],
            [['ip'], 'string', 'max' => 15],
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
            'ip' => 'Ip',
            'date_time' => 'Date Time',
            'mine_id' => 'Mine ID',
        ];
    }
}
