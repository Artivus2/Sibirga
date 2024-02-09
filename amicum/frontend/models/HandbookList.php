<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "handbook_list".
 *
 * @property int $id ключ справочника
 * @property string $title название справочника
 * @property string $url путь до справочника
 * @property string $description краткое описание справчочника
 */
class HandbookList extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'handbook_list';
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
            [['title', 'url', 'description'], 'required'],
            [['title'], 'string', 'max' => 500],
            [['url', 'description'], 'string', 'max' => 1000],
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
            'url' => 'Url',
            'description' => 'Description',
        ];
    }
}
