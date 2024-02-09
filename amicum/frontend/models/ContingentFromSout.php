<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "contingent_from_sout".
 *
 * @property int $id
 * @property int $company_department_id Внешний идентификатор участка
 * @property int $role_id Внешний идентификатор роли
 * @property int $sout_id Внешний идентификатор СОУТа
 *
 * @property CompanyDepartment $companyDepartment
 * @property Role $role
 * @property Sout $sout
 * @property ContingentHarmfulFactorSout[] $contingentHarmfulFactorSouts
 */
class ContingentFromSout extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contingent_from_sout';
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
            [['company_department_id', 'role_id', 'sout_id'], 'required'],
            [['company_department_id', 'role_id', 'sout_id'], 'integer'],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
            [['sout_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sout::className(), 'targetAttribute' => ['sout_id' => 'id']],
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
            'role_id' => 'Внешний идентификатор роли',
            'sout_id' => 'Внешний идентификатор СОУТа',
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
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSout()
    {
        return $this->hasOne(Sout::className(), ['id' => 'sout_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContingentHarmfulFactorSouts()
    {
        return $this->hasMany(ContingentHarmfulFactorSout::className(), ['contingent_from_sout_id' => 'id']);
    }
}
