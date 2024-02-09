<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "graphic_status".
 *
 * @property int $id Идентификатор статуса графика, то есть идентификатор самой таблицы (автоинкрементный)
 * @property int $graphic_list_id Уникальный идентификатор графика из списка графиков
 * @property int $worker_id Уникальный идентификатор работника (кем был создан)
 * @property int $status_id Уникальный идентификатор статуса
 * @property string $date_time Дата и время изменения статус графика
 *
 * @property GraphicList $graphicList
 * @property Status $status
 * @property Worker $worker
 */
class GraphicStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'graphic_status';
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
            [['graphic_list_id', 'worker_id', 'status_id', 'date_time'], 'required'],
            [['graphic_list_id', 'worker_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['graphic_list_id'], 'exist', 'skipOnError' => true, 'targetClass' => GraphicList::className(), 'targetAttribute' => ['graphic_list_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор статуса графика, то есть идентификатор самой таблицы (автоинкрементный)',
            'graphic_list_id' => 'Уникальный идентификатор графика из списка графиков',
            'worker_id' => 'Уникальный идентификатор работника (кем был создан)',
            'status_id' => 'Уникальный идентификатор статуса',
            'date_time' => 'Дата и время изменения статус графика',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraphicList()
    {
        return $this->hasOne(GraphicList::className(), ['id' => 'graphic_list_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
