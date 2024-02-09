<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "worker_motion_less".
 *
 * @property int $id
 * @property string|null $date_work
 * @property string|null $tabel_number
 * @property string|null $fio
 * @property string|null $value
 * @property string|null $title_department
 * @property string|null $title_place
 * @property string|null $date_time
 * @property string|null $smena
 * @property int|null $department_id
 * @property string|null $unmotion_time
 * @property int|null $mine_id
 */
class WorkerMotionLess extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_motion_less';
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
            [['date_work', 'date_time'], 'safe'],
            [['department_id', 'mine_id'], 'integer'],
            [['tabel_number'], 'string', 'max' => 20],
            [['fio'], 'string', 'max' => 500],
            [['value'], 'string', 'max' => 255],
            [['title_department'], 'string', 'max' => 700],
            [['title_place'], 'string', 'max' => 250],
            [['smena'], 'string', 'max' => 45],
            [['unmotion_time'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_work' => 'Date Work',
            'tabel_number' => 'Tabel Number',
            'fio' => 'Fio',
            'value' => 'Value',
            'title_department' => 'Title Department',
            'title_place' => 'Title Place',
            'date_time' => 'Date Time',
            'smena' => 'Smena',
            'department_id' => 'Department ID',
            'unmotion_time' => 'Unmotion Time',
            'mine_id' => 'Mine ID',
        ];
    }
}
