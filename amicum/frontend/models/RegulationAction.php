<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "regulation_action".
 *
 * @property int $id ключ действия
 * @property int $regulation_id
 * @property int|null $action_parent_end_flag флаг первого последнего действия в регламенте 1 первое действие 2 последние действие 0 нет или не задано это обычное действие
 * @property int|null $regulation_exchange_id переход между действиями разных регламентов
 * @property string|null $title название действия
 * @property int|null $action_number порядковый номер действия (либо уровень действия)
 * @property string|null $action_type тип действия (positive - действие, кт было выполнено вовремя; negative - просроченное действие)
 * @property float|null $x координата абсциссы карточки действия (пока не понадобилось свойство)
 * @property float|null $y координата ординаты карточки действия (пока не понадобилось свойство)
 * @property int|null $responsible_position_id должность ответственного лица 
 * @property float|null $regulation_time регламентное время выполнения действия
 * @property int|null $expired_indicator_flag флаг установки индикатора просрочки действия
 * @property string|null $expired_indicator_mode тип действия просрочки (auto - автоматическое действие, manual - ручное)
 * @property string|null $finish_flag_mode  тип действия завершения (auto - автоматическое действие, manual - ручное)
 * @property int|null $parent_id родительское действие в регламенте
 * @property int|null $go_to_another_regulation_flag флаг перехода к другому регламенту
 * @property string|null $go_to_another_regulation_mode  тип действия перехода к другому регламенту
 * @property int|null $plan_new_action_flag флаг планирования нового действия
 * @property int|null $child_action_id_negative негативное действие
 * @property int|null $child_action_id_positive позитивное действие
 *
 * @property ActionOperation[] $actionOperations
 * @property OperationRegulationParameter[] $operationRegulationParameters
 * @property Regulation $regulation
 */
class RegulationAction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'regulation_action';
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
            [['regulation_id'], 'required'],
            [['regulation_id', 'action_parent_end_flag', 'regulation_exchange_id', 'action_number', 'responsible_position_id', 'expired_indicator_flag', 'parent_id', 'go_to_another_regulation_flag', 'plan_new_action_flag', 'child_action_id_negative', 'child_action_id_positive'], 'integer'],
            [['x', 'y', 'regulation_time'], 'number'],
            [['title'], 'string', 'max' => 500],
            [['action_type'], 'string', 'max' => 8],
            [['expired_indicator_mode', 'finish_flag_mode', 'go_to_another_regulation_mode'], 'string', 'max' => 6],
            [['regulation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Regulation::className(), 'targetAttribute' => ['regulation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'regulation_id' => 'Regulation ID',
            'action_parent_end_flag' => 'Action Parent End Flag',
            'regulation_exchange_id' => 'Regulation Exchange ID',
            'title' => 'Title',
            'action_number' => 'Action Number',
            'action_type' => 'Action Type',
            'x' => 'X',
            'y' => 'Y',
            'responsible_position_id' => 'Responsible Position ID',
            'regulation_time' => 'Regulation Time',
            'expired_indicator_flag' => 'Expired Indicator Flag',
            'expired_indicator_mode' => 'Expired Indicator Mode',
            'finish_flag_mode' => 'Finish Flag Mode',
            'parent_id' => 'Parent ID',
            'go_to_another_regulation_flag' => 'Go To Another Regulation Flag',
            'go_to_another_regulation_mode' => 'Go To Another Regulation Mode',
            'plan_new_action_flag' => 'Plan New Action Flag',
            'child_action_id_negative' => 'Child Action Id Negative',
            'child_action_id_positive' => 'Child Action Id Positive',
        ];
    }

    /**
     * Gets query for [[ActionOperations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getActionOperations()
    {
        return $this->hasMany(ActionOperation::className(), ['regulation_action_id' => 'id']);
    }

    /**
     * Gets query for [[OperationRegulationParameters]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperationRegulationParameters()
    {
        return $this->hasMany(OperationRegulationParameter::className(), ['operation_regulation_id' => 'id']);
    }

    /**
     * Gets query for [[Regulation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegulation()
    {
        return $this->hasOne(Regulation::className(), ['id' => 'regulation_id']);
    }
}
