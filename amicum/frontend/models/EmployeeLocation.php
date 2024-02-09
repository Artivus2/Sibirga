<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "employee_location".
 *
 * @property int $id
 * @property int $employee_id
 * @property double $x
 * @property double $y
 * @property double $z
 *
 * @property Employee $employee
 */
class EmployeeLocation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'employee_location';
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
            [['employee_id', 'x', 'y', 'z'], 'required'],
            [['employee_id'], 'integer'],
            [['x', 'y', 'z'], 'number'],
            [['employee_id'], 'exist', 'skipOnError' => true, 'targetClass' => Employee::className(), 'targetAttribute' => ['employee_id' => 'id']],
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
            'x' => 'X',
            'y' => 'Y',
            'z' => 'Z',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmployee()
    {
        return $this->hasOne(Employee::className(), ['id' => 'employee_id']);
    }
}
