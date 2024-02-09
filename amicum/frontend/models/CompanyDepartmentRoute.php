<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "company_department_route".
 *
 * @property int $id
 * @property int $company_department_id Внешний идентификатор участка
 * @property string $title Наименование маршрута
 * @property string $way_of_movement Способ передвижения
 *
 * @property CompanyDepartment $companyDepartment
 */
class CompanyDepartmentRoute extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_department_route';
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
            [['company_department_id', 'title', 'way_of_movement'], 'required'],
            [['company_department_id'], 'integer'],
            [['title', 'way_of_movement'], 'string', 'max' => 255],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_department_id' => 'Внешний идентификатор участка',
            'title' => 'Наименование маршрута',
            'way_of_movement' => 'Способ передвижения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }
}
