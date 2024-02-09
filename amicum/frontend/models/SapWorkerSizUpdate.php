<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_worker_siz_update".
 *
 * @property int $id
 * @property int $siz_id
 * @property int $worker_id
 * @property string $size
 * @property int $count_issued_siz
 * @property string $date_issue
 * @property string $date_write_off
 * @property int $status_id
 * @property int $num_sync
 * @property int $status
 */
class SapWorkerSizUpdate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_worker_siz_update';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['siz_id', 'worker_id', 'count_issued_siz', 'status_id', 'num_sync', 'status'], 'integer'],
            [['date_issue', 'date_write_off'], 'safe'],
            [['size'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'siz_id' => 'Siz ID',
            'worker_id' => 'Worker ID',
            'size' => 'Size',
            'count_issued_siz' => 'Count Issued Siz',
            'date_issue' => 'Date Issue',
            'date_write_off' => 'Date Write Off',
            'status_id' => 'Status ID',
            'num_sync' => 'Num Sync',
            'status' => 'Status',
        ];
    }
}
