<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "chane_type".
 *
 * @property int $id
 * @property string $title Наименование типа звена
 *
 * @property Chane[] $chanes
 */
class ChaneType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chane_type';
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
            'title' => 'Title',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChanes()
    {
        return $this->hasMany(Chane::className(), ['chane_type_id' => 'id']);
    }
}
