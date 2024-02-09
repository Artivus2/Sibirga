<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_type".
 *
 * @property int $id Идентификатор таблицы
 * @property string $title Название типа работника (инспектор, присутствовал, ответственный)
 *
 * @property CheckingWorkerType[] $checkingWorkerTypes
 */
class WorkerType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_type';
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
            [['id', 'title'], 'required'],
            [['id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['id'], 'unique'],
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
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingWorkerTypes()
    {
        return $this->hasMany(CheckingWorkerType::className(), ['worker_type_id' => 'id']);
    }
}
