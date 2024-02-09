<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "diseases".
 *
 * @property int $id Ключ проф.заболевания
 * @property string $title Название профзаболевания
 *
 * @property MedReport[] $medReports
 */
class Diseases extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'diseases';
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
            'id' => 'Ключ проф.заболевания',
            'title' => 'Название профзаболевания',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedReports()
    {
        return $this->hasMany(MedReport::className(), ['disease_id' => 'id']);
    }
}
