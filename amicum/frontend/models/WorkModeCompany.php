<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "work_mode_company".
 *
 * @property int $id ключ связки режима работы и компании
 * @property int $status_id Статус режима работы (действует или нет1/19)
 * @property string $date_time_start дата, с которой действует режим работы
 * @property string date_time_create дата, когда создали режим работы
 * @property string|null $date_time_end дата по который действует режим работы
 * @property int $company_id ключ компании, на который распространяется режим работы
 * @property int $work_mode_id ключ режима работы
 * @property int $creater_worker_id
 *
 * @property Company $company
 * @property Worker $createrWorker
 * @property WorkMode $workMode
 */
class WorkModeCompany extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'work_mode_company';
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
            [['status_id', 'company_id', 'work_mode_id', 'creater_worker_id'], 'integer'],
            [['date_time_start','date_time_create', 'company_id', 'work_mode_id', 'creater_worker_id'], 'required'],
            [['date_time_start', 'date_time_end', 'date_time_create'], 'safe'],
            [['date_time_start', 'company_id', 'work_mode_id'], 'unique', 'targetAttribute' => ['date_time_start', 'company_id', 'work_mode_id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'id']],
            [['creater_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['creater_worker_id' => 'id']],
            [['work_mode_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkMode::className(), 'targetAttribute' => ['work_mode_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ связки режима работы и компании',
            'status_id' => 'Статус режима работы (действует или нет1/19)',
            'date_time_start' => 'дата, с которой действует режим работы',
            'date_time_end' => 'дата по который действует режим работы',
            'date_time_create' => 'дата создания режима работы',
            'company_id' => 'ключ компании, на который распространяется режим работы',
            'work_mode_id' => 'ключ режима работы',
            'creater_worker_id' => 'Creater Worker ID',
        ];
    }

    /**
     * Gets query for [[Company]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['id' => 'company_id']);
    }

    /**
     * Gets query for [[CreaterWorker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreaterWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'creater_worker_id']);
    }

    /**
     * Gets query for [[WorkMode]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkMode()
    {
        return $this->hasOne(WorkMode::className(), ['id' => 'work_mode_id']);
    }
}
