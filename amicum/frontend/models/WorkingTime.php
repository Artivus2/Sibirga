<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "working_time".
 *
 * @property int $id Идентификатор
 * @property string $title Наименование 
 * @property string $code Кодовое обозначение, например 01, 02
 * @property string $short_title короткое обозначение, например отпуск - о
 *
 * @property GraficChaneTable[] $graficChaneTables
 * @property GraficTabelDateFact[] $graficTabelDateFacts
 * @property GraficTabelDatePlan[] $graficTabelDatePlans
 */
class WorkingTime extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'working_time';
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
            [['title', 'code', 'short_title'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 10],
            [['short_title'], 'string', 'max' => 20],
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
            'title' => 'Title',
            'code' => 'Code',
            'short_title' => 'Short Title',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficChaneTables()
    {
        return $this->hasMany(GraficChaneTable::className(), ['working_time_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDateFacts()
    {
        return $this->hasMany(GraficTabelDateFact::className(), ['working_time_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDatePlans()
    {
        return $this->hasMany(GraficTabelDatePlan::className(), ['working_time_id' => 'id']);
    }
}
