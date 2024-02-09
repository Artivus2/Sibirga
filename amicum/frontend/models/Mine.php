<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "mine".
 *
 * @property int $id ключ шахты
 * @property string $title название шахты
 * @property int $object_id ключ типового объекта
 * @property int $company_id ключ связанного подразделения
 * @property int|null $version_scheme Версия схемы шахты
 *
 * @property BrigadeWorker[] $brigadeWorkers
 * @property ChaneWorker[] $chaneWorkers
 * @property Conjunction[] $conjunctions
 * @property EventCompareGas[] $eventCompareGas
 * @property EventCompareGas[] $eventCompareGas0
 * @property Examination[] $examinations
 * @property ForbiddenZone[] $forbiddenZones
 * @property GraficTabelDateFact[] $graficTabelDateFacts
 * @property Company $company
 * @property Object $object
 * @property MineCameraRotation $mineCameraRotation
 * @property MineFunction[] $mineFunctions
 * @property MineParameter[] $mineParameters
 * @property Order[] $orders
 * @property OrderVtbAb[] $orderVtbAbs
 * @property Place[] $places
 * @property SituationJournal[] $situationJournals
 * @property User[] $users
 * @property Worker[] $workers
 */
class Mine extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mine';
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
            [['id', 'title', 'object_id'], 'required'],
            [['id', 'object_id', 'company_id', 'version_scheme'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['id'], 'unique'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'id']],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
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
            'object_id' => 'Object ID',
            'company_id' => 'Company ID',
            'version_scheme' => 'Version Scheme',
        ];
    }

    /**
     * Gets query for [[BrigadeWorkers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBrigadeWorkers()
    {
        return $this->hasMany(BrigadeWorker::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[ChaneWorkers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChaneWorkers()
    {
        return $this->hasMany(ChaneWorker::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[Conjunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctions()
    {
        return $this->hasMany(Conjunction::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[EventCompareGas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventCompareGas()
    {
        return $this->hasMany(EventCompareGas::className(), ['lamp_mine_id' => 'id']);
    }

    /**
     * Gets query for [[EventCompareGas0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventCompareGas0()
    {
        return $this->hasMany(EventCompareGas::className(), ['static_mine_id' => 'id']);
    }

    /**
     * Gets query for [[Examinations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExaminations()
    {
        return $this->hasMany(Examination::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[ForbiddenZones]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenZones()
    {
        return $this->hasMany(ForbiddenZone::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[GraficTabelDateFacts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelDateFacts()
    {
        return $this->hasMany(GraficTabelDateFact::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[Company]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['id' => 'company_id']);
    }

    /**
     * Gets query for [[Object]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
    }

    /**
     * Gets query for [[MineCameraRotation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMineCameraRotation()
    {
        return $this->hasOne(MineCameraRotation::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[MineFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMineFunctions()
    {
        return $this->hasMany(MineFunction::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[MineParameters]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMineParameters()
    {
        return $this->hasMany(MineParameter::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[OrderVtbAbs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderVtbAbs()
    {
        return $this->hasMany(OrderVtbAb::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[Places]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlaces()
    {
        return $this->hasMany(Place::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[SituationJournals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournals()
    {
        return $this->hasMany(SituationJournal::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['mine_id' => 'id']);
    }

    /**
     * Gets query for [[Workers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkers()
    {
        return $this->hasMany(Worker::className(), ['mine_id' => 'id']);
    }
}
