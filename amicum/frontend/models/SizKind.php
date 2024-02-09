<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "siz_kind".
 *
 * @property int $id
 * @property string $title Наименование вида СИЗ
 *
 * @property Siz[] $sizs
 */
class SizKind extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'siz_kind';
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
            [['title'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Наименование вида СИЗ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSizs()
    {
        return $this->hasMany(Siz::className(), ['siz_kind_id' => 'id']);
    }
}
