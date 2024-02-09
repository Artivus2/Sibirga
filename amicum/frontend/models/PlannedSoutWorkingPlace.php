<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "planned_sout_working_place".
 *
 * @property int $id
 * @property int $planned_sout_id Внешний идентификатор плнового СОУТ/ПК
 * @property int $working_place_id Внешний идентификатор рабочего места
 * @property int $count_worker Количество рабочих мест
 *
 * @property PlannedSout $plannedSout
 * @property WorkingPlace $workingPlace
 */
class PlannedSoutWorkingPlace extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'planned_sout_working_place';
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
            [['planned_sout_id', 'working_place_id', 'count_worker'], 'required'],
            [['planned_sout_id', 'working_place_id', 'count_worker'], 'integer'],
            [['planned_sout_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlannedSout::className(), 'targetAttribute' => ['planned_sout_id' => 'id']],
            [['working_place_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkingPlace::className(), 'targetAttribute' => ['working_place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'planned_sout_id' => 'Внешний идентификатор плнового СОУТ/ПК',
            'working_place_id' => 'Внешний идентификатор рабочего места',
            'count_worker' => 'Количество рабочих мест',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlannedSout()
    {
        return $this->hasOne(PlannedSout::className(), ['id' => 'planned_sout_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkingPlace()
    {
        return $this->hasOne(WorkingPlace::className(), ['id' => 'working_place_id']);
    }
}
