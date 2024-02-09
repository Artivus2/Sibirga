<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "shift_mine".
 *
 * @property int $id
 * @property string $date_time
 * @property int $plan_shift_id
 * @property int $company_id
 *
 * @property Company $company
 * @property PlanShift $planShift
 */
class ShiftMine extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shift_mine';
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
            [['date_time', 'plan_shift_id', 'company_id'], 'required'],
            [['date_time'], 'safe'],
            [['plan_shift_id', 'company_id'], 'integer'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'id']],
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
            'company_id' => 'Company ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlanShift()
    {
        return $this->hasOne(PlanShift::className(), ['id' => 'plan_shift_id']);
    }
}
