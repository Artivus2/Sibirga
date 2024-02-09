<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_duration".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)\n
 * @property string $title Название вида длительности (до устранения, на срок)\n
 *
 * @property StopPb[] $stopPbs
 */
class KindDuration extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_duration';
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
    public function getStopPbs()
    {
        return $this->hasMany(StopPb::className(), ['kind_duration_id' => 'id']);
    }
}
