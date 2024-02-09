<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "strata_ip".
 *
 * @property int $id
 * @property string $start_ip
 * @property string $end_ip
 * @property int $port
 */
class StrataIp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'strata_ip';
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
            [['start_ip', 'port'], 'required'],
            [['port'], 'integer'],
            [['start_ip', 'end_ip'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'start_ip' => 'Start Ip',
            'end_ip' => 'End Ip',
            'port' => 'Port',
        ];
    }
}
