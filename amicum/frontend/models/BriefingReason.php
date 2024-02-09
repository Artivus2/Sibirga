<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "briefing_reason".
 *
 * @property int $id
 * @property string $title Причины инструктажа
 * @property string $parent_id идеинтификатор родителя
 */
class BriefingReason extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'briefing_reason';
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
            [['title', 'parent_id'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Причины инструктажа',
            'parent_id' => 'идеинтификатор родителя',
        ];
    }
}
