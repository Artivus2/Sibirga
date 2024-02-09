<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "department_parameter".
 *
 * @property int $id уникальный ключ конкретного параметра департамента
 * @property int $company_department_id ключ связки департамента и компании
 * @property int $parameter_id ключ параметра
 * @property int $parameter_type_id ключ типа параметра
 *
 * @property CompanyDepartment $companyDepartment
 * @property ParameterType $parameterType
 * @property Parameter $parameter
 * @property DepartmentParameterHandbookValue[] $departmentParameterHandbookValues
 * @property DepartmentParameterSummaryWorkerSettings[] $departmentParameterSummaryWorkerSettings
 * @property Employee[] $employees
 * @property DepartmentParameterValue[] $departmentParameterValues
 */
class DepartmentParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'department_parameter';
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
            [['company_department_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['company_department_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['company_department_id', 'parameter_id', 'parameter_type_id'], 'unique', 'targetAttribute' => ['company_department_id', 'parameter_id', 'parameter_type_id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['parameter_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParameterType::className(), 'targetAttribute' => ['parameter_type_id' => 'id']],
            [['parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'уникальный ключ конкретного параметра департамента',
            'company_department_id' => 'ключ связки департамента и компании',
            'parameter_id' => 'ключ параметра',
            'parameter_type_id' => 'ключ типа параметра',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParameterType()
    {
        return $this->hasOne(ParameterType::className(), ['id' => 'parameter_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParameter()
    {
        return $this->hasOne(Parameter::className(), ['id' => 'parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentParameterHandbookValues()
    {
        return $this->hasMany(DepartmentParameterHandbookValue::className(), ['department_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentParameterSummaryWorkerSettings()
    {
        return $this->hasMany(DepartmentParameterSummaryWorkerSettings::className(), ['department_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmployees()
    {
        return $this->hasMany(Employee::className(), ['id' => 'employee_id'])->viaTable('department_parameter_summary_worker_settings', ['department_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentParameterValues()
    {
        return $this->hasMany(DepartmentParameterValue::className(), ['department_parameter_id' => 'id']);
    }
}
