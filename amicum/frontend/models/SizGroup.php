<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "siz_group".
 *
 * @property int $id Идентификатор(автоинкрементный)
 * @property string $title Наименование группы СИЗ
 *
 * @property SizSubgroup[] $sizSubgroups
 */
class SizGroup extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'siz_group';
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
            'id' => 'Идентификатор(автоинкрементный)',
            'title' => 'Наименование группы СИЗ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSizSubgroups()
    {
        return $this->hasMany(SizSubgroup::className(), ['siz_group_id' => 'id']);
    }
}
