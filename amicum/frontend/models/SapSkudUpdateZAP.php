<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_skud_update_ZAP".
 *
 * @property int $id
 * @property int $worker_id
 * @property string $date_time
 * @property int $type_skud
 * @property int $num_sync
 * @property int $status
 */
class SapSkudUpdateZAP extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_skud_update_ZAP';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['worker_id', 'type_skud', 'num_sync', 'status'], 'integer'],
            [['date_time'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_id' => 'Worker ID',
            'date_time' => 'Date Time',
            'type_skud' => 'Type Skud',
            'num_sync' => 'Num Sync',
            'status' => 'Status',
        ];
    }
}
