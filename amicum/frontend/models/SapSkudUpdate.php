<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_skud_update".
 *
 * @property int $id
 * @property int|null $worker_id
 * @property string|null $date_time
 * @property int|null $type_skud
 * @property int|null $num_sync
 * @property int|null $status
 * @property int|null $mine_id
 */
class SapSkudUpdate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_skud_update';
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
            [['worker_id', 'type_skud', 'num_sync', 'status', 'mine_id'], 'integer'],
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
            'mine_id' => 'Mine ID',
        ];
    }
}
