<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "company_expert".
 *
 * @property int $id
 * @property string $title Наименование компании-эксперта
 * @property string $address Адрес компании эксперта
 *
 * @property ExpertiseCompanyExpert[] $expertiseCompanyExperts
 * @property PlannedSoutCompanyExpert[] $plannedSoutCompanyExperts
 * @property Sout[] $souts
 */
class CompanyExpert extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_expert';
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
            [['title', 'address'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Наименование компании-эксперта',
            'address' => 'Адрес компании эксперта',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpertiseCompanyExperts()
    {
        return $this->hasMany(ExpertiseCompanyExpert::className(), ['company_expert_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlannedSoutCompanyExperts()
    {
        return $this->hasMany(PlannedSoutCompanyExpert::className(), ['company_expert_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSouts()
    {
        return $this->hasMany(Sout::className(), ['company_expert_id' => 'id']);
    }
}
