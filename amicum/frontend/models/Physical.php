<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "physical".
 *
 * @property int $id
 * @property int $year Год, за который строится график мо
 * @property string $title Название графика
 * @property int $worker_id Сотрудник, который составляет график
 *
 * @property DocumentPhysical[] $documentPhysicals
 * @property Worker $worker
 * @property PhysicalAttachment[] $physicalAttachments
 * @property PhysicalHistory[] $physicalHistories
 * @property PhysicalSchedule[] $physicalSchedules
 */
class Physical extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'physical';
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
            [['year', 'title', 'worker_id'], 'required'],
            [['year', 'worker_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
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
            'year' => 'Год, за который строится график мо',
            'title' => 'Название графика',
            'worker_id' => 'Сотрудник, который составляет график',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentPhysicals()
    {
        return $this->hasMany(DocumentPhysical::className(), ['physical_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalAttachments()
    {
        return $this->hasMany(PhysicalAttachment::className(), ['physical_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalHistories()
    {
        return $this->hasMany(PhysicalHistory::className(), ['physical_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhysicalSchedules()
    {
        return $this->hasMany(PhysicalSchedule::className(), ['physical_id' => 'id']);
    }
}
