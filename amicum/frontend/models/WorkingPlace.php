<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "working_place".
 *
 * @property int $id
 * @property int $company_department_id Внешний идентификатор участка
 * @property int $place_id Внешний идентификатор места
 * @property int $place_type_id Внешний идентификатор типа места
 * @property int $role_id Внешний идентификатор роли
 *
 * @property PlannedSoutWorkingPlace[] $plannedSoutWorkingPlaces
 * @property CompanyDepartment $companyDepartment
 * @property Place $place
 * @property PlaceType $placeType
 * @property Role $role
 */
class WorkingPlace extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'working_place';
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
            [['company_department_id', 'place_id', 'place_type_id', 'role_id'], 'required'],
            [['company_department_id', 'place_id', 'place_type_id', 'role_id'], 'integer'],
            [['company_department_id', 'place_id', 'place_type_id', 'role_id'], 'unique', 'targetAttribute' => ['company_department_id', 'place_id', 'place_type_id', 'role_id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
            [['place_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlaceType::className(), 'targetAttribute' => ['place_type_id' => 'id']],
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
            'company_department_id' => 'Внешний идентификатор участка',
            'place_id' => 'Внешний идентификатор места',
            'place_type_id' => 'Внешний идентификатор типа места',
            'role_id' => 'Внешний идентификатор роли',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlannedSoutWorkingPlaces()
    {
        return $this->hasMany(PlannedSoutWorkingPlace::className(), ['working_place_id' => 'id']);
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
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceType()
    {
        return $this->hasOne(PlaceType::className(), ['id' => 'place_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
    }
}
