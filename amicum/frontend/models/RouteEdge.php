<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "route_edge".
 *
 * @property int $id
 * @property int $route_id Внешний идентификатор маршрутов
 * @property int $edge_id Внешний идентификатор эджа
 *
 * @property Edge $edge
 * @property Route $route
 */
class RouteEdge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'route_edge';
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
            [['route_id', 'edge_id'], 'required'],
            [['route_id', 'edge_id'], 'integer'],
            [['edge_id'], 'exist', 'skipOnError' => true, 'targetClass' => Edge::className(), 'targetAttribute' => ['edge_id' => 'id']],
            [['route_id'], 'exist', 'skipOnError' => true, 'targetClass' => Route::className(), 'targetAttribute' => ['route_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'route_id' => 'Route ID',
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
    public function getRoute()
    {
        return $this->hasOne(Route::className(), ['id' => 'route_id']);
    }
}
