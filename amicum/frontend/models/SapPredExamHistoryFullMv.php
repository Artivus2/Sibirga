<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_pred_exam_history_full_mv".
 *
 * @property int $id ключ теста
 * @property string|null $personal_number ключ работника
 * @property string|null $start_test_time дата и время старта экзамена
 * @property int|null $count_right количество правильных ответов
 * @property int|null $count_false количество не правильных ответов
 * @property float|null $points количество баллов
 * @property string|null $sap_kind_exam_id ключ справочника вида экзамена
 * @property string|null $date_created дата создания записи1
 * @property string|null $date_modified дата изменения записи1
 */
class SapPredExamHistoryFullMv extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_pred_exam_history_full_mv';
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
            [['start_test_time', 'date_created', 'date_modified'], 'safe'],
            [['count_right', 'count_false'], 'integer'],
            [['points'], 'number'],
            [['personal_number'], 'string', 'max' => 255],
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
            'personal_number' => 'Personal Number',
            'start_test_time' => 'Start Test Time',
            'count_right' => 'Count Right',
            'count_false' => 'Count False',
            'points' => 'Points',
            'sap_kind_exam_id' => 'Sap Kind Exam ID',
            'date_created' => 'Date Created',
            'date_modified' => 'Date Modified',
        ];
    }
}
