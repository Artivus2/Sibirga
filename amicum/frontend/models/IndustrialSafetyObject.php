<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "industrial_safety_object".
 *
 * @property int $id
 * @property string $title Наименование объекта экспертизы промышленной безопасности
 * @property int $industrial_safety_object_type_id Внешний идентификатор типа объекта ЭПБ
 *
 * @property Expertise[] $expertises
 * @property IndustrialSafetyObjectType $industrialSafetyObjectType
 */
class IndustrialSafetyObject extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'industrial_safety_object';
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
            [['title', 'industrial_safety_object_type_id'], 'required'],
            [['industrial_safety_object_type_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['industrial_safety_object_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => IndustrialSafetyObjectType::className(), 'targetAttribute' => ['industrial_safety_object_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Наименование объекта экспертизы промышленной безопасности',
            'industrial_safety_object_type_id' => 'Внешний идентификатор типа объекта ЭПБ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpertises()
    {
        return $this->hasMany(Expertise::className(), ['industrial_safety_object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIndustrialSafetyObjectType()
    {
        return $this->hasOne(IndustrialSafetyObjectType::className(), ['id' => 'industrial_safety_object_type_id']);
    }
}
