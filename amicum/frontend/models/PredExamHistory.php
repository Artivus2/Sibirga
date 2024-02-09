<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "pred_exam_history".
 *
 * @property int $id ключ теста
 * @property int|null $mine_id ключ шахты для синхронизации
 * @property string $employee_id ключ работника
 * @property int|null $mo_session_id ключ медицинского осмотра (если тестирование было при МО)
 * @property string $start_test_time дата и время старта экзамена1
 * @property int|null $status_id Ключ статуса прохождения предсменного тестирования (контроль знаний начат, контроль знаний окончен, контроль знаний прерван)
 * @property string $sap_kind_exam_id ключ справочника вида экзамена
 * @property int|null $count_right количество правильных ответов (question_count)
 * @property int|null $count_false количество не правильных ответов (cnt_correct)
 * @property int|null $question_count Количество заданных вопросов
 * @property float|null $points количество баллов
 * @property int|null $sap_id ключ интеграции (sap_id или quiz_session_id)
 * @property string|null $date_created дата создания записи
 * @property string|null $date_modified дата изменения записи
 */
class PredExamHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pred_exam_history';
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
            [['mine_id', 'mo_session_id', 'status_id', 'count_right', 'count_false', 'question_count', 'sap_id'], 'integer'],
            [['employee_id', 'start_test_time'], 'required'],
            [['start_test_time', 'date_created', 'date_modified'], 'safe'],
            [['points'], 'number'],
            [['employee_id'], 'string', 'max' => 255],
            [['sap_kind_exam_id'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mine_id' => 'Mine ID',
            'employee_id' => 'Worker ID',
            'mo_session_id' => 'Mo Session ID',
            'start_test_time' => 'Start Test Time',
            'status_id' => 'Status ID',
            'sap_kind_exam_id' => 'Sap Kind Exam ID',
            'count_right' => 'Count Right',
            'count_false' => 'Count False',
            'question_count' => 'Question Count',
            'points' => 'Points',
            'sap_id' => 'Sap ID',
            'date_created' => 'Date Created',
            'date_modified' => 'Date Modified',
        ];
    }

    /**
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEmployee()
    {
        return $this->hasOne(Employee::className(), ['id' => 'employee_id']);
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
}
