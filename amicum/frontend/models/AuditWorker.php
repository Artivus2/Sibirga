<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "audit_worker".
 *
 * @property int $id
 * @property int $audit_id Внешний идентификатор аудита
 * @property int $worker_id Внешний идентификатор работника (аудитора)
 *
 * @property Audit $audit
 * @property Worker $worker
 */
class AuditWorker extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'audit_worker';
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
            [['audit_id', 'worker_id'], 'required'],
            [['audit_id', 'worker_id'], 'integer'],
            [['audit_id'], 'exist', 'skipOnError' => true, 'targetClass' => Audit::className(), 'targetAttribute' => ['audit_id' => 'id']],
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
            'audit_id' => 'Внешний идентификатор аудита',
            'worker_id' => 'Внешний идентификатор работника (аудитора)',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAudit()
    {
        return $this->hasOne(Audit::className(), ['id' => 'audit_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
