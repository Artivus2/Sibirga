<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "face_parameter".
 *
 * @property int $id
 * @property int $face_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property Face $face
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property FaceParameterHandbookValue[] $faceParameterHandbookValues
 * @property FaceParameterValue[] $faceParameterValues
 * @property NominalFaceParameter[] $nominalFaceParameters
 */
class FaceParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'face_parameter';
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
            [['face_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['face_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['face_id'], 'exist', 'skipOnError' => true, 'targetClass' => Face::className(), 'targetAttribute' => ['face_id' => 'id']],
            [['parameter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Parameter::className(), 'targetAttribute' => ['parameter_id' => 'id']],
            [['parameter_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParameterType::className(), 'targetAttribute' => ['parameter_type_id' => 'id']],
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
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
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
    public function getParameter()
    {
        return $this->hasOne(Parameter::className(), ['id' => 'parameter_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParameterType()
    {
        return $this->hasOne(ParameterType::className(), ['id' => 'parameter_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFaceParameterHandbookValues()
    {
        return $this->hasMany(FaceParameterHandbookValue::className(), ['face_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFaceParameterValues()
    {
        return $this->hasMany(FaceParameterValue::className(), ['face_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNominalFaceParameters()
    {
        return $this->hasMany(NominalFaceParameter::className(), ['face_parameter_id' => 'id']);
    }
}
