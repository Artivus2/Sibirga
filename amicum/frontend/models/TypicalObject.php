<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "object".
 *
 * @property int $id
 * @property string $title
 * @property int $object_type_id
 * @property string $object_table
 *
 * @property Conjunction[] $conjunctions
 * @property EnergyMine[] $energyMines
 * @property Equipment[] $equipments
 * @property Event[] $events
 * @property Face[] $faces
 * @property Mine[] $mines
 * @property MineSituation[] $mineSituations
 * @property ObjectType $objectType
 * @property ObjectModel[] $objectModels
 * @property Order[] $orders
 * @property OrderByChane[] $orderByChanes
 * @property PassportObject[] $passportObjects
 * @property Pla[] $plas
 * @property Place[] $places
 * @property Plast[] $plasts
 * @property PpsMine[] $ppsMines
 * @property Regulation[] $regulations
 * @property RepairMapSpecific[] $repairMapSpecifics
 * @property RepairMapTypical[] $repairMapTypicals
 * @property Sensor[] $sensors
 * @property Situation[] $situations
 * @property TypeObjectFunction[] $typeObjectFunctions
 * @property TypeObjectParameter[] $typeObjectParameters
 * @property WorkerObject[] $workerObjects
 * @property Worker[] $workers
 */
class TypicalObject extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'object';
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
            [['title', 'object_type_id'], 'required'],
            [['object_type_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['object_table'], 'string', 'max' => 200],
            [['title'], 'unique'],
            [['object_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ObjectType::className(), 'targetAttribute' => ['object_type_id' => 'id']],
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
            'object_type_id' => 'Object Type ID',
            'object_table' => 'Object Table',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctions()
    {
        return $this->hasMany(Conjunction::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyMines()
    {
        return $this->hasMany(EnergyMine::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipments()
    {
        return $this->hasMany(Equipment::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvents()
    {
        return $this->hasMany(Event::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFaces()
    {
        return $this->hasMany(Face::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMines()
    {
        return $this->hasMany(Mine::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituations()
    {
        return $this->hasMany(MineSituation::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObjectType()
    {
        return $this->hasOne(ObjectType::className(), ['id' => 'object_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObjectModels()
    {
        return $this->hasMany(ObjectModel::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChanes()
    {
        return $this->hasMany(OrderByChane::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPassportObjects()
    {
        return $this->hasMany(PassportObject::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlas()
    {
        return $this->hasMany(Pla::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaces()
    {
        return $this->hasMany(Place::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlasts()
    {
        return $this->hasMany(Plast::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPpsMines()
    {
        return $this->hasMany(PpsMine::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRegulations()
    {
        return $this->hasMany(Regulation::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecifics()
    {
        return $this->hasMany(RepairMapSpecific::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicals()
    {
        return $this->hasMany(RepairMapTypical::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSensors()
    {
        return $this->hasMany(Sensor::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSituations()
    {
        return $this->hasMany(Situation::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectFunctions()
    {
        return $this->hasMany(TypeObjectFunction::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeObjectParameters()
    {
        return $this->hasMany(TypeObjectParameter::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerObjects()
    {
        return $this->hasMany(WorkerObject::className(), ['object_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkers()
    {
        return $this->hasMany(Worker::className(), ['id' => 'worker_id'])->viaTable('worker_object', ['object_id' => 'id']);
    }
}
