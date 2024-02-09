<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "order_item".
 *
 * @property int $id ключ атомарного наряда
 * @property int $order_history_id ключ главного наряда
 * @property int|null $worker_id ключ работника
 * @property int|null $equipment_id ключ оборудования
 * @property int|null $operation_id ключ операции
 * @property int|null $place_from_id ключ места откуда
 * @property int|null $place_to_id ключ места куда
 * @property int|null $group_order_id ключ группы наряда
 * @property float|null $plan план
 * @property float|null $fact факт
 * @property string|null $description комментарий
 * @property int|null $group_id ключ группы
 * @property int|null $chane_id Звено в котором числится человек
 * @property int|null $brigade_id бригада в которой числится человек
 * @property int|null $status_id Внешний ключ справочника статусов
 * @property int|null $order_operation_id_vtb ключ конкретной операции из наряда ВТБ
 * @property int|null $correct_measures_id ключ конкретной операции из предписания
 * @property int|null $order_place_id_vtb место в котором было выдан наряд ВТБ
 * @property int|null $injunction_violation_id ключ привязки нарушения к месту в наряде
 * @property int|null $injunction_id ключ привязки предписания к месту в наряде
 * @property int|null $equipment_status_id
 * @property int|null $role_id ключ роли пользователя
 * @property string $date_time_create дата и время создания
 * @property int|null $order_type_id тип наряда по месту 
 * @property int|null $chat_room_id ключ чата, для работы с отчетом, вложениями, видео, аудио
 * @property int|null $passport_id паспорт ведения работ
 * @property int|null $route_template_id ключ шаблона маршрута
 * @property string|null $order_route_json наряд путевка горного мастера АБ/ВТБ
 * @property string|null $order_route_esp_json наряд путевка электрослесарей АБ
 *
 * @property Brigade $brigade
 * @property Chane $chane
 * @property CorrectMeasures $correctMeasures
 * @property Equipment $equipment
 * @property Status $equipmentStatus
 * @property OrderItemGroup $group
 * @property Injunction $injunction
 * @property InjunctionViolation $injunctionViolation
 * @property Operation $operation
 * @property OrderHistory $orderHistory
 * @property OrderOperationPlaceVtbAb $orderOperationIdVtb
 * @property OrderPlaceVtbAb $orderPlaceIdVtb
 * @property Place $placeFrom
 * @property Place $placeTo
 * @property Role $role
 * @property Status $status
 * @property Worker $worker
 * @property ChatRoom $chatRoom
 * @property Passport $passport
 * @property RouteTemplate $routeTemplate
 * @property OrderItemStatus[] $orderItemStatuses
 */
