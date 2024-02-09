<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "work_mode".
 *
 * @property int $id ключ спраочника режимов работы
 * @property string $title Название режима работы
 * @property int $type_work_mode_id ключ типа режима работы (праздничный/предпраздничный/рабочий)
 * @property float $count_hours Количество часов в режиме работы
 * @property float $count_norm_hours Количество нормированных часов в режиме работы
 *
 * @property TypeWorkMode $typeWorkMode
 * @property WorkModeCompany[] $workModeCompanies
 * @property WorkModeShift[] $workModeShifts
 * @property WorkModeWorker[] $workModeWorkers
 */
class WorkMode extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'work_mode';
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
            [['title', 'type_work_mode_id', 'count_hours', 'count_norm_hours'], 'required'],
            [['type_work_mode_id'], 'integer'],
            [['count_hours', 'count_norm_hours'], 'number'],
            [['title'], 'string', 'max' => 255],
            [['type_work_mode_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypeWorkMode::className(), 'targetAttribute' => ['type_work_mode_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ спраочника режимов работы',
            'title' => 'Название режима работы',
            'type_work_mode_id' => 'ключ типа режима работы (праздничный/предпраздничный/рабочий)',
            'count_hours' => 'Количество часов в режиме работы',
            'count_norm_hours' => 'Количество нормированных часов в режиме работы',
        ];
    }

    /**
     * Gets query for [[TypeWorkMode]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTypeWorkMode()
    {
        return $this->hasOne(TypeWorkMode::className(), ['id' => 'type_work_mode_id']);
    }

    /**
     * Gets query for [[WorkModeCompanies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkModeCompanies()
    {
        return $this->hasMany(WorkModeCompany::className(), ['work_mode_id' => 'id']);
    }

    /**
     * Gets query for [[WorkModeShifts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkModeShifts()
    {
        return $this->hasMany(WorkModeShift::className(), ['work_mode_id' => 'id']);
    }

    /**
     * Gets query for [[WorkModeWorkers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkModeWorkers()
    {
        return $this->hasMany(WorkModeWorker::className(), ['work_mode_id' => 'id']);
    }
}
