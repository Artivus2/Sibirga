<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "passport_cyclegramm_lava".
 *
 * @property int $id ключ циклограммы паспорта
 * @property int $passport_id Внешний ключ справочника паспорта
 * @property int $type_operation_id
 * @property string $time
 * @property int $section_number
 *
 * @property TypeOperation $typeOperation
 * @property Passport $passport
 */
class PassportCyclegrammLava extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'passport_cyclegramm_lava';
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
            [['id', 'passport_id', 'type_operation_id', 'time', 'section_number'], 'required'],
            [['id', 'passport_id', 'type_operation_id', 'section_number'], 'integer'],
            [['time'], 'safe'],
            [['id'], 'unique'],
            [['passport_id', 'type_operation_id', 'time'], 'unique', 'targetAttribute' => ['passport_id', 'type_operation_id', 'time']],
            [['type_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypeOperation::className(), 'targetAttribute' => ['type_operation_id' => 'id']],
            [['passport_id'], 'exist', 'skipOnError' => true, 'targetClass' => Passport::className(), 'targetAttribute' => ['passport_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ циклограммы паспорта',
            'passport_id' => 'Внешний ключ справочника паспорта',
            'type_operation_id' => 'Type Operation ID',
            'time' => 'Time',
            'section_number' => 'Section Number',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeOperation()
    {
        return $this->hasOne(TypeOperation::className(), ['id' => 'type_operation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPassport()
    {
        return $this->hasOne(Passport::className(), ['id' => 'passport_id']);
    }
}
