<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "grafic_tabel_date_plan".
 *
 * @property int $id ключ таблицы привязки графика выходов и работников по дням
 * @property int $grafic_tabel_main_id внешний ключ таблиы график выходов
 * @property int $day День, на который составляется график выходов
 * @property int $shift_id Внешний ключ справочника смен.
 * @property int $mine_id Внешний ключ справочника шахт.
 * @property int $worker_id Внешний ключ типизированного работника
 * @property int|null $hours_value Планновое количество часов для отработки. По умолчанию ставится В - выходной.
 * @property int $role_id внешний ключ справочника ролей - в роли кого должен или хочет выходить данный работник на смену (МГВМ/ГРП, ГРОЗ и т.д.)
 * @property string $date_time
 * @property int $month месяц, на который составляется график выходов
 * @property string $year Год на который составляется график выходов для работника 
 * @property int $kind_working_time_id Внешний ключ видов выходов
 * @property int $working_time_id Внешний ключ справочника  условного обозначения рабочего времени
 * @property string|null $description Описание
 * @property int|null $chane_id звено в котором находится человек
 *
 * @property Chane $chane
 * @property KindWorkingTime $kindWorkingTime
 * @property WorkingTime $workingTime
 * @property Shift $shift
 * @property GraficTabelMain $graficTabelMain
 * @property Role $role
 * @property Worker $worker
 */
class GraficTabelDatePlan extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'grafic_tabel_date_plan';
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
            [['grafic_tabel_main_id', 'day', 'shift_id', 'worker_id', 'role_id', 'date_time', 'month', 'year', 'kind_working_time_id', 'working_time_id'], 'required'],
            [['grafic_tabel_main_id', 'day', 'shift_id', 'worker_id', 'hours_value', 'role_id', 'month', 'kind_working_time_id', 'working_time_id', 'chane_id', 'mine_id'], 'integer'],
            [['date_time', 'year'], 'safe'],
            [['description'], 'string', 'max' => 255],
            [['grafic_tabel_main_id', 'shift_id', 'worker_id', 'role_id', 'date_time', 'kind_working_time_id', 'working_time_id'], 'unique', 'targetAttribute' => ['grafic_tabel_main_id', 'shift_id', 'worker_id', 'role_id', 'date_time', 'kind_working_time_id', 'working_time_id']],
            [['chane_id'], 'exist', 'skipOnError' => true, 'targetClass' => Chane::className(), 'targetAttribute' => ['chane_id' => 'id']],
            [['kind_working_time_id'], 'exist', 'skipOnError' => true, 'targetClass' => KindWorkingTime::className(), 'targetAttribute' => ['kind_working_time_id' => 'id']],
            [['working_time_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkingTime::className(), 'targetAttribute' => ['working_time_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
            [['grafic_tabel_main_id'], 'exist', 'skipOnError' => true, 'targetClass' => GraficTabelMain::className(), 'targetAttribute' => ['grafic_tabel_main_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ таблицы привязки графика выходов и работников по дням',
            'grafic_tabel_main_id' => 'внешний ключ таблиы график выходов',
            'day' => 'День, на который составляется график выходов',
            'shift_id' => 'Внешний ключ справочника смен.',
            'mine_id' => 'Внешний ключ справочника шахт.',
            'worker_id' => 'Внешний ключ типизированного работника',
            'hours_value' => 'Планновое количество часов для отработки. По умолчанию ставится В - выходной.',
            'role_id' => 'внешний ключ справочника ролей - в роли кого должен или хочет выходить данный работник на смену (МГВМ/ГРП, ГРОЗ и т.д.)',
            'date_time' => 'Date Time',
            'month' => 'месяц, на который составляется график выходов',
            'year' => 'Год на который составляется график выходов для работника ',
            'kind_working_time_id' => 'Внешний ключ видов выходов',
            'working_time_id' => 'Внешний ключ справочника  условного обозначения рабочего времени',
            'description' => 'Описание',
            'chane_id' => 'звено в котором находится человек',
        ];
    }

    /**
     * Gets query for [[Chane]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChane()
    {
        return $this->hasOne(Chane::className(), ['id' => 'chane_id']);
    }

    /**
     * Gets query for [[KindWorkingTime]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getKindWorkingTime()
    {
        return $this->hasOne(KindWorkingTime::className(), ['id' => 'kind_working_time_id']);
    }

    /**
     * Gets query for [[WorkingTime]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkingTime()
    {
        return $this->hasOne(WorkingTime::className(), ['id' => 'working_time_id']);
    }

    /**
     * Gets query for [[Shift]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShift()
    {
        return $this->hasOne(Shift::className(), ['id' => 'shift_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'mine_id']);
    }

    /**
     * Gets query for [[GraficTabelMain]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelMain()
    {
        return $this->hasOne(GraficTabelMain::className(), ['id' => 'grafic_tabel_main_id']);
    }

    /**
     * Gets query for [[Role]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
    }

    /**
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
