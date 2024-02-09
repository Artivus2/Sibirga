<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "face_function".
 *
 * @property int $id
 * @property int $face_id
 * @property int $function_id
 *
 * @property Face $face
 * @property Func $function
 */
class FaceFunction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'face_function';
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
            [['face_id', 'function_id'], 'required'],
            [['face_id', 'function_id'], 'integer'],
            [['face_id'], 'exist', 'skipOnError' => true, 'targetClass' => Face::className(), 'targetAttribute' => ['face_id' => 'id']],
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
            'face_id' => 'Face ID',
            'function_id' => 'Function ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFace()
    {
        return $this->hasOne(Face::className(), ['id' => 'face_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFunction()
    {
        return $this->hasOne(Func::className(), ['id' => 'function_id']);
    }
}
