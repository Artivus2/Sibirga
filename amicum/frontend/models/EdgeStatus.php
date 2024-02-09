<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "edge_status".
 *
 * @property int $id
 * @property int $edge_id
 * @property int $status_id
 * @property string $date_time
 *
 * @property Edge $edge
 * @property Status $status
 */
class EdgeStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'edge_status';
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
            [['edge_id', 'status_id', 'date_time'], 'required'],
            [['edge_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['edge_id', 'date_time'], 'unique', 'targetAttribute' => ['edge_id', 'date_time']],
            [['edge_id'], 'exist', 'skipOnError' => true, 'targetClass' => Edge::className(), 'targetAttribute' => ['edge_id' => 'id']],
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
            'edge_id' => 'Edge ID',
            'status_id' => 'Status ID',
            'date_time' => 'Date Time',
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
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
