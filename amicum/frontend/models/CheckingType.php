<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checking_type".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)
 * @property string $title Название вида проверки 
 *
 * @property Checking[] $checkings
 */
class CheckingType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checking_type';
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
            [['title'], 'unique'],
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
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckings()
    {
        return $this->hasMany(Checking::className(), ['checking_type_id' => 'id']);
    }
}
