<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "mine_parameter_value".
 *
 * @property int $id
 * @property int $mine_parameter_id
 * @property string $value
 * @property string $date_time DATETIME(3)
 * @property int $status_id
 *
 * @property MineParameter $mineParameter
 * @property Status $status
 */
class MineParameterValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mine_parameter_value';
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
            [['mine_parameter_id', 'value', 'date_time', 'status_id'], 'required'],
            [['mine_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['mine_parameter_id', 'value'], 'unique', 'targetAttribute' => ['mine_parameter_id', 'value']],
            [['mine_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => MineParameter::className(), 'targetAttribute' => ['mine_parameter_id' => 'id']],
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
            'mine_parameter_id' => 'Mine Parameter ID',
            'value' => 'Value',
            'date_time' => 'DATETIME(3)',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineParameter()
    {
        return $this->hasOne(MineParameter::className(), ['id' => 'mine_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
