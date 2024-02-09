<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "classifier_diseases_kind".
 *
 * @property int $id
 * @property string $title Наименование вида классификатора заболевания
 *
 * @property ClassifierDiseasesType[] $classifierDiseasesTypes
 */
class ClassifierDiseasesKind extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'classifier_diseases_kind';
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
            'title' => 'Наименование вида классификатора заболевания',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClassifierDiseasesTypes()
    {
        return $this->hasMany(ClassifierDiseasesType::className(), ['classifier_diseases_kind_id' => 'id']);
    }
}
