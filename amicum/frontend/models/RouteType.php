<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "route_type".
 *
 * @property int $id Индентификатор типа маршрута. 
 * @property string $title Название типа маршрута
 *
 * @property Route[] $routes
 * @property RouteTemplate[] $routeTemplates
 */
class RouteType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'route_type';
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
    public function getRoutes()
    {
        return $this->hasMany(Route::className(), ['route_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRouteTemplates()
    {
        return $this->hasMany(RouteTemplate::className(), ['route_type_id' => 'id']);
    }
}
