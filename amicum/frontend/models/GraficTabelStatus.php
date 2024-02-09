<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "grafic_tabel_status".
 *
 * @property int $id Ключ таблицы статуса наряда
 * @property int $grafic_tabel_main_id внешний ключ списка нарядов
 * @property int $worker_object_id внешний ключ работника установивший данный статус
 * @property int $status_id внешний ключ справочника статусов
 *
 * @property Status $status
 * @property GraficTabelMain $graficTabelMain
 */
class GraficTabelStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'grafic_tabel_status';
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
            [['id', 'grafic_tabel_main_id', 'worker_object_id', 'status_id'], 'required'],
            [['id', 'grafic_tabel_main_id', 'worker_object_id', 'status_id'], 'integer'],
            [['id'], 'unique'],
            [['grafic_tabel_main_id', 'worker_object_id', 'status_id'], 'unique', 'targetAttribute' => ['grafic_tabel_main_id', 'worker_object_id', 'status_id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['grafic_tabel_main_id'], 'exist', 'skipOnError' => true, 'targetClass' => GraficTabelMain::className(), 'targetAttribute' => ['grafic_tabel_main_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Ключ таблицы статуса наряда',
            'grafic_tabel_main_id' => 'внешний ключ списка нарядов',
            'worker_object_id' => 'внешний ключ работника установивший данный статус',
            'status_id' => 'внешний ключ справочника статусов',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGraficTabelMain()
    {
        return $this->hasOne(GraficTabelMain::className(), ['id' => 'grafic_tabel_main_id']);
    }
}
