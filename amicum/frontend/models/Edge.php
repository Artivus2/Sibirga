<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "edge".
 *
 * @property int $id
 * @property int $conjunction_start_id
 * @property int $conjunction_end_id
 * @property int $place_id
 * @property int $edge_type_id
 * @property int|null $ventilation_id
 * @property int|null $ventilation_current_id
 *
 * @property Conjunction $conjunctionStart
 * @property EdgeType $edgeType
 * @property Place $place
 * @property EdgeChangesHistory[] $edgeChangesHistories
 * @property EdgeChanges[] $edgeChanges
 * @property EdgeFunction[] $edgeFunctions
 * @property EdgeParameter[] $edgeParameters
 * @property EdgeStatus[] $edgeStatuses
 * @property EventCompareGas[] $eventCompareGas
 * @property EventCompareGas[] $eventCompareGas0
 * @property ForbiddenEdge[] $forbiddenEdges
 * @property ForbiddenEdge[] $forbiddenEdges0
 * @property ForbiddenZone[] $forbiddenZones
 * @property ForbiddenZone[] $forbiddenZones0
 * @property PathEdge[] $pathEdges
 * @property RouteEdge[] $routeEdges
 * @property RouteTemplateEdge[] $routeTemplateEdges
 * @property SituationJournalZone[] $situationJournalZones
 * @property SituationJournal[] $situationJournals
 */
class Edge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'edge';
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
            [['conjunction_start_id', 'conjunction_end_id', 'place_id', 'edge_type_id'], 'required'],
            [['conjunction_start_id', 'conjunction_end_id', 'place_id', 'edge_type_id', 'ventilation_id', 'ventilation_current_id'], 'integer'],
            [['conjunction_start_id'], 'exist', 'skipOnError' => true, 'targetClass' => Conjunction::className(), 'targetAttribute' => ['conjunction_start_id' => 'id']],
            [['edge_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => EdgeType::className(), 'targetAttribute' => ['edge_type_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'conjunction_start_id' => 'Conjunction Start ID',
            'conjunction_end_id' => 'Conjunction End ID',
            'place_id' => 'Place ID',
            'edge_type_id' => 'Edge Type ID',
            'ventilation_id' => 'Ventilation ID',
            'ventilation_current_id' => 'Ventilation Current ID',
        ];
    }

    /**
     * Gets query for [[ConjunctionStart]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getConjunctionStart()
    {
        return $this->hasOne(Conjunction::className(), ['id' => 'conjunction_start_id']);
    }

    /**
     * Gets query for [[EdgeType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeType()
    {
        return $this->hasOne(EdgeType::className(), ['id' => 'edge_type_id']);
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
     * @return \yii\db\ActiveQuery
     */
    public function getPlace0()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeChangesHistories()
    {
        return $this->hasMany(EdgeChangesHistory::className(), ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[EdgeChanges]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeChanges()
    {
        return $this->hasMany(EdgeChanges::className(), ['id' => 'id_edge_changes'])->viaTable('edge_changes_history', ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[EdgeFunctions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeFunctions()
    {
        return $this->hasMany(EdgeFunction::className(), ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[EdgeParameters]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeParameters()
    {
        return $this->hasMany(EdgeParameter::className(), ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[EdgeStatuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeStatuses()
    {
        return $this->hasMany(EdgeStatus::className(), ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[EventCompareGas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventCompareGas()
    {
        return $this->hasMany(EventCompareGas::className(), ['lamp_edge_id' => 'id']);
    }

    /**
     * Gets query for [[EventCompareGas0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventCompareGas0()
    {
        return $this->hasMany(EventCompareGas::className(), ['static_edge_id' => 'id']);
    }

    /**
     * Gets query for [[ForbiddenEdges]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenEdges()
    {
        return $this->hasMany(ForbiddenEdge::className(), ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[ForbiddenEdges0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenEdges0()
    {
        return $this->hasMany(ForbiddenEdge::className(), ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[ForbiddenZones]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenZones()
    {
        return $this->hasMany(ForbiddenZone::className(), ['id' => 'forbidden_zone_id'])->viaTable('forbidden_edge', ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[ForbiddenZones0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenZones0()
    {
        return $this->hasMany(ForbiddenZone::className(), ['id' => 'forbidden_zone_id'])->viaTable('forbidden_edge', ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[PathEdges]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPathEdges()
    {
        return $this->hasMany(PathEdge::className(), ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[RouteEdges]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRouteEdges()
    {
        return $this->hasMany(RouteEdge::className(), ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[RouteTemplateEdges]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRouteTemplateEdges()
    {
        return $this->hasMany(RouteTemplateEdge::className(), ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[SituationJournalZones]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournalZones()
    {
        return $this->hasMany(SituationJournalZone::className(), ['edge_id' => 'id']);
    }

    /**
     * Gets query for [[SituationJournals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationJournals()
    {
        return $this->hasMany(SituationJournal::className(), ['id' => 'situation_journal_id'])->viaTable('situation_journal_zone', ['edge_id' => 'id']);
    }

    // ручные методы - нужны для того, что бы работала выборка в сравнении двух газов

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLampPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id'])->alias('lamp_place');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaticPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id'])->alias('static_place');
    }



    // ручные методы

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id'])->alias('eventPlace');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLastStatusEdge()
    {
        return $this->hasOne(ViewEdgeStatusMaxDateFull::className(), ['edge_id' => 'id']);
    }
}
