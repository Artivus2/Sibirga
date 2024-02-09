<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "classifier_diseases_type".
 *
 * @property int $id
 * @property string $title Наименование типа классификатора заболевания
 * @property int $classifier_diseases_kind_id внешний идентификатор вида классификатора заболевания
 *
 * @property ClassifierDiseasesKind $classifierDiseasesKind
 */
class ClassifierDiseasesType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'classifier_diseases_type';
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
            [['title', 'classifier_diseases_kind_id'], 'required'],
            [['classifier_diseases_kind_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['classifier_diseases_kind_id'], 'exist', 'skipOnError' => true, 'targetClass' => ClassifierDiseasesKind::className(), 'targetAttribute' => ['classifier_diseases_kind_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Наименование типа классификатора заболевания',
            'classifier_diseases_kind_id' => 'внешний идентификатор вида классификатора заболевания',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClassifierDiseasesKind()
    {
        return $this->hasOne(ClassifierDiseasesKind::className(), ['id' => 'classifier_diseases_kind_id']);
    }
}
