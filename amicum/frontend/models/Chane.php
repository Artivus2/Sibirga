<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "chane".
 *
 * @property int $id
 * @property string $title
 * @property int $brigade_id
 * @property int $chaner_id
 * @property int $chane_type_id
 *
 * @property Brigade $brigade
 * @property Worker $chaner
 * @property ChaneType $chaneType
 * @property ChaneWorker[] $chaneWorkers
 */
class Chane extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chane';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'brigade_id', 'chaner_id', 'chane_type_id'], 'required'],
            [['brigade_id', 'chaner_id', 'chane_type_id'], 'integer'],
            [['title'], 'string', 'max' => 120],
            [['brigade_id'], 'exist', 'skipOnError' => true, 'targetClass' => Brigade::className(), 'targetAttribute' => ['brigade_id' => 'id']],
            [['chaner_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['chaner_id' => 'id']],
            [['chane_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ChaneType::className(), 'targetAttribute' => ['chane_type_id' => 'id']],
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
            'brigade_id' => 'Brigade ID',
            'chaner_id' => 'Chaner ID',
            'chane_type_id' => 'Chane Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigade()
    {
        return $this->hasOne(Brigade::className(), ['id' => 'brigade_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChaner()
    {
        return $this->hasOne(Worker::className(), ['id' => 'chaner_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChaneType()
    {
        return $this->hasOne(ChaneType::className(), ['id' => 'chane_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChaneWorkers()
    {
        return $this->hasMany(ChaneWorker::className(), ['chane_id' => 'id']);
    }

    /** Добавленная вручную запись на получение работников звена */
    public function getWorker()
    {
        return $this->hasMany(Worker::className(), ['id' => 'chane_id'])->via('chaneWorkers');
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDateFacts()
    {
        return $this->hasMany(GraficTabelDateFact::className(), ['chane_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDatePlans()
    {
        return $this->hasMany(GraficTabelDatePlan::className(), ['chane_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperationWorkers()
    {
        return $this->hasMany(OperationWorker::className(), ['chane_id' => 'id']);
    }
}
