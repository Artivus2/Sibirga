<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "test_company_department".
 *
 * @property int $id ключ привязки подразделения к тесту
 * @property int $test_id ключ теста
 * @property int $company_department_id ключ подразделения
 *
 * @property Test $test
 * @property CompanyDepartment $companyDepartment
 */
class TestCompanyDepartment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'test_company_department';
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
            [['test_id', 'company_department_id'], 'required'],
            [['test_id', 'company_department_id'], 'integer'],
            [['test_id', 'company_department_id'], 'unique', 'targetAttribute' => ['test_id', 'company_department_id']],
            [['test_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test::className(), 'targetAttribute' => ['test_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ привязки подразделения к тесту',
            'test_id' => 'ключ теста',
            'company_department_id' => 'ключ подразделения',
        ];
    }

    /**
     * Gets query for [[Test]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTest()
    {
        return $this->hasOne(Test::className(), ['id' => 'test_id']);
    }

    /**
     * Gets query for [[CompanyDepartment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }
}
