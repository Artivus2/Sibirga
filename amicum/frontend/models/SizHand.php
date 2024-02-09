<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "siz_hand".
 *
 * @property int $id ключ сиз
 * @property string|null $title Название СИЗ
 * @property string|null $link_1c ключ СИЗ из внешней системы 1С
 */
class SizHand extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'siz_hand';
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
            [['title'], 'string', 'max' => 255],
            [['link_1c'], 'string', 'max' => 100],
            [['link_1c'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'link_1c' => 'Link 1c',
        ];
    }
}
