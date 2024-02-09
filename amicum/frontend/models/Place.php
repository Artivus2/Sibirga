<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "place".
 *
 * @property int $id ключ справочника мест
 * @property string $title название места
 * @property int $mine_id внешний ключ справочника шахт
 * @property int $object_id внешний ключ справочника типовых объектов
 * @property int $plast_id внешний ключ справочника пластов
 *
 * @property AuditPlace[] $auditPlaces
 * @property CheckingPlace[] $checkingPlaces
 * @property CheckingPlan[] $checkingPlans
 * @property ConfigurationFace[] $configurationFaces
 * @property Edge[] $edges
 * @property FireFightingObject[] $fireFightingObjects
 * @property Injunction[] $injunctions
 * @property InjunctionViolation[] $injunctionViolations
 * @property ObjectPlace[] $objectPlaces
 * @property OrderPlace[] $orderPlaces
 * @property Order[] $orders
 * @property OrderPlaceVtbAb[] $orderPlaceVtbAbs
 * @property OrderRelation[] $orderRelations
 * @property OrderTemplatePlace[] $orderTemplatePlaces
 * @property OrderTemplate[] $orderTemplates
 * @property Passport[] $passports
 * @property Mine $mine
 * @property Object $object
 * @property Plast $plast
 * @property PlaceCompanyDepartment[] $placeCompanyDepartments
 * @property PlaceFunction[] $placeFunctions
 * @property PlaceOperation[] $placeOperations
 * @property PlaceParameter[] $placeParameters
 * @property StopPb[] $stopPbs
 */
class Place extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'place';
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
            [['title', 'mine_id', 'object_id'], 'required'],
            [['mine_id', 'object_id', 'plast_id'], 'integer'],
            [['title'], 'string', 'max' => 250],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
            [['plast_id'], 'exist', 'skipOnError' => true, 'targetClass' => Plast::className(), 'targetAttribute' => ['plast_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ключ справочника мест',
            'title' => 'название места',
            'mine_id' => 'внешний ключ справочника шахт',
            'object_id' => 'внешний ключ справочника типовых объектов',
            'plast_id' => 'внешний ключ справочника пластов',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuditPlaces()
    {
        return $this->hasMany(AuditPlace::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingPlaces()
    {
        return $this->hasMany(CheckingPlace::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckingPlans()
    {
        return $this->hasMany(CheckingPlan::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigurationFaces()
    {
        return $this->hasMany(ConfigurationFace::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdges()
    {
        return $this->hasMany(Edge::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFireFightingObjects()
    {
        return $this->hasMany(FireFightingObject::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctions()
    {
        return $this->hasMany(Injunction::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionViolations()
    {
        return $this->hasMany(InjunctionViolation::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObjectPlaces()
    {
        return $this->hasMany(ObjectPlace::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlaces()
    {
        return $this->hasMany(OrderPlace::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['id' => 'order_id'])->viaTable('order_place', ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderPlaceVtbAbs()
    {
        return $this->hasMany(OrderPlaceVtbAb::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderRelations()
    {
        return $this->hasMany(OrderRelation::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplatePlaces()
    {
        return $this->hasMany(OrderTemplatePlace::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderTemplates()
    {
        return $this->hasMany(OrderTemplate::className(), ['id' => 'order_template_id'])->viaTable('order_template_place', ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPassports()
    {
        return $this->hasMany(Passport::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'mine_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlast()
    {
        return $this->hasOne(Plast::className(), ['id' => 'plast_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceCompanyDepartments()
    {
        return $this->hasMany(PlaceCompanyDepartment::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceFunctions()
    {
        return $this->hasMany(PlaceFunction::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceOperations()
    {
        return $this->hasMany(PlaceOperation::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaceParameters()
    {
        return $this->hasMany(PlaceParameter::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStopPbs()
    {
        return $this->hasMany(StopPb::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventJournals()
    {
        return $this->hasMany(EventJournal::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChaneGroupOperations()
    {
        return $this->hasMany(OrderByChaneGroupOperation::className(), ['place_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByWorkers()
    {
        return $this->hasMany(OrderByWorker::className(), ['place_id' => 'id']);
    }
}
