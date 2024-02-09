<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "company_department1".
 *
 * @property int $department_id
 * @property int $company_id
 */
class CompanyDepartment1Y extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_department1';
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
            [['company_id'], 'required'],
            [['company_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'department_id' => 'Department ID',
            'company_id' => 'Company ID',
        ];
    }
}
