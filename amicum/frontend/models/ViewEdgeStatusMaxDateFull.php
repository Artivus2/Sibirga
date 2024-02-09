<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_edge_status_maxDate_full".
 *
 * @property int $id
 * @property int $edge_id
 * @property int $status_id
 * @property string $date_time
 */
class ViewEdgeStatusMaxDateFull extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_edge_status_maxDate_full';
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
            [['id', 'edge_id', 'status_id'], 'integer'],
            [['edge_id', 'status_id', 'date_time'], 'required'],
            [['date_time'], 'safe'],
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
}
