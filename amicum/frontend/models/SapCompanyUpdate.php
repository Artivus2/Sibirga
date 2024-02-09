<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_company_update".
 *
 * @property int $id
 * @property int $id_comp
 * @property string $title
 * @property int $upper_company_id
 * @property int $num_sync
 * @property int $status
 * @property string $date_modified
 */
class SapCompanyUpdate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_company_update';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_comp', 'upper_company_id', 'num_sync', 'status'], 'integer'],
            [['date_modified'], 'safe'],
            [['title'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_comp' => 'Id Comp',
            'title' => 'Title',
            'upper_company_id' => 'Upper Company ID',
            'num_sync' => 'Num Sync',
            'status' => 'Status',
            'date_modified' => 'Date Modified',
        ];
    }
}
