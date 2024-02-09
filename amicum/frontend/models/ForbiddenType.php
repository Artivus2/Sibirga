<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "forbidden_type".
 *
 * @property int $id
 * @property string $title
 *
 * @property ForbiddenTimeType[] $forbiddenTimeTypes
 * @property ForbiddenZapret[] $forbiddenZaprets
 */
class ForbiddenType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'forbidden_type';
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
    public function getForbiddenTimeTypes()
    {
        return $this->hasMany(ForbiddenTimeType::className(), ['forbidden_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenZaprets()
    {
        return $this->hasMany(ForbiddenZapret::className(), ['forbidden_type_id' => 'id']);
    }
}
