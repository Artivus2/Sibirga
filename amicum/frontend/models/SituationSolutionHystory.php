<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "situation_solution_hystory".
 *
 * @property int $id
 * @property int $situation_solution_id
 * @property string|null $situation_solution_json
 * @property string|null $date_time
 *
 * @property SituationSolution $situationSolution
 */
class SituationSolutionHystory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'situation_solution_hystory';
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
            [['situation_solution_id'], 'required'],
            [['situation_solution_id'], 'integer'],
            [['situation_solution_json', 'date_time'], 'safe'],
            [['situation_solution_id'], 'exist', 'skipOnError' => true, 'targetClass' => SituationSolution::className(), 'targetAttribute' => ['situation_solution_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'situation_solution_id' => 'Situation Solution ID',
            'situation_solution_json' => 'Situation Solution Json',
            'date_time' => 'Date Time',
        ];
    }

    /**
     * Gets query for [[SituationSolution]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSituationSolution()
    {
        return $this->hasOne(SituationSolution::className(), ['id' => 'situation_solution_id']);
    }
}
