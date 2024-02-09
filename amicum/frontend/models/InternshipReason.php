<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "internship_reason".
 *
 * @property int $id ключ стажировки
 * @property string $title Наименование справочника основания проведения стажировки
 *
 * @property Briefing[] $briefings
 */
class InternshipReason extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'internship_reason';
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
            'id' => 'ключ стажировки',
            'title' => 'Наименование справочника основания проведения стажировки',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBriefings()
    {
        return $this->hasMany(Briefing::className(), ['internship_reason_id' => 'id']);
    }
}
