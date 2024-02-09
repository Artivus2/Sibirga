<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "shift_department".
 *
 * @property int $id
 * @property string $date_time
 * @property int $plan_shift_id
 * @property int $company_department_id
 *
 * @property CompanyDepartment $companyDepartment
 * @property PlanShift $planShift
 */
class ShiftDepartment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shift_department';
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
            [['date_time', 'plan_shift_id', 'company_department_id'], 'required'],
            [['date_time'], 'safe'],
            [['plan_shift_id', 'company_department_id'], 'integer'],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['plan_shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlanShift::className(), 'targetAttribute' => ['plan_shift_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_time' => 'Date Time',
            'plan_shift_id' => 'Plan Shift ID',
            'company_department_id' => 'Company Department ID',
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
    public function getPlanShift()
    {
        return $this->hasOne(PlanShift::className(), ['id' => 'plan_shift_id']);
    }
}
