<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "cyclegramm".
 *
 * @property int $id Идентификатор таблицы
 * @property int $order_id Внешний ключ к таблице нарядов
 * @property string $date_time_start Дата и время начала работы в циклограмме
 * @property string $date_time_end Дата и время окончания работы в циклограмме
 * @property int $max_section Максимальное количество секций
 * @property int $cyclegramm_type_id Тип циклограммы (плановая, внепланновая)
 * @property int $chane_id Внешний идентификатор звена
 * @property int $section_start Начало секции крепи
 * @property int $section_end Конец секции крепи
 * @property int $equipment_id Внешний идентификатор оборудования
 *
 * @property Chane $chane
 * @property Equipment $equipment
 * @property Order $order
 * @property CyclegrammType $cyclegrammType
 * @property CyclegrammOperation[] $cyclegrammOperations
 */
class Cyclegramm extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cyclegramm';
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
            [['order_id', 'date_time_start', 'date_time_end', 'max_section', 'cyclegramm_type_id', 'chane_id', 'equipment_id'], 'required'],
            [['order_id', 'max_section', 'cyclegramm_type_id', 'chane_id', 'section_start', 'section_end', 'equipment_id'], 'integer'],
            [['date_time_start', 'date_time_end'], 'safe'],
            [['chane_id'], 'exist', 'skipOnError' => true, 'targetClass' => Chane::className(), 'targetAttribute' => ['chane_id' => 'id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
            [['cyclegramm_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => CyclegrammType::className(), 'targetAttribute' => ['cyclegramm_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы',
            'order_id' => 'Внешний ключ к таблице нарядов',
            'date_time_start' => 'Дата и время начала работы в циклограмме',
            'date_time_end' => 'Дата и время окончания работы в циклограмме',
            'max_section' => 'Максимальное количество секций',
            'cyclegramm_type_id' => 'Тип циклограммы (плановая, внепланновая)',
            'chane_id' => 'Внешний идентификатор звена',
            'section_start' => 'Начало секции крепи',
            'section_end' => 'Конец секции крепи',
            'equipment_id' => 'Внешний идентификатор оборудования',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChane()
    {
        return $this->hasOne(Chane::className(), ['id' => 'chane_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCyclegrammType()
    {
        return $this->hasOne(CyclegrammType::className(), ['id' => 'cyclegramm_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCyclegrammOperations()
    {
        return $this->hasMany(CyclegrammOperation::className(), ['cyclegramm_id' => 'id']);
    }
}
