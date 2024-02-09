<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sout".
 *
 * @property int $id
 * @property int $sout_type_id Тип СОУТ/ПК
 * @property int $place_id Внешний идентификатор места
 * @property int $place_type_id Внешний идентификатор типа места
 * @property int $company_department_id Внешний идентификатор участка
 * @property string $date Дата проведения СОУТ/ПК
 * @property string $number номер проверки
 * @property int $company_expert_id Внешний идентификатор компании эксперта
 * @property int|null $count_worker Количество сотрудников в проверке
 * @property string|null $class Класс
 * @property int $role_id Внешний идентификатор роли
 *
 * @property ContingentFromSout[] $contingentFromSouts
 * @property CompanyDepartment $companyDepartment
 * @property CompanyExpert $companyExpert
 * @property Place $place
 * @property PlaceType $placeType
 * @property Role $role
 * @property CheckingSoutType $soutType
 * @property SoutAttachment[] $soutAttachments
 * @property SoutResearch[] $soutResearches
 */
class Sout extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sout';
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
            [['sout_type_id', 'place_id', 'place_type_id', 'company_department_id', 'date', 'number', 'company_expert_id', 'role_id'], 'required'],
            [['sout_type_id', 'place_id', 'place_type_id', 'company_department_id', 'company_expert_id', 'count_worker', 'role_id'], 'integer'],
            [['date'], 'safe'],
            [['number', 'class'], 'string', 'max' => 255],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['company_expert_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyExpert::className(), 'targetAttribute' => ['company_expert_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
            [['place_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlaceType::className(), 'targetAttribute' => ['place_type_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
            [['sout_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => CheckingSoutType::className(), 'targetAttribute' => ['sout_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sout_type_id' => 'Sout Type ID',
            'place_id' => 'Place ID',
            'place_type_id' => 'Place Type ID',
            'company_department_id' => 'Company Department ID',
            'date' => 'Date',
            'number' => 'Number',
            'company_expert_id' => 'Company Expert ID',
            'count_worker' => 'Count Worker',
            'class' => 'Class',
            'role_id' => 'Role ID',
        ];
    }

    /**
     * Gets query for [[ContingentFromSouts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContingentFromSouts()
    {
        return $this->hasMany(ContingentFromSout::className(), ['sout_id' => 'id']);
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
     * Gets query for [[CompanyExpert]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyExpert()
    {
        return $this->hasOne(CompanyExpert::className(), ['id' => 'company_expert_id']);
    }

    /**
     * Gets query for [[Place]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * Gets query for [[PlaceType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceType()
    {
        return $this->hasOne(PlaceType::className(), ['id' => 'place_type_id']);
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
     * Gets query for [[SoutType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSoutType()
    {
        return $this->hasOne(CheckingSoutType::className(), ['id' => 'sout_type_id']);
    }

    /**
     * Gets query for [[SoutAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSoutAttachments()
    {
        return $this->hasMany(SoutAttachment::className(), ['sout_id' => 'id']);
    }

    /**
     * Gets query for [[SoutResearches]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSoutResearches()
    {
        return $this->hasMany(SoutResearch::className(), ['sout_id' => 'id']);
    }
}
