<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "conjunction_function".
 *
 * @property int $id
 * @property int $conjunction_id
 * @property int $function_id
 *
 * @property Conjunction $conjunction
 * @property Func $function
 */
class ConjunctionFunction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conjunction_function';
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
            [['conjunction_id', 'function_id'], 'integer'],
            [['conjunction_id'], 'exist', 'skipOnError' => true, 'targetClass' => Conjunction::className(), 'targetAttribute' => ['conjunction_id' => 'id']],
            [['function_id'], 'exist', 'skipOnError' => true, 'targetClass' => Func::className(), 'targetAttribute' => ['function_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'conjunction_id' => 'Conjunction ID',
            'function_id' => 'Function ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConjunction()
    {
        return $this->hasOne(Conjunction::className(), ['id' => 'conjunction_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFunction()
    {
        return $this->hasOne(Func::className(), ['id' => 'function_id']);
    }
}
