<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "industrial_safety_object_type".
 *
 * @property int $id
 * @property string $title Наименование типа объекта ЭПБ
 *
 * @property IndustrialSafetyObject[] $industrialSafetyObjects
 */
class IndustrialSafetyObjectType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'industrial_safety_object_type';
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
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Наименование типа объекта ЭПБ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndustrialSafetyObjects()
    {
        return $this->hasMany(IndustrialSafetyObject::className(), ['industrial_safety_object_type_id' => 'id']);
    }
}
