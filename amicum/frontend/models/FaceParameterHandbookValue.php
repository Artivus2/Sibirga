<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "face_parameter_handbook_value".
 *
 * @property int $id
 * @property int $face_parameter_id
 * @property string $date_time DATETIME(3)DATETIME(6)
 * @property string $value
 * @property int $status_id
 *
 * @property FaceParameter $faceParameter
 * @property Status $status
 */
class FaceParameterHandbookValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'face_parameter_handbook_value';
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
            [['face_parameter_id', 'date_time', 'value', 'status_id'], 'required'],
            [['face_parameter_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['value'], 'string', 'max' => 255],
            [['face_parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => FaceParameter::className(), 'targetAttribute' => ['face_parameter_id' => 'id']],
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
            'face_parameter_id' => 'Face Parameter ID',
            'date_time' => 'DATETIME(3)DATETIME(6)',
            'value' => 'Value',
            'status_id' => 'Status ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFaceParameter()
    {
        return $this->hasOne(FaceParameter::className(), ['id' => 'face_parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
