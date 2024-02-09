<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_operation".
 *
 * @property int $id Ключ справочника типов операции
 * @property string $title название типа операции  выемка по углю/зачистка лавы/регламентируемый перерыв
 *
 * @property CyclegrammOperation[] $cyclegrammOperations
 * @property PassportCyclegrammLava[] $passportCyclegrammLavas
 */
class TypeOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_operation';
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
            [['title'], 'required'],
            [['title'], 'string', 'max' => 35],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    /**
     * Gets query for [[CyclegrammOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCyclegrammOperations()
    {
        return $this->hasMany(CyclegrammOperation::className(), ['type_operation_id' => 'id']);
    }

    /**
     * Gets query for [[PassportCyclegrammLavas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassportCyclegrammLavas()
    {
        return $this->hasMany(PassportCyclegrammLava::className(), ['type_operation_id' => 'id']);
    }
}
