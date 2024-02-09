<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_template_place".
 *
 * @property int $id ключ таблицы привязки наряда к месту ведения горных работ
 * @property int|null $place_id внешний ключ справочника мест
 * @property int|null $passport_id паспорт ведения работ
 * @property int $order_template_id ключ шаблона наряда
 * @property string|null $coordinate координаты
 * @property int|null $edge_id
 * @property int|null $route_template_id
 * @property int|null $place_from_id внешний ключ справочника мест из которого едут
 * @property int|null $place_to_id внешний ключ справочника мест в которое едут
 *
 * @property OrderTemplateOperation[] $orderTemplateOperations
 * @property OrderTemplate $orderTemplate
 * @property Passport $passport
 * @property Place $place
 * @property Place $placeFrom
 * @property Place $placeTo
 */
class OrderTemplatePlace extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_template_place';
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
            [['place_id', 'passport_id', 'order_template_id', 'edge_id', 'route_template_id', 'place_from_id', 'place_to_id'], 'integer'],
            [['order_template_id'], 'required'],
            [['coordinate'], 'string', 'max' => 50],
            [['order_template_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderTemplate::className(), 'targetAttribute' => ['order_template_id' => 'id']],
            [['passport_id'], 'exist', 'skipOnError' => true, 'targetClass' => Passport::className(), 'targetAttribute' => ['passport_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
            [['place_from_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_from_id' => 'id']],
            [['place_to_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_to_id' => 'id']],
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
            'passport_id' => 'Passport ID',
            'order_template_id' => 'Order Template ID',
            'coordinate' => 'Coordinate',
            'edge_id' => 'Edge ID',
            'route_template_id' => 'Route Template ID',
            'place_from_id' => 'Place From ID',
            'place_to_id' => 'Place To ID',
        ];
    }

    /**
     * Gets query for [[OrderTemplateOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplateOperations()
    {
        return $this->hasMany(OrderTemplateOperation::className(), ['order_template_place_id' => 'id']);
    }

    /**
     * Gets query for [[OrderTemplate]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplate()
    {
        return $this->hasOne(OrderTemplate::className(), ['id' => 'order_template_id']);
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
     * Gets query for [[PlaceFrom]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceFrom()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_from_id']);
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
     * @return \yii\db\ActiveQuery
     */
    public function getOperations()
    {
        return $this->hasMany(Operation::className(), ['id' => 'operation_id'])->viaTable('order_template_operation', ['order_template_place_id' => 'id']);
    }
}
