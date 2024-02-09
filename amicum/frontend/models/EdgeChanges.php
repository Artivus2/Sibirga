<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "edge_changes".
 *
 * @property int $id
 * @property string $date_time
 * @property int $status_id
 *
 * @property Status $status
 * @property EdgeChangesHistory[] $edgeChangesHistories
 * @property Edge[] $edges
 */
class EdgeChanges extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'edge_changes';
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
            [['date_time'], 'required'],
            [['date_time'], 'safe'],
            [['status_id'], 'integer'],
            [['date_time'], 'unique'],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_time' => 'Date Time',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeChangesHistories()
    {
        return $this->hasMany(EdgeChangesHistory::className(), ['id_edge_changes' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdges()
    {
        return $this->hasMany(Edge::className(), ['id' => 'edge_id'])->viaTable('edge_changes_history', ['id_edge_changes' => 'id']);
    }
}
