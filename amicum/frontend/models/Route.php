<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "route".
 *
 * @property int $id Идентификатор маршрута
 * @property string $title Название маршрута
 * @property int $order_id Внешний идентификатор наряда
 * @property double $offset_end  интерполированное значение (ОТ 0 ДО 1) если марщрут начинается не с начала или конца эджа
 * @property int $chane_id кому пренадлежит (звено)
 * @property double $offset_start интерполированное значение (ОТ 0 ДО 1) если марщрут начинается не с начала или конца эджа
 * @property int $status_id Внешний идентификатор справочника статусов
 * @property int $route_type_id
 *
 * @property Order $order
 * @property RouteType $routeType
 * @property Status $status
 * @property RouteEdge[] $routeEdges
 */
class Route extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'route';
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
            [['title', 'order_id', 'offset_end', 'chane_id', 'offset_start', 'status_id', 'route_type_id'], 'required'],
            [['order_id', 'chane_id', 'status_id', 'route_type_id'], 'integer'],
            [['offset_end', 'offset_start'], 'number'],
            [['title'], 'string', 'max' => 255],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
            [['route_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => RouteType::className(), 'targetAttribute' => ['route_type_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор маршрута',
            'title' => 'Название маршрута',
            'order_id' => 'Внешний идентификатор наряда',
            'offset_end' => ' интерполированное значение (ОТ 0 ДО 1) если марщрут начинается не с начала или конца эджа',
            'chane_id' => 'кому пренадлежит (звено)',
            'offset_start' => 'интерполированное значение (ОТ 0 ДО 1) если марщрут начинается не с начала или конца эджа',
            'status_id' => 'Внешний идентификатор справочника статусов',
            'route_type_id' => 'Route Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRouteType()
    {
        return $this->hasOne(RouteType::className(), ['id' => 'route_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRouteEdges()
    {
        return $this->hasMany(RouteEdge::className(), ['route_id' => 'id']);
    }
}
