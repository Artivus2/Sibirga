<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_asu_worker_siz_update".
 *
 * @property int $id
 * @property int $work_wear_id
 * @property int $n_nomencl
 * @property int $tabn
 * @property int $objid
 * @property string $date_give
 * @property string $name_size
 * @property string $date_return
 * @property string $date_written
 * @property int $sign_written
 * @property int $working_life
 * @property string $date_modified
 */
class SapAsuWorkerSizUpdate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_asu_worker_siz_update';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['work_wear_id', 'n_nomencl', 'date_give', 'date_return'], 'required'],
            [['work_wear_id', 'n_nomencl', 'tabn', 'objid', 'sign_written', 'working_life'], 'integer'],
            [['date_give', 'date_return', 'date_written', 'date_modified'], 'safe'],
            [['name_size'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'work_wear_id' => 'Work Wear ID',
            'n_nomencl' => 'N Nomencl',
            'tabn' => 'Tabn',
            'objid' => 'Objid',
            'date_give' => 'Date Give',
            'name_size' => 'Name Size',
            'date_return' => 'Date Return',
            'date_written' => 'Date Written',
            'sign_written' => 'Sign Written',
            'working_life' => 'Working Life',
            'date_modified' => 'Date Modified',
        ];
    }
}
