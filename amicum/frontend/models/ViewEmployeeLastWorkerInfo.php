<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_employee_last_worker_info".
 *
 * @property int $worker_id
 * @property string $fio
 * @property string $birthdate дата рождения
 * @property string $gender пол
 * @property string $title Название роли (сокращенное). Например, МГВМ, ГРОЗ и т.д.
 * @property string $qualification
 * @property string $tabel_number табельный номер
 */
class ViewEmployeeLastWorkerInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_employee_last_worker_info';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['worker_id'], 'integer'],
            [['birthdate', 'gender', 'title', 'tabel_number'], 'required'],
            [['birthdate'], 'safe'],
            [['fio'], 'string', 'max' => 152],
            [['gender'], 'string', 'max' => 1],
            [['title'], 'string', 'max' => 255],
            [['qualification'], 'string', 'max' => 10],
            [['tabel_number'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'worker_id' => 'Worker ID',
            'fio' => 'Fio',
            'birthdate' => 'Birthdate',
            'gender' => 'Gender',
            'title' => 'Title',
            'qualification' => 'Qualification',
            'tabel_number' => 'Tabel Number',
        ];
    }

    /**
     * {@inheritdoc}
     * @return ViewEmployeeLastWorkerInfoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ViewEmployeeLastWorkerInfoQuery(get_called_class());
    }
}