class OrderItem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_item';
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
            [['order_history_id', 'date_time_create'], 'required'],
            [['order_history_id', 'worker_id', 'equipment_id', 'operation_id', 'place_from_id', 'place_to_id', 'group_order_id', 'group_id', 'chane_id', 'brigade_id', 'status_id', 'order_operation_id_vtb', 'correct_measures_id', 'order_place_id_vtb', 'injunction_violation_id', 'injunction_id', 'equipment_status_id', 'role_id', 'order_type_id', 'chat_room_id', 'passport_id', 'route_template_id'], 'integer'],
            [['plan', 'fact'], 'number'],
            [['description'], 'string'],
            [['date_time_create', 'order_route_json', 'order_route_esp_json'], 'safe'],
            [['brigade_id'], 'exist', 'skipOnError' => true, 'targetClass' => Brigade::className(), 'targetAttribute' => ['brigade_id' => 'id']],
            [['chane_id'], 'exist', 'skipOnError' => true, 'targetClass' => Chane::className(), 'targetAttribute' => ['chane_id' => 'id']],
            [['correct_measures_id'], 'exist', 'skipOnError' => true, 'targetClass' => CorrectMeasures::className(), 'targetAttribute' => ['correct_measures_id' => 'id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['equipment_status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['equipment_status_id' => 'id']],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderItemGroup::className(), 'targetAttribute' => ['group_id' => 'id']],
            [['injunction_id'], 'exist', 'skipOnError' => true, 'targetClass' => Injunction::className(), 'targetAttribute' => ['injunction_id' => 'id']],
            [['injunction_violation_id'], 'exist', 'skipOnError' => true, 'targetClass' => InjunctionViolation::className(), 'targetAttribute' => ['injunction_violation_id' => 'id']],
            [['operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Operation::className(), 'targetAttribute' => ['operation_id' => 'id']],
            [['order_history_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderHistory::className(), 'targetAttribute' => ['order_history_id' => 'id']],
            [['order_operation_id_vtb'], 'exist', 'skipOnError' => true, 'targetClass' => OrderOperationPlaceVtbAb::className(), 'targetAttribute' => ['order_operation_id_vtb' => 'id']],
            [['order_place_id_vtb'], 'exist', 'skipOnError' => true, 'targetClass' => OrderPlaceVtbAb::className(), 'targetAttribute' => ['order_place_id_vtb' => 'id']],
            [['place_from_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_from_id' => 'id']],
            [['place_to_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_to_id' => 'id']],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['role_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
            [['chat_room_id'], 'exist', 'skipOnError' => true, 'targetClass' => ChatRoom::className(), 'targetAttribute' => ['chat_room_id' => 'id']],
            [['passport_id'], 'exist', 'skipOnError' => true, 'targetClass' => Passport::className(), 'targetAttribute' => ['passport_id' => 'id']],
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
            'order_history_id' => 'Order History ID',
            'worker_id' => 'Worker ID',
            'equipment_id' => 'Equipment ID',
            'operation_id' => 'Operation ID',
            'place_from_id' => 'Place From ID',
            'place_to_id' => 'Place To ID',
            'group_order_id' => 'Group Order ID',
            'plan' => 'Plan',
            'fact' => 'Fact',
            'description' => 'Description',
            'group_id' => 'Group ID',
            'chane_id' => 'Chane ID',
            'brigade_id' => 'Brigade ID',
            'status_id' => 'Status ID',
            'order_operation_id_vtb' => 'Order Operation Id Vtb',
            'correct_measures_id' => 'Correct Measures ID',
            'order_place_id_vtb' => 'Order Place Id Vtb',
            'injunction_violation_id' => 'Injunction Violation ID',
            'injunction_id' => 'Injunction ID',
            'equipment_status_id' => 'Equipment Status ID',
            'role_id' => 'Role ID',
            'date_time_create' => 'Date Time Create',
            'order_type_id' => 'Order Type ID',
            'chat_room_id' => 'Chat Room ID',
            'passport_id' => 'Passport ID',
            'route_template_id' => 'Route Template ID',
            'order_route_json' => 'Order Route Json',
            'order_route_esp_json' => 'Order Route Esp Json',
        ];
    }

    /**
     * Gets query for [[Brigade]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBrigade()
    {
        return $this->hasOne(Brigade::className(), ['id' => 'brigade_id']);
    }

    /**
     * Gets query for [[Chane]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChane()
    {
        return $this->hasOne(Chane::className(), ['id' => 'chane_id']);
    }

    /**
     * Gets query for [[CorrectMeasures]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCorrectMeasures()
    {
        return $this->hasOne(CorrectMeasures::className(), ['id' => 'correct_measures_id']);
    }

    /**
     * Gets query for [[Equipment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
    }

    /**
     * Gets query for [[EquipmentStatus]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipmentStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'equipment_status_id']);
    }

    /**
     * Gets query for [[Group]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(OrderItemGroup::className(), ['id' => 'group_id']);
    }

    /**
     * Gets query for [[Injunction]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInjunction()
    {
        return $this->hasOne(Injunction::className(), ['id' => 'injunction_id']);
    }

    /**
     * Gets query for [[InjunctionViolation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionViolation()
    {
        return $this->hasOne(InjunctionViolation::className(), ['id' => 'injunction_violation_id']);
    }

    /**
     * Gets query for [[Operation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperation()
    {
        return $this->hasOne(Operation::className(), ['id' => 'operation_id']);
    }

    /**
     * Gets query for [[OrderHistory]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderHistory()
    {
        return $this->hasOne(OrderHistory::className(), ['id' => 'order_history_id']);
    }

    /**
     * Gets query for [[OrderOperationIdVtb]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderOperationIdVtb()
    {
        return $this->hasOne(OrderOperationPlaceVtbAb::className(), ['id' => 'order_operation_id_vtb']);
    }

    /**
     * Gets query for [[OrderPlaceIdVtb]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlaceIdVtb()
    {
        return $this->hasOne(OrderPlaceVtbAb::className(), ['id' => 'order_place_id_vtb']);
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
     * Gets query for [[Role]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
    }

    /**
     * Gets query for [[Status]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }

    /**
     * Gets query for [[ChatRoom]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChatRoom()
    {
        return $this->hasOne(ChatRoom::className(), ['id' => 'chat_room_id']);
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
     * Gets query for [[RouteTemplate]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRouteTemplate()
    {
        return $this->hasOne(RouteTemplate::className(), ['id' => 'route_template_id']);
    }

    /**
     * Gets query for [[OrderItemStatuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItemStatuses()
    {
        return $this->hasMany(OrderItemStatus::className(), ['order_item_id' => 'id']);
    }
}
