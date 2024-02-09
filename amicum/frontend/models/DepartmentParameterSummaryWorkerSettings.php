<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "department_parameter_summary_worker_settings".
 *
 * @property int $department_parameter_id ключ сводного параметра
 * @property int $employee_id Ключ человека, т.к. может быть у одного человека несколько должностей, то рабочее место мы привязываем к конкретному человеку.
 * @property int $status_id Статус отображения параметра на панели уведомлений (да или нет)
 * @property string $date_time дата смены статуса
 * @property int $read_message_status прочтено/не прочтено уведомление пользователем
 * @property int $id
 *
 * @property DepartmentParameter $departmentParameter
 * @property Employee $employee
 * @property Status $status
 */
class DepartmentParameterSummaryWorkerSettings extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'department_parameter_summary_worker_settings';
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
            [['department_parameter_id', 'employee_id'], 'required'],
            [['department_parameter_id', 'employee_id', 'status_id', 'read_message_status'], 'integer'],
            [['date_time'], 'safe'],
            [['department_parameter_id', 'employee_id'], 'unique', 'targetAttribute' => ['department_parameter_id', 'employee_id']],
            [['department_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => DepartmentParameter::className(), 'targetAttribute' => ['department_parameter_id' => 'id']],
            [['employee_id'], 'exist', 'skipOnError' => true, 'targetClass' => Employee::className(), 'targetAttribute' => ['employee_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'department_parameter_id' => 'ключ сводного параметра',
            'employee_id' => 'Ключ человека, т.к. может быть у одного человека несколько должностей, то рабочее место мы привязываем к конкретному человеку.',
            'status_id' => 'Статус отображения параметра на панели уведомлений (да или нет)',
            'date_time' => 'дата смены статуса',
            'read_message_status' => 'прочтено/не прочтено уведомление пользователем',
            'id' => 'ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentParameter()
    {
        return $this->hasOne(DepartmentParameter::className(), ['id' => 'department_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmployee()
    {
        return $this->hasOne(Employee::className(), ['id' => 'employee_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
