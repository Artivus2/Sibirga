<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "position1".
 *
 * @property int $id
 * @property string $title
 * @property string $qualification
 * @property string $short_title
 */
class Position1 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'position1';
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
            [['id', 'title'], 'required'],
            [['id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['qualification'], 'string', 'max' => 10],
            [['short_title'], 'string', 'max' => 15],
            [['id'], 'unique'],
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
            'qualification' => 'Qualification',
            'short_title' => 'Short Title',
        ];
    }
}
