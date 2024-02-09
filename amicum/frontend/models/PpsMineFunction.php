<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "pps_mine_function".
 *
 * @property int $id
 * @property int $pps_mine_id
 * @property int $function_id
 *
 * @property Func $function
 * @property PpsMine $ppsMine
 */
class PpsMineFunction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pps_mine_function';
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
            [['pps_mine_id', 'function_id'], 'required'],
            [['pps_mine_id', 'function_id'], 'integer'],
            [['function_id'], 'exist', 'skipOnError' => true, 'targetClass' => Func::className(), 'targetAttribute' => ['function_id' => 'id']],
            [['pps_mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => PpsMine::className(), 'targetAttribute' => ['pps_mine_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pps_mine_id' => 'Pps Mine ID',
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
    public function getPpsMine()
    {
        return $this->hasOne(PpsMine::className(), ['id' => 'pps_mine_id']);
    }
}
