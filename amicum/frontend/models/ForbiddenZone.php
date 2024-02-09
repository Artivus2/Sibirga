<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "forbidden_zone".
 *
 * @property int $id
 * @property string $title Наименование запретной зоны
 * @property int $mine_id
 *
 * @property ForbiddenEdge[] $forbiddenEdges
 * @property Edge[] $edges
 * @property Edge[] $edges0
 * @property ForbiddenZapret[] $forbiddenZaprets
 * @property Mine $mine
 */
class ForbiddenZone extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'forbidden_zone';
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
            [['title', 'mine_id'], 'required'],
            [['mine_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Наименование запретной зоны',
            'mine_id' => 'Mine ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenEdges()
    {
        return $this->hasMany(ForbiddenEdge::className(), ['forbidden_zone_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdges()
    {
        return $this->hasMany(Edge::className(), ['id' => 'edge_id'])->viaTable('forbidden_edge', ['forbidden_zone_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdges0()
    {
        return $this->hasMany(Edge::className(), ['id' => 'edge_id'])->viaTable('forbidden_edge', ['forbidden_zone_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getForbiddenZaprets()
    {
        return $this->hasMany(ForbiddenZapret::className(), ['forbidden_zone_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'mine_id']);
    }
}
