<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "planogramm_operation".
 *
 * @property int $id
 * @property string $date_time_start Дата и время начала операции в планограмме
 * @property string $date_time_end Дата и время окончания операции в планограмме
 * @property int $type_operation_id Внешний ключ операций
 * @property int $planogramma_id Внешний ключ планограммы
 *
 * @property Planogramma $planogramma
 * @property TypeOperation $typeOperation
 */
class PlanogrammOperation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'planogramm_operation';
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
            [['date_time_start', 'date_time_end', 'type_operation_id', 'planogramma_id'], 'required'],
            [['date_time_start', 'date_time_end'], 'safe'],
            [['type_operation_id', 'planogramma_id'], 'integer'],
            [['planogramma_id'], 'exist', 'skipOnError' => true, 'targetClass' => Planogramma::className(), 'targetAttribute' => ['planogramma_id' => 'id']],
            [['type_operation_id'], 'exist', 'skipOnError' => true, 'targetClass' => TypeOperation::className(), 'targetAttribute' => ['type_operation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_time_start' => 'Дата и время начала операции в планограмме',
            'date_time_end' => 'Дата и время окончания операции в планограмме',
            'type_operation_id' => 'Внешний ключ операций',
            'planogramma_id' => 'Внешний ключ планограммы',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlanogramma()
    {
        return $this->hasOne(Planogramma::className(), ['id' => 'planogramma_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTypeOperation()
    {
        return $this->hasOne(TypeOperation::className(), ['id' => 'type_operation_id']);
    }
}
