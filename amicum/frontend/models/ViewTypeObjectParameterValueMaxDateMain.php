<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_type_object_parameter_value_maxDate_main".
 *
 * @property int $parameter_id
 * @property int $parameter_type_id
 * @property int $object_id
 * @property string $date_time DATETIME(3)DATETIME(6)f
 * @property string $value
 */
class ViewTypeObjectParameterValueMaxDateMain extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_type_object_parameter_value_maxDate_main';
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
            [['parameter_id', 'parameter_type_id', 'object_id', 'value'], 'required'],
            [['parameter_id', 'parameter_type_id', 'object_id'], 'integer'],
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
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
            'object_id' => 'Object ID',
            'date_time' => 'Date Time',
            'value' => 'Value',
        ];
    }
}
