<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_exam".
 *
 * @property int $id ключ справочника видов экзаменов
 * @property string $name название вида экзамена
 * @property int|null $quantity количество правильных ответов как проходной бал
 * @property string|null $date_created дата создания записи1
 * @property string|null $date_modified дата изменения записи1
 * @property int|null $sap_id ключ из сап
 */
class KindExam extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_exam';
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
            [['name'], 'required'],
            [['quantity', 'sap_id'], 'integer'],
            [['date_created', 'date_modified'], 'safe'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'quantity' => 'Quantity',
            'date_created' => 'Date Created',
            'date_modified' => 'Date Modified',
            'sap_id' => 'Sap ID',
        ];
    }
}
