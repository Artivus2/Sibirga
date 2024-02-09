<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_siz_status".
 *
 * @property int $id Идентификатор таблицы(автоинкрементный)
 * @property int $worker_siz_id Внешний ключ к таблице средств индивидуальной защиты работников
 * @property string $date Дата и время изменения статуса
 * @property string $comment Признак(комментарий) к изменению статуса
 * @property int $percentage_wear Процент износа средства индивидуальной защиты
 * @property int $status_id Внешний ключ к справочнику статусов
 *
 * @property Status $status
 * @property WorkerSiz $workerSiz
 */
class WorkerSizStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_siz_status';
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
            [['worker_siz_id', 'date', 'status_id'], 'required'],
            [['worker_siz_id', 'percentage_wear', 'status_id'], 'integer'],
            [['date'], 'safe'],
            [['comment'], 'string', 'max' => 255],
            [['worker_siz_id', 'date', 'status_id'], 'unique', 'targetAttribute' => ['worker_siz_id', 'date', 'status_id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['worker_siz_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkerSiz::className(), 'targetAttribute' => ['worker_siz_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы(автоинкрементный)',
            'worker_siz_id' => 'Внешний ключ к таблице средств индивидуальной защиты работников',
            'date' => 'Дата и время изменения статуса',
            'comment' => 'Признак(комментарий) к изменению статуса',
            'percentage_wear' => 'Процент износа средства индивидуальной защиты',
            'status_id' => 'Внешний ключ к справочнику статусов',
        ];
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
    public function getWorkerSiz()
    {
        return $this->hasOne(WorkerSiz::className(), ['id' => 'worker_siz_id']);
    }
}
