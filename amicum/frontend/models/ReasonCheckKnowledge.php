<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "reason_check_knowledge".
 *
 * @property int $id
 * @property string $title Наименование причины проверки знаний
 */
class ReasonCheckKnowledge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'reason_check_knowledge';
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
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Наименование причины проверки знаний',
        ];
    }
}
