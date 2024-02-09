<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "unity_texture".
 *
 * @property int $id
 * @property string $texture
 * @property string $title
 * @property string $description
 */
class UnityTexture extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'unity_texture';
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
            [['texture', 'title'], 'required'],
            [['description'], 'string'],
            [['texture', 'title'], 'string', 'max' => 120],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'texture' => 'Texture',
            'title' => 'Title',
            'description' => 'Description',
        ];
    }
}
