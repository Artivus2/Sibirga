<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "classifier_diseases".
 *
 * @property int $id
 * @property string $disease_code Код классификатора
 * @property string $title Наименование заболевания
 * @property int $classifier_diseases_type_id Тип профзаболевания
 *
 * @property ClassifierDiseasesType $classifierDiseasesType
 * @property MedReport[] $medReports
 */
class ClassifierDiseases extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'classifier_diseases';
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
            [['disease_code', 'title', 'classifier_diseases_type_id'], 'required'],
            [['classifier_diseases_type_id'], 'integer'],
            [['disease_code', 'title'], 'string', 'max' => 255],
            [['classifier_diseases_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ClassifierDiseasesType::className(), 'targetAttribute' => ['classifier_diseases_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'disease_code' => 'Код классификатора',
            'title' => 'Наименование заболевания',
            'classifier_diseases_type_id' => 'Тип профзаболевания',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClassifierDiseasesType()
    {
        return $this->hasOne(ClassifierDiseasesType::className(), ['id' => 'classifier_diseases_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedReports()
    {
        return $this->hasMany(MedReport::className(), ['classifier_diseases_id' => 'id']);
    }
}
