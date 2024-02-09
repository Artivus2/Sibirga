<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "injunction_1".
 *
 * @property int $id
 * @property int $place_id
 * @property int $worker_id
 * @property int $kind_document_id
 * @property int $rtn_statistic_status_id
 * @property int $checking_id
 * @property string $description
 * @property int $status_id
 * @property int $observation_number
 * @property int $company_department_id
 */
class Injunction1 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'injunction_1';
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
            [['place_id', 'worker_id', 'kind_document_id', 'rtn_statistic_status_id', 'checking_id', 'status_id', 'observation_number', 'company_department_id'], 'integer'],
            [['description'], 'string', 'max' => 805],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'place_id' => 'Place ID',
            'worker_id' => 'Worker ID',
            'kind_document_id' => 'Kind Document ID',
            'rtn_statistic_status_id' => 'Rtn Statistic Status ID',
            'checking_id' => 'Checking ID',
            'description' => 'Description',
            'status_id' => 'Status ID',
            'observation_number' => 'Observation Number',
            'company_department_id' => 'Company Department ID',
        ];
    }
}
