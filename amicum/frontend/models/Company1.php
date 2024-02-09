<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "company1".
 *
 * @property int $id в Oracle = OBJID
 * @property string $title
 * @property int $upper_company_id
 *
 * @property CompanyDepartment1[] $companyDepartment1s
 */
class Company1 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company1';
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
            [['id'], 'required'],
            [['id', 'upper_company_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'в Oracle = OBJID',
            'title' => 'Title',
            'upper_company_id' => 'Upper Company ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment1s()
    {
        return $this->hasMany(CompanyDepartment1::className(), ['company_id' => 'id']);
    }
}
