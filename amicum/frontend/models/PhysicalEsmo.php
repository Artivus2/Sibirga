<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "physical_esmo".
 *
 * @property int $id
 * @property int $worker_id
 * @property string $date_time_start time_MO дата начала МО
 * @property string|null $date_time_end time_dopusk_end Дата окончания МО
 * @property int|null $mo_result_id ключ медосмотра
 * @property int|null $mo_dopusk_id ключ допуска
 * @property string|null $terminal_name
 * @property string|null $question
 * @property int|null $alko
 * @property int|null $systolic
 * @property int|null $diastolic
 * @property int|null $pulse
 * @property float|null $temperature
 * @property int|null $tk
 * @property int|null $mine_id ключ шахты для синхронизации
 *
 * @property MoResult $moResult
 * @property MoDopusk $moDopusk
 */
class PhysicalEsmo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'physical_esmo';
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
            [['worker_id', 'date_time_start'], 'required'],
            [['worker_id', 'mo_result_id', 'mo_dopusk_id', 'alko', 'systolic', 'diastolic', 'pulse', 'tk', 'mine_id'], 'integer'],
            [['date_time_start', 'date_time_end'], 'safe'],
            [['temperature'], 'number'],
            [['terminal_name', 'question'], 'string', 'max' => 45],
            [['mo_result_id'], 'exist', 'skipOnError' => true, 'targetClass' => MoResult::className(), 'targetAttribute' => ['mo_result_id' => 'id']],
            [['mo_dopusk_id'], 'exist', 'skipOnError' => true, 'targetClass' => MoDopusk::className(), 'targetAttribute' => ['mo_dopusk_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_id' => 'Worker ID',
            'date_time_start' => 'Date Time Start',
            'date_time_end' => 'Date Time End',
            'mo_result_id' => 'Mo Result ID',
            'mo_dopusk_id' => 'Mo Dopusk ID',
            'terminal_name' => 'Terminal Name',
            'question' => 'Question',
            'alko' => 'Alko',
            'systolic' => 'Systolic',
            'diastolic' => 'Diastolic',
            'pulse' => 'Pulse',
            'temperature' => 'Temperature',
            'tk' => 'Tk',
            'mine_id' => 'Mine ID',
        ];
    }

    /**
     * Gets query for [[MoResult]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMoResult()
    {
        return $this->hasOne(MoResult::className(), ['id' => 'mo_result_id']);
    }

    /**
     * Gets query for [[MoDopusk]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMoDopusk()
    {
        return $this->hasOne(MoDopusk::className(), ['id' => 'mo_dopusk_id']);
    }
}
