<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_siz1".
 *
 * @property int $id
 * @property int $siz_id
 * @property int $worker_id
 * @property string $size
 * @property int $count_issued_siz
 * @property string $date_issue
 * @property string $date_write_off
 */
class WorkerSiz1 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_siz1';
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
            [['siz_id', 'worker_id', 'count_issued_siz'], 'integer'],
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
        ];
    }
}
