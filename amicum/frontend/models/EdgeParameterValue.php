<?php

namespace frontend\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Connection;

/**
 * This is the model class for table "edge_parameter_value".
 *
 * @property int $id
 * @property int $edge_parameter_id
 * @property string $date_time
 * @property string $value
 * @property int $status_id
 *
 * @property Status $status
 * @property EdgeParameter $edgeParameter
 */
class EdgeParameterValue extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'edge_parameter_value';
    }

    /**
     * @return Connection the database connection used by this AR class.
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
            [['edge_parameter_id', 'date_time', 'value', 'status_id'], 'required'],
            [['edge_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['edge_parameter_id', 'date_time'], 'unique', 'targetAttribute' => ['edge_parameter_id', 'date_time']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['edge_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => EdgeParameter::className(), 'targetAttribute' => ['edge_parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'edge_parameter_id' => 'Edge Parameter ID',
            'date_time' => 'Date Time',
            'value' => 'Value',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * Gets query for [[Status]].
     *
     * @return ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * Gets query for [[EdgeParameter]].
     *
     * @return ActiveQuery
     */
    public function getEdgeParameter()
    {
        return $this->hasOne(EdgeParameter::className(), ['id' => 'edge_parameter_id']);
    }
}
