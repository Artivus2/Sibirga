<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "grafic_chane_table".
 *
 * @property int $id ключ таблицы привязки графика выходов и работников по дням
 * @property int $grafic_tabel_main_id внешний ключ таблиы график выходов
 * @property int $shift_id Внешний ключ справочника смен. 
 * @property int $chane_id Внешний ключ звена
 * @property string $date_time
 * @property int $working_time_id Внешний ключ справочника  условного обозначения рабочего времени
 *
 * @property Chane $chane
 * @property WorkingTime $workingTime
 * @property GraficTabelMain $graficTabelMain
 * @property Shift $shift
 */
class GraficChaneTable extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'grafic_chane_table';
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
            [['grafic_tabel_main_id', 'shift_id', 'chane_id', 'date_time', 'working_time_id'], 'required'],
            [['grafic_tabel_main_id', 'shift_id', 'chane_id', 'working_time_id'], 'integer'],
            [['date_time'], 'safe'],
            [['grafic_tabel_main_id', 'chane_id', 'date_time', 'working_time_id'], 'unique', 'targetAttribute' => ['grafic_tabel_main_id', 'chane_id', 'date_time', 'working_time_id']],
            [['chane_id'], 'exist', 'skipOnError' => true, 'targetClass' => Chane::className(), 'targetAttribute' => ['chane_id' => 'id']],
            [['working_time_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkingTime::className(), 'targetAttribute' => ['working_time_id' => 'id']],
            [['grafic_tabel_main_id'], 'exist', 'skipOnError' => true, 'targetClass' => GraficTabelMain::className(), 'targetAttribute' => ['grafic_tabel_main_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'grafic_tabel_main_id' => 'Grafic Tabel Main ID',
            'shift_id' => 'Shift ID',
            'chane_id' => 'Chane ID',
            'date_time' => 'Date Time',
            'working_time_id' => 'Working Time ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChane()
    {
        return $this->hasOne(Chane::className(), ['id' => 'chane_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkingTime()
    {
        return $this->hasOne(WorkingTime::className(), ['id' => 'working_time_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelMain()
    {
        return $this->hasOne(GraficTabelMain::className(), ['id' => 'grafic_tabel_main_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShift()
    {
        return $this->hasOne(Shift::className(), ['id' => 'shift_id']);
    }
}
