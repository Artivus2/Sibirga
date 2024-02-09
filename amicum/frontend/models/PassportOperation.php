<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "passport_operation".
 *
 * @property int $id Ключ таблицы привязки операций к паспорту
 * @property int $passport_id внешний ключ справочника паспартов
 * @property int $operation_id Внешний ключ справочника операций
 * @property int $shift_id Внешний ключ справоника смен
 * @property string|null $date_time_start дата/время относительное начала операции
 * @property string|null $date_time_end дата/время относительное окончания операции
 * @property string|null $plan_value плановое значение данной операции
 * @property int|null $passport_operation_id родительская операция (к кому привязана данная операция)
 * @property int|null $line_in_grafic номер по порядку
 *
 * @property Shift $shift
 * @property Operation $operation
 * @property Shift $shift0
 * @property Passport $passport
 * @property Operation $operation0
 * @property PassportOperationMaterial[] $passportOperationMaterials
 * @property Material[] $materials
 */
class PassportOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'passport_operation';
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
            [['passport_id', 'operation_id', 'shift_id'], 'required'],
            [['passport_id', 'operation_id', 'shift_id', 'passport_operation_id', 'line_in_grafic'], 'integer'],
            [['date_time_start', 'date_time_end'], 'safe'],
            [['plan_value'], 'string', 'max' => 255],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
            [['passport_id'], 'exist', 'skipOnError' => true, 'targetClass' => Passport::className(), 'targetAttribute' => ['passport_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'passport_id' => 'Passport ID',
            'operation_id' => 'Operation ID',
            'shift_id' => 'Shift ID',
            'date_time_start' => 'Date Time Start',
            'date_time_end' => 'Date Time End',
            'plan_value' => 'Plan Value',
            'passport_operation_id' => 'Passport Operation ID',
            'line_in_grafic' => 'Line In Grafic',
        ];
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

    /**
     * Gets query for [[Operation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * Gets query for [[Shift0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShift0()
    {
        return $this->hasOne(Shift::className(), ['id' => 'shift_id']);
    }

    /**
     * Gets query for [[Passport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassport()
    {
        return $this->hasOne(Passport::className(), ['id' => 'passport_id']);
    }

    /**
     * Gets query for [[Operation0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperation0()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * Gets query for [[PassportOperationMaterials]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportOperationMaterials()
    {
        return $this->hasMany(PassportOperationMaterial::className(), ['passport_operation_id' => 'id']);
    }

    /**
     * Gets query for [[Materials]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMaterials()
    {
        return $this->hasMany(Material::className(), ['id' => 'material_id'])->viaTable('passport_operation_material', ['passport_operation_id' => 'id']);
    }
}
