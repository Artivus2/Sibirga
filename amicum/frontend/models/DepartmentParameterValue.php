<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "department_parameter_value".
 *
 * @property int $id
 * @property int $department_parameter_id ключ конкретного параметра департамента
 * @property string $date_time время записи значения параметра департамента 
 * @property string $value значение конкретного параметра департамента
 * @property int $status_id статус значения конкретного параметра департамента
 *
 * @property DepartmentParameter $departmentParameter
 * @property Status $status
 */
class DepartmentParameterValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'department_parameter_value';
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
            [['department_parameter_id', 'date_time', 'value', 'status_id'], 'required'],
            [['department_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 45],
            [['department_parameter_id', 'date_time'], 'unique', 'targetAttribute' => ['department_parameter_id', 'date_time']],
            [['department_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => DepartmentParameter::className(), 'targetAttribute' => ['department_parameter_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'department_parameter_id' => 'ключ конкретного параметра департамента',
            'date_time' => 'время записи значения параметра департамента ',
            'value' => 'значение конкретного параметра департамента',
            'status_id' => 'статус значения конкретного параметра департамента',
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
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
