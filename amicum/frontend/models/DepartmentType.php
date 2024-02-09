<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "department_type".
 *
 * @property int $id
 * @property string $title
 *
 * @property CompanyDepartment[] $companyDepartments
 */
class DepartmentType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'department_type';
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
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartments()
    {
        return $this->hasMany(CompanyDepartment::className(), ['department_type_id' => 'id']);
    }
}
