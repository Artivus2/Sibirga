<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "fire_fighting_equipment_specific_status".
 *
 * @property int $id Идентификатор статусов пожарной безопасности
 * @property int $fire_fighting_equipment_specific_id Внешний идентификатор спецификации средства пожарной безопасности
 * @property int $status_id
 * @property string $date_time Дата и время смены статуса
 *
 * @property FireFightingEquipmentSpecific $fireFightingEquipmentSpecific
 * @property Status $status
 */
class FireFightingEquipmentSpecificStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fire_fighting_equipment_specific_status';
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
            [['fire_fighting_equipment_specific_id', 'status_id', 'date_time'], 'required'],
            [['fire_fighting_equipment_specific_id', 'status_id'], 'integer'],
            [['date_time'], 'safe'],
            [['fire_fighting_equipment_specific_id'], 'exist', 'skipOnError' => true, 'targetClass' => FireFightingEquipmentSpecific::className(), 'targetAttribute' => ['fire_fighting_equipment_specific_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор статусов пожарной безопасности',
            'fire_fighting_equipment_specific_id' => 'Внешний идентификатор спецификации средства пожарной безопасности',
            'status_id' => 'Status ID',
            'date_time' => 'Дата и время смены статуса',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFireFightingEquipmentSpecific()
    {
        return $this->hasOne(FireFightingEquipmentSpecific::className(), ['id' => 'fire_fighting_equipment_specific_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }
}
