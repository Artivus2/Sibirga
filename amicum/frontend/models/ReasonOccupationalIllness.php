<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "reason_occupational_illness".
 *
 * @property int $id
 * @property string $title Название причины профзаболевания
 *
 * @property OccupationalIllness[] $occupationalIllnesses
 */
class ReasonOccupationalIllness extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'reason_occupational_illness';
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
            'title' => 'Название причины профзаболевания',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOccupationalIllnesses()
    {
        return $this->hasMany(OccupationalIllness::className(), ['reason_occupational_illness_id' => 'id']);
    }
}
