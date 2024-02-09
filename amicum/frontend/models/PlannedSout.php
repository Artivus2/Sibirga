<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "planned_sout".
 *
 * @property int $id
 * @property string $date_start Дата начала периода проведения СОУТ/ПК
 * @property string $date_end Дата окончанияпериода проведения СОУТ/ПК
 * @property int $day_start День начала в графике
 * @property int $day_end День окончания в графике
 * @property int $sout_type_id Тип проверки СОУТа/ПК
 * @property int $company_department_id Внешний идентификатор участка где будет проводиться СОУТ/ПК
 * @property int $planned_sout_kind_id Вид планового СОУТ/ПК
 * @property int $selected_workers Выбрано рабочих мест
 * @property int $workers_place_count Всего рабочих мест
 *
 * @property CheckingSoutType $soutType
 * @property CompanyDepartment $companyDepartment
 * @property PlannedSoutKind $plannedSoutKind
 * @property PlannedSoutCompanyExpert[] $plannedSoutCompanyExperts
 * @property PlannedSoutWorkingPlace[] $plannedSoutWorkingPlaces
 */
class PlannedSout extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'planned_sout';
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
            [['date_start', 'date_end', 'day_start', 'day_end', 'sout_type_id', 'company_department_id', 'planned_sout_kind_id'], 'required'],
            [['date_start', 'date_end'], 'safe'],
            [['day_start', 'day_end', 'sout_type_id', 'company_department_id', 'planned_sout_kind_id', 'selected_workers', 'workers_place_count'], 'integer'],
            [['sout_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => CheckingSoutType::className(), 'targetAttribute' => ['sout_type_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['planned_sout_kind_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlannedSoutKind::className(), 'targetAttribute' => ['planned_sout_kind_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_start' => 'Дата начала периода проведения СОУТ/ПК',
            'date_end' => 'Дата окончанияпериода проведения СОУТ/ПК',
            'day_start' => 'День начала в графике',
            'day_end' => 'День окончания в графике',
            'sout_type_id' => 'Тип проверки СОУТа/ПК',
            'company_department_id' => 'Внешний идентификатор участка где будет проводиться СОУТ/ПК',
            'planned_sout_kind_id' => 'Вид планового СОУТ/ПК',
            'selected_workers' => 'Выбрано рабочих мест',
            'workers_place_count' => 'Всего рабочих мест',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSoutType()
    {
        return $this->hasOne(CheckingSoutType::className(), ['id' => 'sout_type_id']);
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
    public function getPlannedSoutKind()
    {
        return $this->hasOne(PlannedSoutKind::className(), ['id' => 'planned_sout_kind_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlannedSoutCompanyExperts()
    {
        return $this->hasMany(PlannedSoutCompanyExpert::className(), ['planned_sout_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlannedSoutWorkingPlaces()
    {
        return $this->hasMany(PlannedSoutWorkingPlace::className(), ['planned_sout_id' => 'id']);
    }
}
