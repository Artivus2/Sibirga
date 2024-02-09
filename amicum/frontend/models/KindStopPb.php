<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_stop_pb".
 *
 * @property int $id Идентификатор текущей таблицы (автоинкрементный)\n
 * @property string $title Название вида простоя
 *
 * @property StopPb[] $stopPbs
 */
class KindStopPb extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_stop_pb';
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
            'id' => 'Идентификатор текущей таблицы (автоинкрементный)\\n',
            'title' => 'Название вида простоя',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStopPbs()
    {
        return $this->hasMany(StopPb::className(), ['kind_stop_pb_id' => 'id']);
    }
}
