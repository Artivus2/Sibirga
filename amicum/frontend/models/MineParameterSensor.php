<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "mine_parameter_sensor".
 *
 * @property int $id
 * @property int $mine_parameter_id
 * @property int $sensor_id
 * @property string $date_time
 *
 * @property MineParameter $mineParameter
 */
class MineParameterSensor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mine_parameter_sensor';
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
            [['mine_parameter_id', 'sensor_id', 'date_time'], 'required'],
            [['mine_parameter_id', 'sensor_id'], 'integer'],
            [['date_time'], 'safe'],
            [['mine_parameter_id', 'sensor_id', 'date_time'], 'unique', 'targetAttribute' => ['mine_parameter_id', 'sensor_id', 'date_time']],
            [['mine_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => MineParameter::className(), 'targetAttribute' => ['mine_parameter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mine_parameter_id' => 'Mine Parameter ID',
            'sensor_id' => 'Sensor ID',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineParameter()
    {
        return $this->hasOne(MineParameter::className(), ['id' => 'mine_parameter_id']);
    }
}
