<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "plast_function".
 *
 * @property int $id
 * @property int $plast_id
 * @property int $function_id
 *
 * @property Func $function
 * @property Plast $plast
 */
class PlastFunction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'plast_function';
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
            [['plast_id', 'function_id'], 'required'],
            [['plast_id', 'function_id'], 'integer'],
            [['function_id'], 'exist', 'skipOnError' => true, 'targetClass' => Func::className(), 'targetAttribute' => ['function_id' => 'id']],
            [['plast_id'], 'exist', 'skipOnError' => true, 'targetClass' => Plast::className(), 'targetAttribute' => ['plast_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'plast_id' => 'Plast ID',
            'function_id' => 'Function ID',
        ];
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
    public function getPlast()
    {
        return $this->hasOne(Plast::className(), ['id' => 'plast_id']);
    }
}
