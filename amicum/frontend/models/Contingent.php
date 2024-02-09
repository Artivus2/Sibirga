<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "contingent".
 *
 * @property int $id
 * @property int $company_department_id Ключ участка
 * @property int $role_id ключ професси, для которой вредный фактор
 * @property int|null $period периодичность, с которой будет происходить мед. осмотр
 * @property string|null $year_contingent год в котором актуален список контингента
 * @property int $status Статус, актуально/неактуально (1/0)
 *
 * @property CompanyDepartment $companyDepartment
 * @property Role $role
 * @property FactorsOfContingent[] $factorsOfContingents
 * @property PhysicalWorker[] $physicalWorkers
 */
class Contingent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contingent';
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
            [['company_department_id', 'role_id', 'status'], 'required'],
            [['company_department_id', 'role_id', 'period', 'status'], 'integer'],
            [['year_contingent'], 'safe'],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_department_id' => 'Ключ участка',
            'role_id' => 'ключ професси, для которой вредный фактор',
            'period' => 'периодичность, с которой будет происходить мед. осмотр',
            'year_contingent' => 'год в котором актуален список контингента',
            'status' => 'Статус, актуально/неактуально (1/0)',
        ];
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

    /**
     * Gets query for [[Role]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
    }

    /**
     * Gets query for [[FactorsOfContingents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFactorsOfContingents()
    {
        return $this->hasMany(FactorsOfContingent::className(), ['contingent_id' => 'id']);
    }

    /**
     * Gets query for [[PhysicalWorkers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalWorkers()
    {
        return $this->hasMany(PhysicalWorker::className(), ['contingent_id' => 'id']);
    }
}
