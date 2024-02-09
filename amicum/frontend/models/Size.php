<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "size".
 *
 * @property int $id Ключ справочника размеров
 * @property string|null $title
 * @property string|null $link_1c
 */
class Size extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'size';
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
            [['title', 'link_1c'], 'string', 'max' => 100],
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
