<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "edge_parameter".
 *
 * @property int $id
 * @property int $edge_id
 * @property int $parameter_id
 * @property int $parameter_type_id
 *
 * @property Edge $edge
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property EdgeParameterHandbookValue[] $edgeParameterHandbookValues
 * @property EdgeParameterValue[] $edgeParameterValues
 */
class EdgeParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'edge_parameter';
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
            [['edge_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['edge_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['parameter_id', 'parameter_type_id', 'edge_id'], 'unique', 'targetAttribute' => ['parameter_id', 'parameter_type_id', 'edge_id']],
            [['edge_id'], 'exist', 'skipOnError' => true, 'targetClass' => Edge::className(), 'targetAttribute' => ['edge_id' => 'id']],
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
            'edge_id' => 'Edge ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * Gets query for [[Edge]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEdge()
    {
        return $this->hasOne(Edge::className(), ['id' => 'edge_id']);
    }

    /**
     * Gets query for [[Parameter]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParameter()
    {
        return $this->hasOne(Parameter::className(), ['id' => 'parameter_id']);
    }

    /**
     * Gets query for [[ParameterType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParameterType()
    {
        return $this->hasOne(ParameterType::className(), ['id' => 'parameter_type_id']);
    }

    /**
     * Gets query for [[EdgeParameterHandbookValues]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeParameterHandbookValues()
    {
        return $this->hasMany(EdgeParameterHandbookValue::className(), ['edge_parameter_id' => 'id']);
    }

    /**
     * Gets query for [[EdgeParameterValues]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEdgeParameterValues()
    {
        return $this->hasMany(EdgeParameterValue::className(), ['edge_parameter_id' => 'id']);
    }
}
