<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "downtime_dependency_face".
 *
 * @property int $id Простои зависимых забоев
 * @property int $downtime_id Уникальный идентификатор простоя\\n
 * @property int $face_id Уникальный идентификатор забоя
 *
 * @property Downtime $downtime
 * @property Face $face
 */
class DowntimeDependencyFace extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'downtime_dependency_face';
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
            [['downtime_id', 'face_id'], 'required'],
            [['downtime_id', 'face_id'], 'integer'],
            [['downtime_id'], 'exist', 'skipOnError' => true, 'targetClass' => Downtime::className(), 'targetAttribute' => ['downtime_id' => 'id']],
            [['face_id'], 'exist', 'skipOnError' => true, 'targetClass' => Face::className(), 'targetAttribute' => ['face_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Простои зависимых забоев',
            'downtime_id' => 'Уникальный идентификатор простоя\\\\n',
            'face_id' => 'Уникальный идентификатор забоя',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDowntime()
    {
        return $this->hasOne(Downtime::className(), ['id' => 'downtime_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFace()
    {
        return $this->hasOne(Face::className(), ['id' => 'face_id']);
    }
}
