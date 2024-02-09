<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "fire_fighting_equipment_specific".
 *
 * @property int $id Идентификатор таблицы(автоинкрементный)
 * @property int $fire_fighting_object_id внешний идентификатор средства пожарной безопасности
 * @property string $inventory_number Инвентарный номер оборудования
 * @property double $wear_period Срок службы (лет)
 * @property string $date_issue Дата ввода в эксплутацию
 * @property string $date_write_off дата списания средства пожарной безопасности
 * @property string $description Примечание
 * @property int $status_id Статус спецификации средства пожарной безопасности
 *
 * @property FireFightingEquipmentDocuments $fireFightingEquipmentDocuments
 * @property FireFightingObject $fireFightingObject
 * @property Status $status
 * @property FireFightingEquipmentSpecificStatus[] $fireFightingEquipmentSpecificStatuses
 */
class FireFightingEquipmentSpecific extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fire_fighting_equipment_specific';
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
            [['fire_fighting_object_id', 'inventory_number', 'date_issue', 'status_id'], 'required'],
            [['fire_fighting_object_id', 'status_id'], 'integer'],
            [['wear_period'], 'number'],
            [['date_issue', 'date_write_off'], 'safe'],
            [['inventory_number', 'description'], 'string', 'max' => 255],
            [['fire_fighting_object_id'], 'exist', 'skipOnError' => true, 'targetClass' => FireFightingObject::className(), 'targetAttribute' => ['fire_fighting_object_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы(автоинкрементный)',
            'fire_fighting_object_id' => 'внешний идентификатор средства пожарной безопасности',
            'inventory_number' => 'Инвентарный номер оборудования',
            'wear_period' => 'Срок службы (лет)',
            'date_issue' => 'Дата ввода в эксплутацию',
            'date_write_off' => 'дата списания средства пожарной безопасности',
            'description' => 'Примечание',
            'status_id' => 'Статус спецификации средства пожарной безопасности',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFireFightingEquipmentDocuments()
    {
        return $this->hasMany(FireFightingEquipmentDocuments::className(), ['fire_fighting_equipment_specific_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFireFightingObject()
    {
        return $this->hasOne(FireFightingObject::className(), ['id' => 'fire_fighting_object_id']);
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
    public function getFireFightingEquipmentSpecificStatuses()
    {
        return $this->hasMany(FireFightingEquipmentSpecificStatus::className(), ['fire_fighting_equipment_specific_id' => 'id']);
    }
}
