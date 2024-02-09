<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "company_department_1".
 *
 * @property int $id
 * @property int $department_id внешний ключ подразделений
 * @property int $company_id внешний ключ компании
 * @property int $department_type_id тип подразделений
 *
 * @property Company1 $company
 * @property Department1 $department
 */
class CompanyDepartment1 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_department_1';
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
            [['department_id', 'company_id'], 'required'],
            [['department_id', 'company_id', 'department_type_id'], 'integer'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company1::className(), 'targetAttribute' => ['company_id' => 'id']],
            [['department_id'], 'exist', 'skipOnError' => true, 'targetClass' => Department1::className(), 'targetAttribute' => ['department_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'department_id' => 'внешний ключ подразделений',
            'company_id' => 'внешний ключ компании',
            'department_type_id' => 'тип подразделений',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company1::className(), ['id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDepartment()
    {
        return $this->hasOne(Department1::className(), ['id' => 'department_id']);
    }
}
