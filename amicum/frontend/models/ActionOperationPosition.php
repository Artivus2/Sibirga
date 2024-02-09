<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "action_operation_position".
 *
 * @property int $id
 * @property int $position_id
 * @property int $action_operation_id
 * @property int|null $company_department_id ключ подразделения
 * @property int $on_shift оповещать работника на смене или первого на участке с такой должностью
 *
 * @property CompanyDepartment $companyDepartment
 * @property Position $position
 * @property ActionOperation $actionOperation
 */
class ActionOperationPosition extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'action_operation_position';
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
            [['position_id', 'action_operation_id'], 'required'],
            [['position_id', 'action_operation_id', 'company_department_id', 'on_shift'], 'integer'],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Position::className(), 'targetAttribute' => ['position_id' => 'id']],
            [['action_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => ActionOperation::className(), 'targetAttribute' => ['action_operation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'position_id' => 'Position ID',
            'action_operation_id' => 'Action Operation ID',
            'company_department_id' => 'Company Department ID',
            'on_shift' => 'On Shift',
        ];
    }

    /**
     * Gets query for [[CompanyDepartment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * Gets query for [[Position]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPosition()
    {
        return $this->hasOne(Position::className(), ['id' => 'position_id']);
    }

    /**
     * Gets query for [[ActionOperation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getActionOperation()
    {
        return $this->hasOne(ActionOperation::className(), ['id' => 'action_operation_id']);
    }
}
