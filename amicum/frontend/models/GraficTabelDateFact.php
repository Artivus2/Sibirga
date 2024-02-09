<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "grafic_tabel_date_fact".
 *
 * @property int $id ключ таблицы привязки графика выходов и работников по дням
 * @property int $grafic_tabel_main_id внешний ключ таблиы график выходов
 * @property int $day День, на который составляется график выходов
 * @property int $shift_id Внешний ключ справочника смен. 
 * @property int $worker_id Внешний ключ типизированного работника
 * @property int $hours_value Фактическое количество часов для отработки
 * @property int $role_id внешний ключ справочника ролей - в роли кого должен или хочет выходить данный работник на смену (МГВМ/ГРП, ГРОЗ и т.д.)
 * @property string $date_time
 * @property int $month месяц, на который составляется график выходов
 * @property int $mine_id ключ шахты
 * @property string $year Год на который составляется график выходов для работника
 * @property int $working_time_id Внешний ключ справочника  условного обозначения рабочего времени
 * @property int $kind_working_time_id Внешний ключ видов выходов
 * @property string $description Описание
 * @property int $chane_id звено в котором находится человек
 *
 * @property Chane $chane
 * @property Worker $worker
 * @property KindWorkingTime $kindWorkingTime
 * @property Role $role
 * @property WorkingTime $workingTime
 * @property Shift $shift
 * @property GraficTabelMain $graficTabelMain
 */
class GraficTabelDateFact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'grafic_tabel_date_fact';
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
            [['grafic_tabel_main_id', 'day', 'shift_id', 'worker_id', 'role_id', 'date_time', 'month', 'year', 'working_time_id', 'kind_working_time_id'], 'required'],
            [['grafic_tabel_main_id', 'day', 'shift_id', 'worker_id', 'hours_value', 'role_id', 'month', 'working_time_id', 'kind_working_time_id', 'chane_id', 'mine_id'], 'integer'],
            [['date_time', 'year'], 'safe'],
            [['description'], 'string', 'max' => 255],
            [['grafic_tabel_main_id', 'shift_id', 'worker_id', 'role_id', 'date_time', 'working_time_id', 'kind_working_time_id'], 'unique', 'targetAttribute' => ['grafic_tabel_main_id', 'shift_id', 'worker_id', 'role_id', 'date_time', 'working_time_id', 'kind_working_time_id']],
            [['chane_id'], 'exist', 'skipOnError' => true, 'targetClass' => Chane::className(), 'targetAttribute' => ['chane_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['kind_working_time_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindWorkingTime::className(), 'targetAttribute' => ['kind_working_time_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
            [['working_time_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkingTime::className(), 'targetAttribute' => ['working_time_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
            [['grafic_tabel_main_id'], 'exist', 'skipOnError' => true, 'targetClass' => GraficTabelMain::className(), 'targetAttribute' => ['grafic_tabel_main_id' => 'id']],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
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
            'day' => 'Day',
            'shift_id' => 'Shift ID',
            'mine_id' => 'Mine ID',
            'worker_id' => 'Worker ID',
            'hours_value' => 'Hours Value',
            'role_id' => 'Role ID',
            'date_time' => 'Date Time',
            'month' => 'Month',
            'year' => 'Year',
            'working_time_id' => 'Working Time ID',
            'kind_working_time_id' => 'Kind Working Time ID',
            'description' => 'Description',
            'chane_id' => 'Chane ID',
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
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'mine_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getKindWorkingTime()
    {
        return $this->hasOne(KindWorkingTime::className(), ['id' => 'kind_working_time_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
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
    public function getShift()
    {
        return $this->hasOne(Shift::className(), ['id' => 'shift_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelMain()
    {
        return $this->hasOne(GraficTabelMain::className(), ['id' => 'grafic_tabel_main_id']);
    }
}
