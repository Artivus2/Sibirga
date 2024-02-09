<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_object_parameter_handbook_value".
 *
 * @property int $id
 * @property int $type_object_parameter_id
 * @property string $date_time DATETIME(3)DATETIME(6)fff
 * @property string $value
 * @property int $status_id
 */
class TypeObjectParameterHandbookValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_object_parameter_handbook_value';
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
            [['type_object_parameter_id', 'date_time', 'value', 'status_id'], 'required'],
            [['type_object_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['type_object_parameter_id', 'date_time'], 'unique', 'targetAttribute' => ['type_object_parameter_id', 'date_time']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type_object_parameter_id' => 'Type Object Parameter ID',
            'date_time' => 'DATETIME(3)DATETIME(6)fff',
            'value' => 'Value',
            'status_id' => 'Status ID',
        ];
    }
}
