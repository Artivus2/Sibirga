<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "object_place".
 *
 * @property int $id
 * @property int $place_id
 * @property string $polygon
 * @property string $date_time
 * @property int $main_id
 *
 * @property Main $main
 * @property Place $place
 */
class ObjectPlace extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'object_place';
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
            [['place_id', 'polygon', 'date_time', 'main_id'], 'required'],
            [['place_id', 'main_id'], 'integer'],
            [['polygon'], 'string'],
            [['date_time'], 'safe'],
            [['main_id'], 'exist', 'skipOnError' => true, 'targetClass' => Main::className(), 'targetAttribute' => ['main_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'place_id' => 'Place ID',
            'polygon' => 'Polygon',
            'date_time' => 'Date Time',
            'main_id' => 'Main ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMain()
    {
        return $this->hasOne(Main::className(), ['id' => 'main_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }
}
