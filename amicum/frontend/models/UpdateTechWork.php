<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "update_tech_work".
 *
 * @property int $id ключ технических работ
 * @property string $date_start дата и время начала технических работ
 * @property string $date_end дата и время окончания технических работ
 * @property string $description описание технических работ
 */
class UpdateTechWork extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'update_tech_work';
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
            [['date_start', 'date_end', 'description'], 'required'],
            [['date_start', 'date_end'], 'safe'],
            [['description'], 'string', 'max' => 999],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_start' => 'Date Start',
            'date_end' => 'Date End',
            'description' => 'Description',
        ];
    }
}
