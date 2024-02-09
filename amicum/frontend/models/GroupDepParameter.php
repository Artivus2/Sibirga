<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "group_dep_parameter".
 *
 * @property int $id Идентификатор таблицы
 * @property int $group_dep_id Внешний ключ к таблице бригад
 * @property int $parameter_id Внешний ключ к таблице параметров
 * @property int $parameter_type_id Внешний ключ к таблице типов параметров
 *
 * @property GroupDep $groupDep
 * @property Parameter $parameter
 * @property ParameterType $parameterType
 * @property GroupDepParameterCalcValue[] $groupDepParameterCalcValues
 * @property GroupDepParameterHandbookValue[] $groupDepParameterHandbookValues
 */
class GroupDepParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_dep_parameter';
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
            [['group_dep_id', 'parameter_id', 'parameter_type_id'], 'required'],
            [['group_dep_id', 'parameter_id', 'parameter_type_id'], 'integer'],
            [['group_dep_id', 'parameter_id', 'parameter_type_id'], 'unique', 'targetAttribute' => ['group_dep_id', 'parameter_id', 'parameter_type_id']],
            [['group_dep_id'], 'exist', 'skipOnError' => true, 'targetClass' => GroupDep::className(), 'targetAttribute' => ['group_dep_id' => 'id']],
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
            'group_dep_id' => 'Group Dep ID',
            'parameter_id' => 'Parameter ID',
            'parameter_type_id' => 'Parameter Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupDep()
    {
        return $this->hasOne(GroupDep::className(), ['id' => 'group_dep_id']);
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
    public function getGroupDepParameterCalcValues()
    {
        return $this->hasMany(GroupDepParameterCalcValue::className(), ['group_department_parameter_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupDepParameterHandbookValues()
    {
        return $this->hasMany(GroupDepParameterHandbookValue::className(), ['group_department_parameter_id' => 'id']);
    }
}
