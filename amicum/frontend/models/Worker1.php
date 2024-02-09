<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker1".
 *
 * @property int $id
 * @property int $employee_id
 * @property int $position_id
 * @property int $company_department_id
 * @property string $tabel_number
 * @property string $date_start
 * @property string $date_end
 * @property int $mine_id
 */
class Worker1 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker1';
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
            [['employee_id', 'position_id', 'company_department_id', 'tabel_number'], 'required'],
            [['employee_id', 'position_id', 'company_department_id', 'mine_id'], 'integer'],
            [['date_start', 'date_end'], 'safe'],
            [['tabel_number'], 'string', 'max' => 50],
            [['employee_id', 'position_id', 'company_department_id'], 'unique', 'targetAttribute' => ['employee_id', 'position_id', 'company_department_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'employee_id' => 'Employee ID',
            'position_id' => 'Position ID',
            'company_department_id' => 'Company Department ID',
            'tabel_number' => 'Tabel Number',
            'date_start' => 'Date Start',
            'date_end' => 'Date End',
            'mine_id' => 'Mine ID',
        ];
    }
}
