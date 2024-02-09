<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "cyclegramm_operation".
 *
 * @property int $id Идентификатор таблицы 
 * @property string $date_time_end Дата и время окончания операции в циклограмме
 * @property string $date_time_start Дата и время начала операции в циклограмме
 * @property int $type_operation_id Внешний ключ к таблице операций
 * @property int $section_start Секция крепи начала операции
 * @property int $section_end Секция крепи окончания операции
 * @property int $cyclegramm_id
 *
 * @property Cyclegramm $cyclegramm
 * @property TypeOperation $typeOperation
 */
class CyclegrammOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cyclegramm_operation';
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
            [['date_time_end', 'date_time_start', 'type_operation_id', 'section_start', 'section_end', 'cyclegramm_id'], 'required'],
            [['date_time_end', 'date_time_start'], 'safe'],
            [['type_operation_id', 'section_start', 'section_end', 'cyclegramm_id'], 'integer'],
            [['date_time_end', 'date_time_start', 'type_operation_id', 'cyclegramm_id'], 'unique', 'targetAttribute' => ['date_time_end', 'date_time_start', 'type_operation_id', 'cyclegramm_id']],
            [['cyclegramm_id'], 'exist', 'skipOnError' => true, 'targetClass' => Cyclegramm::className(), 'targetAttribute' => ['cyclegramm_id' => 'id']],
            [['type_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypeOperation::className(), 'targetAttribute' => ['type_operation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы ',
            'date_time_end' => 'Дата и время окончания операции в циклограмме',
            'date_time_start' => 'Дата и время начала операции в циклограмме',
            'type_operation_id' => 'Внешний ключ к таблице операций',
            'section_start' => 'Секция крепи начала операции',
            'section_end' => 'Секция крепи окончания операции',
            'cyclegramm_id' => 'Cyclegramm ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCyclegramm()
    {
        return $this->hasOne(Cyclegramm::className(), ['id' => 'cyclegramm_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeOperation()
    {
        return $this->hasOne(TypeOperation::className(), ['id' => 'type_operation_id']);
    }

    /**
     * Написано в ручную
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id'])->via('cyclegramm');
    }
}
