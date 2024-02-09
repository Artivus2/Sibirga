<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checkup".
 *
 * @property int $id
 * @property string $title Название дополнительного обследования
 *
 * @property MedInspection[] $medInspections
 */
class Checkup extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checkup';
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
            'title' => 'Название дополнительного обследования',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedInspections()
    {
        return $this->hasMany(MedInspection::className(), ['checkup_id' => 'id']);
    }
}
