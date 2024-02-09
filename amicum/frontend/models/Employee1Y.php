<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "employee_1".
 *
 * @property int $id
 * @property int $PERNR
 * @property string $last_name
 * @property string $first_name
 * @property string $patronymic
 * @property int $gender
 * @property string $birthdate
 */
class Employee1Y extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'employee_1';
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
            [['PERNR', 'last_name', 'first_name', 'gender', 'birthdate'], 'required'],
            [['PERNR', 'gender'], 'integer'],
            [['birthdate'], 'safe'],
            [['last_name', 'first_name', 'patronymic'], 'string', 'max' => 50],
            [['PERNR'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'PERNR' => 'Pernr',
            'last_name' => 'Last Name',
            'first_name' => 'First Name',
            'patronymic' => 'Patronymic',
            'gender' => 'Gender',
            'birthdate' => 'Birthdate',
        ];
    }
}
