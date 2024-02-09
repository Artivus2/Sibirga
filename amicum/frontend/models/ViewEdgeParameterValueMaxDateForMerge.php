<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_edge_parameter_value_maxDate_for_merge".
 *
 * @property int $edge_parameter_id
 * @property int $edge_id
 * @property string $date_time
 * @property string $value
 * @property int $status_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 */
class ViewEdgeParameterValueMaxDateForMerge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_edge_parameter_value_maxDate_for_merge';
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
            [['edge_parameter_id', 'edge_id', 'status_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['edge_id', 'date_time', 'value', 'status_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'edge_parameter_id' => 'Edge Parameter ID',
            'edge_id' => 'Edge ID',
            'date_time' => 'Date Time',
            'value' => 'Value',
            'status_id' => 'Status ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }
}
