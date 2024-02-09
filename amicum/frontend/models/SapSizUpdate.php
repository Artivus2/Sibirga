<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_siz_update".
 *
 * @property int $id
 * @property int $n_nomencl
 * @property string $name_nomencl
 * @property int $unit_id
 * @property int $type_cost
 * @property string $name_cost
 * @property string $date_beg_give
 * @property string $date_end_give
 * @property int $sign_winter
 * @property string $date_modified
 * @property int $num_sync
 * @property int $status
 */
class SapSizUpdate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_siz_update';
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
            [['n_nomencl', 'unit_id', 'type_cost', 'sign_winter', 'num_sync', 'status'], 'integer'],
            [['date_beg_give', 'date_end_give', 'date_modified'], 'safe'],
            [['name_nomencl', 'name_cost'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'n_nomencl' => 'N Nomencl',
            'name_nomencl' => 'Name Nomencl',
            'unit_id' => 'Unit ID',
            'type_cost' => 'Type Cost',
            'name_cost' => 'Name Cost',
            'date_beg_give' => 'Date Beg Give',
            'date_end_give' => 'Date End Give',
            'sign_winter' => 'Sign Winter',
            'date_modified' => 'Date Modified',
            'num_sync' => 'Num Sync',
            'status' => 'Status',
        ];
    }
}
