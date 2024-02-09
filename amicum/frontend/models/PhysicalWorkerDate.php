<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "physical_worker_date".
 *
 * @property int $id идентификатор таблицы отметки о прохождении ПМО
 * @property int $physical_worker_id внешний идентификатор работника на которого назначено пройти медицинский осмотр
 * @property string $date дата прохождения медицинского осмотра работником
 * @property int $status_id внешний идентификатор статуса 
 *
 * @property MedReport[] $medReports
 * @property PhysicalWorker $physicalWorker
 * @property Status $status
 */
class PhysicalWorkerDate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'physical_worker_date';
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
            [['physical_worker_id', 'status_id'], 'integer'],
            [['date'], 'safe'],
            [['physical_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => PhysicalWorker::className(), 'targetAttribute' => ['physical_worker_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'идентификатор таблицы отметки о прохождении ПМО',
            'physical_worker_id' => 'внешний идентификатор работника на которого назначено пройти медицинский осмотр',
            'date' => 'дата прохождения медицинского осмотра работником',
            'status_id' => 'внешний идентификатор статуса ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMedReports()
    {
        return $this->hasMany(MedReport::className(), ['physical_worker_date_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalWorker()
    {
        return $this->hasOne(PhysicalWorker::className(), ['id' => 'physical_worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
