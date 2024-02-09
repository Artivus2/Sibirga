<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "grafic_tabel_date".
 *
 * @property int $id ключ таблицы привязки графика выходов и работников по дням
 * @property int $grafic_tabel_main_id внешний ключ таблиы график выходов
 * @property string $date_time дата выхода на работу работника
 * @property int $shift_id Внешний ключ справочника смен
 * @property int $hourse_plan_value планновое количество часов для отработки
 * @property int $worker_object_id Внешний ключ типизированного работника
 * @property int $role_id внешний ключ справочника ролей - в роли кого должен или хочет выходить данный работник на смену (МГВМ/ГРП, ГРОЗ и т.д.)
 *
 * @property GraficTabelMain $graficTabelMain
 * @property Role $role
 * @property Shift $shift
 * @property WorkerObject $workerObject
 */
class GraficTabelDate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'grafic_tabel_date';
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
            [['id', 'grafic_tabel_main_id', 'date_time', 'shift_id', 'worker_object_id'], 'required'],
            [['id', 'grafic_tabel_main_id', 'shift_id', 'hourse_plan_value', 'worker_object_id', 'role_id'], 'integer'],
            [['date_time'], 'safe'],
            [['id'], 'unique'],
            [['grafic_tabel_main_id', 'date_time', 'shift_id', 'worker_object_id'], 'unique', 'targetAttribute' => ['grafic_tabel_main_id', 'date_time', 'shift_id', 'worker_object_id']],
            [['grafic_tabel_main_id'], 'exist', 'skipOnError' => true, 'targetClass' => GraficTabelMain::className(), 'targetAttribute' => ['grafic_tabel_main_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
            [['shift_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shift::className(), 'targetAttribute' => ['shift_id' => 'id']],
            [['worker_object_id'], 'exist', 'skipOnError' => true, 'targetClass' => WorkerObject::className(), 'targetAttribute' => ['worker_object_id' => 'id']],
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
            'date_time' => 'дата выхода на работу работника',
            'shift_id' => 'Внешний ключ справочника смен',
            'hourse_plan_value' => 'планновое количество часов для отработки',
            'worker_object_id' => 'Внешний ключ типизированного работника',
            'role_id' => 'внешний ключ справочника ролей - в роли кого должен или хочет выходить данный работник на смену (МГВМ/ГРП, ГРОЗ и т.д.)',
        ];
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
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
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
    public function getWorkerObject()
    {
        return $this->hasOne(WorkerObject::className(), ['id' => 'worker_object_id']);
    }
}
