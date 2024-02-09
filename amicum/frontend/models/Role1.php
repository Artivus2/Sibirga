<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "role_1".
 *
 * @property int $id
 * @property string $title
 * @property int $weight
 * @property int $type
 */
class Role1 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'role_1';
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
            [['id', 'weight', 'type'], 'integer'],
            [['title'], 'string', 'max' => 255],
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
            'weight' => 'Weight',
            'type' => 'Type',
        ];
    }
}
