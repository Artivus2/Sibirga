<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "graphic_list".
 *
 * @property int $id Идентификатор  графика, то есть идентификатор самой таблицы (автоинкрементный)
 * @property string $title Наименование конкретного графика
 * @property int $worker_created_id Идентификатор созданного работника, то есть ИД работника, которым был создан график 
 * @property int $ status_id Уникальный идентификатор статуса графика(утвержден, согласован)
 * @property string $date_create Дата и время создания графика
 *
 * @property Status $status
 * @property Worker $workerCreated
 * @property GraphicRepair[] $graphicRepairs
 * @property GraphicStatus[] $graphicStatuses
 */
class GraphicList extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'graphic_list';
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
            [['title', 'worker_created_id', ' status_id'], 'required'],
            [['worker_created_id', ' status_id'], 'integer'],
            [['date_create'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [[' status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => [' status_id' => 'id']],
            [['worker_created_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_created_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор  графика, то есть идентификатор самой таблицы (автоинкрементный)',
            'title' => 'Наименование конкретного графика',
            'worker_created_id' => 'Идентификатор созданного работника, то есть ИД работника, которым был создан график ',
            ' status_id' => 'Уникальный идентификатор статуса графика(утвержден, согласован)',
            'date_create' => 'Дата и время создания графика',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => ' status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerCreated()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_created_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraphicRepairs()
    {
        return $this->hasMany(GraphicRepair::className(), ['graphic_list_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraphicStatuses()
    {
        return $this->hasMany(GraphicStatus::className(), ['graphic_list_id' => 'id']);
    }
}
