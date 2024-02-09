<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "contraindications".
 *
 * @property int $id Ключ
 * @property string $title Название протипоказания
 *
 * @property MedReport[] $medReports
 */
class Contraindications extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contraindications';
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
            'id' => 'Ключ',
            'title' => 'Название протипоказания',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedReports()
    {
        return $this->hasMany(MedReport::className(), ['contraindications_id' => 'id']);
    }
}
