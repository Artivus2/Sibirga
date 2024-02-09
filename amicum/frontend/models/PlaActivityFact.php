<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "pla_activity_fact".
 *
 * @property int $id
 * @property int $pla_fact_id
 * @property int $activity_id
 * @property string $duration
 * @property int $order_id
 * @property int $function_id
 * @property int $trigger_id
 *
 * @property MineSituationFactParameterValue[] $mineSituationFactParameterValues
 * @property Activity $activity
 * @property Func $function
 * @property PlaFact $plaFact
 * @property Trigger $trigger
 */
class PlaActivityFact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pla_activity_fact';
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
            [['pla_fact_id', 'activity_id', 'duration', 'order_id', 'function_id', 'trigger_id'], 'required'],
            [['pla_fact_id', 'activity_id', 'order_id', 'function_id', 'trigger_id'], 'integer'],
            [['duration'], 'safe'],
            [['activity_id'], 'exist', 'skipOnError' => true, 'targetClass' => Activity::className(), 'targetAttribute' => ['activity_id' => 'id']],
            [['function_id'], 'exist', 'skipOnError' => true, 'targetClass' => Func::className(), 'targetAttribute' => ['function_id' => 'id']],
            [['pla_fact_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlaFact::className(), 'targetAttribute' => ['pla_fact_id' => 'id']],
            [['trigger_id'], 'exist', 'skipOnError' => true, 'targetClass' => Trigger::className(), 'targetAttribute' => ['trigger_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pla_fact_id' => 'Pla Fact ID',
            'activity_id' => 'Activity ID',
            'duration' => 'Duration',
            'order_id' => 'Order ID',
            'function_id' => 'Function ID',
            'trigger_id' => 'Trigger ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMineSituationFactParameterValues()
    {
        return $this->hasMany(MineSituationFactParameterValue::className(), ['pla_activity_fact_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActivity()
    {
        return $this->hasOne(Activity::className(), ['id' => 'activity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFunction()
    {
        return $this->hasOne(Func::className(), ['id' => 'function_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaFact()
    {
        return $this->hasOne(PlaFact::className(), ['id' => 'pla_fact_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTrigger()
    {
        return $this->hasOne(Trigger::className(), ['id' => 'trigger_id']);
    }
}
