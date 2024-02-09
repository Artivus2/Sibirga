<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_collection".
 *
 * @property int $id
 * @property string $last_name
 * @property string $titleObject
 * @property string $date_work дата
 * @property string $titlePlace
 * @property string $titleType
 * @property string $titleKind
 * @property string $titleDepartment
 * @property string $titleCompany
 * @property string $status_worker
 * @property int $type_id
 * @property int $kind_id
 * @property int $dep_id
 * @property int $stat_id
 */
class WorkerCollection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_collection';
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
            [['date_work'], 'safe'],
            [['type_id', 'kind_id', 'dep_id', 'stat_id'], 'integer'],
            [['last_name', 'titleObject', 'titlePlace', 'titleType', 'titleKind', 'titleDepartment', 'titleCompany', 'status_worker'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'last_name' => 'Last Name',
            'titleObject' => 'Title Object',
            'date_work' => 'дата',
            'titlePlace' => 'Title Place',
            'titleType' => 'Title Type',
            'titleKind' => 'Title Kind',
            'titleDepartment' => 'Title Department',
            'titleCompany' => 'Title Company',
            'status_worker' => 'Status Worker',
            'type_id' => 'Type ID',
            'kind_id' => 'Kind ID',
            'dep_id' => 'Dep ID',
            'stat_id' => 'Stat ID',
        ];
    }
}
