<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "material".
 *
 * @property int $id Идентификатор самой таблицы (автоинкрементный)\\\\n
 * @property int $nomenclature_id Наименование материала 
 * @property int $unit_id Уникальный идентификатор единицы измерения 
 *
 * @property Unit $unit
 * @property Nomenclature $nomenclature
 * @property PassportOperationMaterial[] $passportOperationMaterials
 * @property PassportOperation[] $passportOperations
 * @property RepairMapSpecificMaterial[] $repairMapSpecificMaterials
 * @property RepairMapTypicalMaterial[] $repairMapTypicalMaterials
 * @property Storage[] $storages
 */
class Material extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'material';
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
            [['nomenclature_id', 'unit_id'], 'required'],
            [['nomenclature_id', 'unit_id'], 'integer'],
            [['unit_id'], 'exist', 'skipOnError' => true, 'targetClass' => Unit::className(), 'targetAttribute' => ['unit_id' => 'id']],
            [['nomenclature_id'], 'exist', 'skipOnError' => true, 'targetClass' => Nomenclature::className(), 'targetAttribute' => ['nomenclature_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nomenclature_id' => 'Nomenclature ID',
            'unit_id' => 'Unit ID',
        ];
    }

    /**
     * Gets query for [[Unit]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUnit()
    {
        return $this->hasOne(Unit::className(), ['id' => 'unit_id']);
    }

    /**
     * Gets query for [[Nomenclature]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNomenclature()
    {
        return $this->hasOne(Nomenclature::className(), ['id' => 'nomenclature_id']);
    }

    /**
     * Gets query for [[PassportOperationMaterials]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportOperationMaterials()
    {
        return $this->hasMany(PassportOperationMaterial::className(), ['material_id' => 'id']);
    }

    /**
     * Gets query for [[PassportOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportOperations()
    {
        return $this->hasMany(PassportOperation::className(), ['id' => 'passport_operation_id'])->viaTable('passport_operation_material', ['material_id' => 'id']);
    }

    /**
     * Gets query for [[RepairMapSpecificMaterials]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecificMaterials()
    {
        return $this->hasMany(RepairMapSpecificMaterial::className(), ['material_id' => 'id']);
    }

    /**
     * Gets query for [[RepairMapTypicalMaterials]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicalMaterials()
    {
        return $this->hasMany(RepairMapTypicalMaterial::className(), ['material_id' => 'id']);
    }

    /**
     * Gets query for [[Storages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStorages()
    {
        return $this->hasMany(Storage::className(), ['material_id' => 'id']);
    }
}
