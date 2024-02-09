<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "storage".
 *
 * @property int $id ключ на складе
 * @property int $material_id ключ материала
 * @property float $nomenclature_value количество материалов
 * @property int $kind_direction_store_id ключ направления списания материалов
 * @property int $company_department_id ключ подразделения
 * @property float|null $cost_nomenclature стоимость номенклатуры
 * @property string $date_time дата и время внесения данных
 * @property int $worker_id ключ работника совершившего внесение/списание материалов
 * @property int $place_id ключ места на кторое списывается материал
 * @property int $shift_id ключ смены рамках которой происходит списание приемка
 * @property string|null $description причина списания комментарий
 * @property string|null $date_work производственная дата
 *
 * @property CompanyDepartment $companyDepartment
 * @property KindDirectionStore $kindDirectionStore
 * @property Material $material
 * @property Place $place
 * @property Shift $shift
 */
class Storage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'storage';
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
            [['material_id', 'nomenclature_value', 'kind_direction_store_id', 'company_department_id', 'date_time', 'worker_id', 'place_id', 'shift_id'], 'required'],
            [['material_id', 'kind_direction_store_id', 'company_department_id', 'worker_id', 'place_id', 'shift_id'], 'integer'],
            [['nomenclature_value', 'cost_nomenclature'], 'number'],
            [['date_time', 'date_work'], 'safe'],
            [['description'], 'string', 'max' => 500],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['kind_direction_store_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindDirectionStore::className(), 'targetAttribute' => ['kind_direction_store_id' => 'id']],
            [['material_id'], 'exist', 'skipOnError' => true, 'targetClass' => Material::className(), 'targetAttribute' => ['material_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'material_id' => 'Material ID',
            'nomenclature_value' => 'Nomenclature Value',
            'kind_direction_store_id' => 'Kind Direction Store ID',
            'company_department_id' => 'Company Department ID',
            'cost_nomenclature' => 'Cost Nomenclature',
            'date_time' => 'Date Time',
            'worker_id' => 'Worker ID',
            'place_id' => 'Place ID',
            'shift_id' => 'Shift ID',
            'description' => 'Description',
            'date_work' => 'Date Work',
        ];
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
     * Gets query for [[KindDirectionStore]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getKindDirectionStore()
    {
        return $this->hasOne(KindDirectionStore::className(), ['id' => 'kind_direction_store_id']);
    }

    /**
     * Gets query for [[Material]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMaterial()
    {
        return $this->hasOne(Material::className(), ['id' => 'material_id']);
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
     * Gets query for [[Shift]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShift()
    {
        return $this->hasOne(Shift::className(), ['id' => 'shift_id']);
    }
}
