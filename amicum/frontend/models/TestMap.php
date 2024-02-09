<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "test_map".
 *
 * @property int $id ключ привязки теста к карте и номеру на карте
 * @property int $map_id ключ карты
 * @property int $number_on_map номер на карте
 * @property int $test_id ключ теста
 *
 * @property Test $test
 * @property Map $map
 */
class TestMap extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'test_map';
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
            [['map_id', 'number_on_map', 'test_id'], 'required'],
            [['map_id', 'number_on_map', 'test_id'], 'integer'],
            [['map_id', 'test_id', 'number_on_map'], 'unique', 'targetAttribute' => ['map_id', 'test_id', 'number_on_map']],
            [['test_id'], 'exist', 'skipOnError' => true, 'targetClass' => Test::className(), 'targetAttribute' => ['test_id' => 'id']],
            [['map_id'], 'exist', 'skipOnError' => true, 'targetClass' => Map::className(), 'targetAttribute' => ['map_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ привязки теста к карте и номеру на карте',
            'map_id' => 'ключ карты',
            'number_on_map' => 'номер на карте',
            'test_id' => 'ключ теста',
        ];
    }

    /**
     * Gets query for [[Test]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTest()
    {
        return $this->hasOne(Test::className(), ['id' => 'test_id']);
    }

    /**
     * Gets query for [[Map]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMap()
    {
        return $this->hasOne(Map::className(), ['id' => 'map_id']);
    }
}
