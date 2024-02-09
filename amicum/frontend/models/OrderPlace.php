<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_place".
 *
 * @property int $id ключ таблицы привязки наряда к месту ведения горных работ
 * @property int $order_id вешний ключ списка нарядов
 * @property int $place_id внешний ключ справочника мест
 * @property int|null $passport_id паспорт ведения работ
 * @property string|null $coordinate координаты
 * @property int|null $edge_id ключ выработки 
 * @property int|null $route_template_id ключ шаблона маршрута
 * @property string|null $description описание наряда
 * @property int|null $place_from_id внешний ключ справочника мест из которого едут
 * @property int|null $place_to_id внешний ключ справочника мест в которое едут
 *
 * @property OrderOperation[] $orderOperations
 * @property Passport $passport
 * @property Place $place
 * @property Order $order
 * @property RouteTemplate $routeTemplate
 * @property Place $placeTo
 * @property Place $placeFrom
 * @property OrderPlacePath[] $orderPlacePaths
 * @property OrderPlaceReason[] $orderPlaceReasons
 * @property OrderRouteWorker[] $orderRouteWorkers
 */
class OrderPlace extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_place';
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
            [['order_id', 'place_id'], 'required'],
            [['order_id', 'place_id', 'passport_id', 'edge_id', 'route_template_id', 'place_from_id', 'place_to_id'], 'integer'],
            [['coordinate'], 'string', 'max' => 50],
            [['description'], 'string', 'max' => 955],
            [['order_id', 'place_id'], 'unique', 'targetAttribute' => ['order_id', 'place_id']],
            [['passport_id'], 'exist', 'skipOnError' => true, 'targetClass' => Passport::className(), 'targetAttribute' => ['passport_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
            [['route_template_id'], 'exist', 'skipOnError' => true, 'targetClass' => RouteTemplate::className(), 'targetAttribute' => ['route_template_id' => 'id']],
            [['place_to_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_to_id' => 'id']],
            [['place_from_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_from_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'place_id' => 'Place ID',
            'passport_id' => 'Passport ID',
            'coordinate' => 'Coordinate',
            'edge_id' => 'Edge ID',
            'route_template_id' => 'Route Template ID',
            'description' => 'Description',
            'place_from_id' => 'Place From ID',
            'place_to_id' => 'Place To ID',
        ];
    }

    /**
     * Gets query for [[OrderOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperations()
    {
        return $this->hasMany(OrderOperation::className(), ['order_place_id' => 'id']);
    }

    /**
     * Gets query for [[Passport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPassport()
    {
        return $this->hasOne(Passport::className(), ['id' => 'passport_id']);
    }

    /**
     * Gets query for [[Place]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * Gets query for [[Order]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }

    /**
     * Gets query for [[RouteTemplate]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRouteTemplate()
    {
        return $this->hasOne(RouteTemplate::className(), ['id' => 'route_template_id']);
    }

    /**
     * Gets query for [[PlaceTo]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceTo()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_to_id']);
    }

    /**
     * Gets query for [[PlaceFrom]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceFrom()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_from_id']);
    }

    /**
     * Gets query for [[OrderPlacePaths]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlacePaths()
    {
        return $this->hasMany(OrderPlacePath::className(), ['order_place_id' => 'id']);
    }

    /**
     * Gets query for [[OrderPlaceReasons]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlaceReasons()
    {
        return $this->hasOne(OrderPlaceReason::className(), ['order_place_id' => 'id']);
    }

    /**
     * Gets query for [[OrderRouteWorkers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderRouteWorkers()
    {
        return $this->hasMany(OrderRouteWorker::className(), ['order_place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOperations()
    {
        return $this->hasMany(Operation::className(), ['id' => 'operation_id'])->viaTable('order_operation', ['order_place_id' => 'id']);
    }
}
