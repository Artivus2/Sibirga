<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "department_parameter_summary".
 *
 * @property int $id ключ таблицы
 * @property int $company_department_id ключ связки компаний и департамента
 * @property int $parameter_id ключ параметра департамента
 * @property int $parameter_type_id ключ типа параметра
 * @property string $date_time время обновления параметра
 * @property int $value значение сводного параметра
 *
 * @property ParameterType $parameterType
 * @property CompanyDepartment $companyDepartment
 * @property Parameter $parameter
 */
class DepartmentParameterSummary extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'department_parameter_summary';
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
            [['id', 'company_department_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['id', 'company_department_id', 'parameter_id', 'parameter_type_id', 'value'], 'integer'],
            [['date_time'], 'safe'],
            [['id'], 'unique'],
            [['company_department_id', 'parameter_id', 'parameter_type_id'], 'unique', 'targetAttribute' => ['company_department_id', 'parameter_id', 'parameter_type_id']],
            [['parameter_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParameterType::className(), 'targetAttribute' => ['parameter_type_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ таблицы',
            'company_department_id' => 'ключ связки компаний и департамента',
            'parameter_id' => 'ключ параметра департамента',
            'parameter_type_id' => 'ключ типа параметра',
            'date_time' => 'время обновления параметра',
            'value' => 'значение сводного параметра',
        ];
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
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParameter()
    {
        return $this->hasOne(Parameter::className(), ['id' => 'parameter_id']);
    }
}
