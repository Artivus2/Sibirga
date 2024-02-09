<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "grafic_tabel_main".
 *
 * @property int $id Ключ таблицы графика выходов
 * @property string $date_time_create время создания графика выходов
 * @property string $year год на который составляется график выходов
 * @property int $month месяц, на который составляется график выходов
 * @property string $title название графика выходов
 * @property int $company_department_id Внешний ключ справочника подразделений
 * @property int $status_id
 *
 * @property GraficChaneTable[] $graficChaneTables
 * @property GraficTabelDateFact[] $graficTabelDateFacts
 * @property GraficTabelDatePlan[] $graficTabelDatePlans
 * @property CompanyDepartment $companyDepartment
 * @property GraficTabelStatus[] $graficTabelStatuses
 */
class GraficTabelMain extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'grafic_tabel_main';
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
            [['date_time_create', 'year', 'month', 'title', 'company_department_id', 'status_id'], 'required'],
            [['date_time_create', 'year'], 'safe'],
            [['month', 'company_department_id', 'status_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['date_time_create', 'year', 'month', 'company_department_id'], 'unique', 'targetAttribute' => ['date_time_create', 'year', 'month', 'company_department_id']],
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
            'date_time_create' => 'Date Time Create',
            'year' => 'Year',
            'month' => 'Month',
            'title' => 'Title',
            'company_department_id' => 'Company Department ID',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficChaneTables()
    {
        return $this->hasMany(GraficChaneTable::className(), ['grafic_tabel_main_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDateFacts()
    {
        return $this->hasMany(GraficTabelDateFact::className(), ['grafic_tabel_main_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDatePlans()
    {
        return $this->hasMany(GraficTabelDatePlan::className(), ['grafic_tabel_main_id' => 'id']);
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
    public function getGraficTabelStatuses()
    {
        return $this->hasMany(GraficTabelStatus::className(), ['grafic_tabel_main_id' => 'id']);
    }
}
