<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "route_template_edge".
 *
 * @property int $id
 * @property int $route_template_id Внешний идентификатор маршрутов
 * @property int $edge_id Внешний идентификатор эджа
 *
 * @property Edge $edge
 * @property RouteTemplate $routeTemplate
 */
class RouteTemplateEdge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'route_template_edge';
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
            [['route_template_id', 'edge_id'], 'required'],
            [['route_template_id', 'edge_id'], 'integer'],
            [['edge_id'], 'exist', 'skipOnError' => true, 'targetClass' => Edge::className(), 'targetAttribute' => ['edge_id' => 'id']],
            [['route_template_id'], 'exist', 'skipOnError' => true, 'targetClass' => RouteTemplate::className(), 'targetAttribute' => ['route_template_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'route_template_id' => 'Route Template ID',
            'edge_id' => 'Edge ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdge()
    {
        return $this->hasOne(Edge::className(), ['id' => 'edge_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRouteTemplate()
    {
        return $this->hasOne(RouteTemplate::className(), ['id' => 'route_template_id']);
    }
}
