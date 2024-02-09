<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "edge_changes_history".
 *
 * @property int $id
 * @property int $id_edge_changes
 * @property int $edge_id
 *
 * @property Edge $edge
 * @property EdgeChanges $edgeChanges
 */
class EdgeChangesHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'edge_changes_history';
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
            [['id_edge_changes', 'edge_id'], 'required'],
            [['id_edge_changes', 'edge_id'], 'integer'],
            [['id_edge_changes', 'edge_id'], 'unique', 'targetAttribute' => ['id_edge_changes', 'edge_id']],
            [['edge_id'], 'exist', 'skipOnError' => true, 'targetClass' => Edge::className(), 'targetAttribute' => ['edge_id' => 'id']],
            [['id_edge_changes'], 'exist', 'skipOnError' => true, 'targetClass' => EdgeChanges::className(), 'targetAttribute' => ['id_edge_changes' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_edge_changes' => 'Id Edge Changes',
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
    public function getEdgeChanges()
    {
        return $this->hasOne(EdgeChanges::className(), ['id' => 'id_edge_changes']);
    }
}
