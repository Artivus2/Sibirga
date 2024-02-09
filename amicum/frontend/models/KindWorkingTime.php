<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_working_time".
 *
 * @property int $id
 * @property string $title Название выда выходов 
 * @property string $short_title Сокращенное название вида выходов
 *
 * @property GraficTabelDateFact[] $graficTabelDateFacts
 * @property GraficTabelDatePlan[] $graficTabelDatePlans
 */
class KindWorkingTime extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_working_time';
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
            [['title', 'short_title'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['short_title'], 'string', 'max' => 2],
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Название выда выходов ',
            'short_title' => 'Сокращенное название вида выходов',
        ];
    }

    /**
     * Gets query for [[GraficTabelDateFacts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDateFacts()
    {
        return $this->hasMany(GraficTabelDateFact::className(), ['kind_working_time_id' => 'id']);
    }

    /**
     * Gets query for [[GraficTabelDatePlans]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDatePlans()
    {
        return $this->hasMany(GraficTabelDatePlan::className(), ['kind_working_time_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficChaneTables()
    {
        return $this->hasMany(GraficChaneTable::className(), ['kind_working_time_id' => 'id']);
    }
}
