<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "employee".
 *
 * @property int $id Ключ таблиц сотрдуников - людей
 * @property string $last_name Фамилия
 * @property string $first_name Имя
 * @property string $patronymic Отчетство
 * @property string $gender пол
 * @property string $birthdate дата рождения
 * @property string date_time_sync
 * @property string link_1c
 *
 * @property DepartmentParameterSummaryWorkerSettings[] $departmentParameterSummaryWorkerSettings
 * @property DepartmentParameter[] $departmentParameters
 * @property EmployeeLocation[] $employeeLocations
 * @property PhoneNumber[] $phoneNumbers
 * @property Worker[] $workers
 */
class Employee extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'employee';
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
            [['last_name', 'first_name'], 'required'],
            [['birthdate', 'date_time_sync'], 'safe'],
            [['link_1c'], 'string', 'max' => 100],
            [['last_name', 'first_name', 'patronymic'], 'string', 'max' => 50],
            [['gender'], 'string', 'max' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Ключ таблиц сотрдуников - людей',
            'last_name' => 'Фамилия',
            'first_name' => 'Имя',
            'patronymic' => 'Отчетство',
            'gender' => 'пол',
            'birthdate' => 'дата рождения',
            'link_1c' => 'link_1c',
            'date_time_sync' => 'date_time_sync',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentParameterSummaryWorkerSettings()
    {
        return $this->hasMany(DepartmentParameterSummaryWorkerSettings::className(), ['employee_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentParameters()
    {
        return $this->hasMany(DepartmentParameter::className(), ['id' => 'department_parameter_id'])->viaTable('department_parameter_summary_worker_settings', ['employee_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmployeeLocations()
    {
        return $this->hasMany(EmployeeLocation::className(), ['employee_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhoneNumbers()
    {
        return $this->hasMany(PhoneNumber::className(), ['employee_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkers()
    {
        return $this->hasMany(Worker::className(), ['employee_id' => 'id']);
    }
}
