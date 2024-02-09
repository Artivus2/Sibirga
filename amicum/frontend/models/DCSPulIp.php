<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "DCS_pul_ip".
 *
 * @property int $id
 * @property string $start_ip
 * @property string $end_ip
 * @property int $port
 * @property int $DCS_id
 *
 * @property SettingsDCS $dCS
 */
class DCSPulIp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'DCS_pul_ip';
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
            [['start_ip', 'port', 'DCS_id'], 'required'],
            [['port', 'DCS_id'], 'integer'],
            [['start_ip', 'end_ip'], 'string', 'max' => 15],
            [['DCS_id'], 'exist', 'skipOnError' => true, 'targetClass' => SettingsDCS::className(), 'targetAttribute' => ['DCS_id' => 'id']],
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
            'DCS_id' => 'Dcs ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDCS()
    {
        return $this->hasOne(SettingsDCS::className(), ['id' => 'DCS_id']);
    }
}
