<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checking_plan".
 *
 * @property int $id
 * @property int $checking_type_id Тип проверки (аудита)
 * @property string $date Дата на которую будет проведена проверка
 * @property int $place_id Внешний идентификатор места проверки
 * @property int $worker_id Внешний идентификатор работника (аудитора)
 * @property string $description Примечание (необязательное поле)
 *
 * @property CheckingType $checkingType
 * @property Place $place
 * @property Worker $worker
 */
class CheckingPlan extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checking_plan';
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
            [['checking_type_id', 'date', 'place_id', 'worker_id'], 'required'],
            [['checking_type_id', 'place_id', 'worker_id'], 'integer'],
            [['date'], 'safe'],
            [['description'], 'string', 'max' => 255],
            [['checking_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => CheckingType::className(), 'targetAttribute' => ['checking_type_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'checking_type_id' => 'Тип проверки (аудита)',
            'date' => 'Дата на которую будет проведена проверка',
            'place_id' => 'Внешний идентификатор места проверки',
            'worker_id' => 'Внешний идентификатор работника (аудитора)',
            'description' => 'Примечание (необязательное поле)',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingType()
    {
        return $this->hasOne(CheckingType::className(), ['id' => 'checking_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
