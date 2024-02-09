<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "passport_operation_material".
 *
 * @property int $id ключ конкретного материала операции паспорта
 * @property int $passport_operation_id ключ операции паспорта
 * @property int $material_id ключ материала
 * @property string $value количество
 *
 * @property Material $material
 * @property PassportOperation $passportOperation
 */
class PassportOperationMaterial extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'passport_operation_material';
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
            [['passport_operation_id', 'material_id', 'value'], 'required'],
            [['passport_operation_id', 'material_id'], 'integer'],
            [['value'], 'string', 'max' => 55],
            [['passport_operation_id', 'material_id'], 'unique', 'targetAttribute' => ['passport_operation_id', 'material_id']],
            [['material_id'], 'exist', 'skipOnError' => true, 'targetClass' => Material::className(), 'targetAttribute' => ['material_id' => 'id']],
            [['passport_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => PassportOperation::className(), 'targetAttribute' => ['passport_operation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'passport_operation_id' => 'Passport Operation ID',
            'material_id' => 'Material ID',
            'value' => 'Value',
        ];
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
     * Gets query for [[PassportOperation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportOperation()
    {
        return $this->hasOne(PassportOperation::className(), ['id' => 'passport_operation_id']);
    }
}
