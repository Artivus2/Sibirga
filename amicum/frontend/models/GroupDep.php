<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "group_dep".
 *
 * @property int $id Идентификатор таблицы
 * @property int $company_department_id Внешний ключ к справочнику структурных подразделений
 * @property string $description Наименование бригады(по фамилии бригадира)
 *
 * @property CompanyDepartment $companyDepartment
 * @property GroupDepConfig[] $groupDepConfigs
 * @property GroupDepParameter[] $groupDepParameters
 */
class GroupDep extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_dep';
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
            [['company_department_id', 'description'], 'required'],
            [['company_department_id'], 'integer'],
            [['description'], 'string', 'max' => 150],
            [['company_department_id', 'description'], 'unique', 'targetAttribute' => ['company_department_id', 'description']],
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
            'company_department_id' => 'Company Department ID',
            'description' => 'Description',
        ];
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
    public function getGroupDepConfigs()
    {
        return $this->hasMany(GroupDepConfig::className(), ['group_dep_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupDepParameters()
    {
        return $this->hasMany(GroupDepParameter::className(), ['group_dep_id' => 'id']);
    }
}
