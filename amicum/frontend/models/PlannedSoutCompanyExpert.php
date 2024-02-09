<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "planned_sout_company_expert".
 *
 * @property int $id
 * @property int $planned_sout_id Внешний идентификатор запланированного графика
 * @property int $company_expert_id Внешний идентификатор компании эксперта
 *
 * @property CompanyExpert $companyExpert
 * @property PlannedSout $plannedSout
 */
class PlannedSoutCompanyExpert extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'planned_sout_company_expert';
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
            [['planned_sout_id', 'company_expert_id'], 'required'],
            [['planned_sout_id', 'company_expert_id'], 'integer'],
            [['company_expert_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyExpert::className(), 'targetAttribute' => ['company_expert_id' => 'id']],
            [['planned_sout_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlannedSout::className(), 'targetAttribute' => ['planned_sout_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'planned_sout_id' => 'Внешний идентификатор запланированного графика',
            'company_expert_id' => 'Внешний идентификатор компании эксперта',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyExpert()
    {
        return $this->hasOne(CompanyExpert::className(), ['id' => 'company_expert_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlannedSout()
    {
        return $this->hasOne(PlannedSout::className(), ['id' => 'planned_sout_id']);
    }
}
