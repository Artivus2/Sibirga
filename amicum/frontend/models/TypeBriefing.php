<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_briefing".
 *
 * @property int $id Ключ типа инструктажа
 * @property string $title Название типа инструктажа
 *
 * @property Briefing[] $briefings
 */
class TypeBriefing extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_briefing';
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
            'id' => 'Ключ типа инструктажа',
            'title' => 'Название типа инструктажа',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBriefings()
    {
        return $this->hasMany(Briefing::className(), ['type_briefing_id' => 'id']);
    }
}
