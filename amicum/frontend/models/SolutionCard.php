<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "solution_card".
 *
 * @property int $id ключ карточки решения ситуации
 * @property int $situation_solution_id ключ решения ситуации
 * @property string|null $title название решения/устранения
 * @property int|null $solution_parent_id id родителя решения/устранения
 * @property int|null $solution_parent_end_flag флаг первого/последнего действия (2 - последнее действие, 1 - первое действие, 0 - обычное действие)
 * @property int|null $solution_number номер карточки по порядку в решении
 * @property string|null $solution_type тип действия (positive - действие, кт было выполнено вовремя; negative - просроченное действие)
 * @property int|null $child_action_id_negative негативное действие ключ действия при отрицательном исходе по решению карточки
 * @property int|null $child_action_id_positive позитивное действие ключ действия при положительном исходе по решению карточки
 * @property float|null $x координата абсциссы карточки действия (пока не понадобилось свойство)
 * @property float|null $y координата ординаты карточки действия (пока не понадобилось свойство)
 * @property int|null $responsible_position_id ключ должности последнего сменившего статус
 * @property int|null $responsible_worker_id ключ ответственного работника последнего сменившего статус
 * @property int|null $status_id ключ последнего статуса
 * @property string|null $date_time дата и время последнего изменения статуса
 * @property float|null $regulation_time регламентное время выполнения действия (-1 до устранения события/ любое целое цисло)
 * @property string|null $solution_date_time_start начало выполенния действия (используется для определения остатка на устранение решения)
 * @property string|null $finish_flag_mode  тип действия завершения (auto - автоматическое действие, manual - ручное)
 * @property int|null $expired_indicator_flag флаг установки индикатора просрочки действия
 * @property string|null $expired_indicator_mode тип действия просрочки (auto - автоматическое действие, manual - ручное)
 * @property string|null $description комментарий ответственного при изменении/неизменении статуса
 *
 * @property SituationSolution $situationSolution
 * @property Position $responsiblePosition
 * @property Status $status
 * @property Worker $responsibleWorker
 * @property SolutionCardStatus[] $solutionCardStatuses
 * @property SolutionOperation[] $solutionOperations
 */
class SolutionCard extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'solution_card';
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
            [['situation_solution_id'], 'required'],
            [['situation_solution_id', 'solution_parent_id', 'solution_parent_end_flag', 'solution_number', 'child_action_id_negative', 'child_action_id_positive', 'responsible_position_id', 'responsible_worker_id', 'status_id', 'expired_indicator_flag'], 'integer'],
            [['x', 'y', 'regulation_time'], 'number'],
            [['date_time', 'solution_date_time_start'], 'safe'],
            [['title'], 'string', 'max' => 500],
            [['solution_type'], 'string', 'max' => 8],
            [['finish_flag_mode', 'expired_indicator_mode'], 'string', 'max' => 6],
            [['description'], 'string', 'max' => 1000],
            [['situation_solution_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationSolution::className(), 'targetAttribute' => ['situation_solution_id' => 'id']],
            [['responsible_position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Position::className(), 'targetAttribute' => ['responsible_position_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['responsible_worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['responsible_worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'situation_solution_id' => 'Situation Solution ID',
            'title' => 'Title',
            'solution_parent_id' => 'Solution Parent ID',
            'solution_parent_end_flag' => 'Solution Parent End Flag',
            'solution_number' => 'Solution Number',
            'solution_type' => 'Solution Type',
            'child_action_id_negative' => 'Child Action Id Negative',
            'child_action_id_positive' => 'Child Action Id Positive',
            'x' => 'X',
            'y' => 'Y',
            'responsible_position_id' => 'Responsible Position ID',
            'responsible_worker_id' => 'Responsible Worker ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
            'regulation_time' => 'Regulation Time',
            'solution_date_time_start' => 'Solution Date Time Start',
            'finish_flag_mode' => 'Finish Flag Mode',
            'expired_indicator_flag' => 'Expired Indicator Flag',
            'expired_indicator_mode' => 'Expired Indicator Mode',
            'description' => 'Description',
        ];
    }

    /**
     * Gets query for [[SituationSolution]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationSolution()
    {
        return $this->hasOne(SituationSolution::className(), ['id' => 'situation_solution_id']);
    }

    /**
     * Gets query for [[ResponsiblePosition]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getResponsiblePosition()
    {
        return $this->hasOne(Position::className(), ['id' => 'responsible_position_id']);
    }

    /**
     * Gets query for [[Status]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * Gets query for [[ResponsibleWorker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getResponsibleWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'responsible_worker_id']);
    }

    /**
     * Gets query for [[SolutionCardStatuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSolutionCardStatuses()
    {
        return $this->hasMany(SolutionCardStatus::className(), ['solution_card_id' => 'id']);
    }

    /**
     * Gets query for [[SolutionOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSolutionOperations()
    {
        return $this->hasMany(SolutionOperation::className(), ['solution_card_id' => 'id']);
    }


    /**
     * Gets query for [[SolutionCards]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'responsible_worker_id'])->alias("workerActionCard");
    }
}
