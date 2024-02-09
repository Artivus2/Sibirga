<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "graphic_repair".
 *
 * @property int $id Идентификатор  графика ремонта, то есть идентификатор самой таблицы (автоинкрементный)
 * @property int $graphic_list_id Уникальный идентификатор графика из списка графиков
 * @property int $equipment_id Уникальный идентификатор оборудования
 * @property string $date_time_plan Дата и время планирования ремонта, то есть когда должно быть отремонтировано оборудование
 * @property int $repair_map_specific_equipment_section_id Уникальный идентификатор секции оборудования из списка секций оборудования в технологической карте конкретных объектов
 * @property int $brigade_id Уникальный идентификатор бригады (кому назначен ремонт оборудования)\\n
 * @property int $worker_id Уникальный идентификатор работника (кому назначен ремонт оборудования)\\n
 *
 * @property Brigade $brigade
 * @property Equipment $equipment
 * @property GraphicList $graphicList
 * @property Worker $worker
 */
class GraphicRepair extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'graphic_repair';
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
            [['graphic_list_id', 'equipment_id', 'date_time_plan', 'repair_map_specific_equipment_section_id'], 'required'],
            [['graphic_list_id', 'equipment_id', 'repair_map_specific_equipment_section_id', 'brigade_id', 'worker_id'], 'integer'],
            [['date_time_plan'], 'safe'],
            [['brigade_id'], 'exist', 'skipOnError' => true, 'targetClass' => Brigade::className(), 'targetAttribute' => ['brigade_id' => 'id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['graphic_list_id'], 'exist', 'skipOnError' => true, 'targetClass' => GraphicList::className(), 'targetAttribute' => ['graphic_list_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор  графика ремонта, то есть идентификатор самой таблицы (автоинкрементный)',
            'graphic_list_id' => 'Уникальный идентификатор графика из списка графиков',
            'equipment_id' => 'Уникальный идентификатор оборудования',
            'date_time_plan' => 'Дата и время планирования ремонта, то есть когда должно быть отремонтировано оборудование',
            'repair_map_specific_equipment_section_id' => 'Уникальный идентификатор секции оборудования из списка секций оборудования в технологической карте конкретных объектов',
            'brigade_id' => 'Уникальный идентификатор бригады (кому назначен ремонт оборудования)\\\\n',
            'worker_id' => 'Уникальный идентификатор работника (кому назначен ремонт оборудования)\\\\n',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrigade()
    {
        return $this->hasOne(Brigade::className(), ['id' => 'brigade_id']);
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
    public function getGraphicList()
    {
        return $this->hasOne(GraphicList::className(), ['id' => 'graphic_list_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
}
