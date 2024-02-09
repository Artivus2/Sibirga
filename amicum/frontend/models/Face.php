<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "face".
 *
 * @property int $id
 * @property string $title
 * @property string $date_time
 * @property string $description
 * @property int $object_id
 *
 * @property ConfigurationFace[] $configurationFaces
 * @property DowntimeDependencyFace[] $downtimeDependencyFaces
 * @property Object $object
 * @property FaceFunction[] $faceFunctions
 * @property FaceParameter[] $faceParameters
 * @property OrderByChane[] $orderByChanes
 */
class Face extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'face';
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
            [['id', 'title', 'date_time', 'description', 'object_id'], 'required'],
            [['id', 'object_id'], 'integer'],
            [['date_time'], 'safe'],
            [['description'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['id'], 'unique'],
            [['object_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypicalObject::className(), 'targetAttribute' => ['object_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'date_time' => 'Date Time',
            'description' => 'Description',
            'object_id' => 'Object ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigurationFaces()
    {
        return $this->hasMany(ConfigurationFace::className(), ['face_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDowntimeDependencyFaces()
    {
        return $this->hasMany(DowntimeDependencyFace::className(), ['face_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObject()
    {
        return $this->hasOne(TypicalObject::className(), ['id' => 'object_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFaceFunctions()
    {
        return $this->hasMany(FaceFunction::className(), ['face_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFaceParameters()
    {
        return $this->hasMany(FaceParameter::className(), ['face_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderByChanes()
    {
        return $this->hasMany(OrderByChane::className(), ['face_id' => 'id']);
    }
}
