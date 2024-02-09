<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "forbidden_edge".
 *
 * @property int $id
 * @property int $forbidden_zone_id Внешний идентификатор запретной зоны
 * @property int $edge_id Внешний идентификатор выработки
 *
 * @property Edge $edge
 * @property ForbiddenZone $forbiddenZone
 */
class ForbiddenEdge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'forbidden_edge';
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
            [['forbidden_zone_id', 'edge_id'], 'required'],
            [['forbidden_zone_id', 'edge_id'], 'integer'],
            [['forbidden_zone_id', 'edge_id'], 'unique', 'targetAttribute' => ['forbidden_zone_id', 'edge_id']],
            [['edge_id'], 'exist', 'skipOnError' => true, 'targetClass' => Edge::className(), 'targetAttribute' => ['edge_id' => 'id']],
            [['forbidden_zone_id'], 'exist', 'skipOnError' => true, 'targetClass' => ForbiddenZone::className(), 'targetAttribute' => ['forbidden_zone_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'forbidden_zone_id' => 'Forbidden Zone ID',
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
    public function getForbiddenZone()
    {
        return $this->hasOne(ForbiddenZone::className(), ['id' => 'forbidden_zone_id']);
    }
}
