<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "season".
 *
 * @property int $id Идентификатор(автоинкрементный)
 * @property string $title Наименование сезона
 *
 * @property Siz[] $sizs
 */
class Season extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'season';
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
            'title' => 'Наименование сезона',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSizs()
    {
        return $this->hasMany(Siz::className(), ['season_id' => 'id']);
    }
}
