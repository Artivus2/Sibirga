<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "place_route".
 *
 * @property int $route_template_id ключ шаблона
 * @property int $place_id ключ места
 * @property int $id ключ привязки шаблона маршрута к месту
 *
 * @property Place $place
 * @property RouteTemplate $routeTemplate
 */
class PlaceRoute extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'place_route';
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
            [['route_template_id', 'place_id'], 'required'],
            [['route_template_id', 'place_id'], 'integer'],
            [['place_id', 'route_template_id'], 'unique', 'targetAttribute' => ['place_id', 'route_template_id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
            [['route_template_id'], 'exist', 'skipOnError' => true, 'targetClass' => RouteTemplate::className(), 'targetAttribute' => ['route_template_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'route_template_id' => 'Route Template ID',
            'place_id' => 'Place ID',
            'id' => 'ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRouteTemplate()
    {
        return $this->hasOne(RouteTemplate::className(), ['id' => 'route_template_id']);
    }
}
