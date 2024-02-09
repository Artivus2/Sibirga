<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "fire_fighting_object".
 *
 * @property int $id Идентификатор средствапожарной безопасности (автоинкрементный)
 * @property int $fire_fighting_equipment_id Внешний идентификатор типа средства пожарной безопасности
 * @property int $count_issued_plan количествао выданных по плану
 * @property int $count_issued_fact количествао выданных по факту
 * @property int $company_department_id участок на котором ввели в эксплуатацию
 * @property int $place_id место на котором ввели к эксплутацию
 *
 * @property FireFightingEquipmentSpecific[] $fireFightingEquipmentSpecifics
 * @property FireFightingEquipment $fireFightingEquipment
 * @property CompanyDepartment $companyDepartment
 * @property Place $place
 */
class FireFightingObject extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fire_fighting_object';
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
            [['fire_fighting_equipment_id', 'count_issued_plan', 'company_department_id', 'place_id'], 'required'],
            [['fire_fighting_equipment_id', 'count_issued_plan', 'count_issued_fact', 'company_department_id', 'place_id'], 'integer'],
            [['company_department_id', 'place_id', 'fire_fighting_equipment_id'], 'unique', 'targetAttribute' => ['company_department_id', 'place_id', 'fire_fighting_equipment_id']],
            [['fire_fighting_equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => FireFightingEquipment::className(), 'targetAttribute' => ['fire_fighting_equipment_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор средствапожарной безопасности (автоинкрементный)',
            'fire_fighting_equipment_id' => 'Внешний идентификатор типа средства пожарной безопасности',
            'count_issued_plan' => 'количествао выданных по плану',
            'count_issued_fact' => 'количествао выданных по факту',
            'company_department_id' => 'участок на котором ввели в эксплуатацию',
            'place_id' => 'место на котором ввели к эксплутацию',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFireFightingEquipmentSpecifics()
    {
        return $this->hasMany(FireFightingEquipmentSpecific::className(), ['fire_fighting_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFireFightingEquipment()
    {
        return $this->hasOne(FireFightingEquipment::className(), ['id' => 'fire_fighting_equipment_id']);
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
}
