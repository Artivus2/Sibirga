<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_equi_out".
 *
 * @property int $equipment_id номер оборудования
 * @property int|null $parent_equipment_id номер оборудования родителя
 * @property string $equipment_title название оборудования
 * @property string $EQTYP тип единицы оборудования
 * @property string|null $inventory_number инвентарный номер
 * @property string|null $ANLNR основной номер оборудования
 * @property string|null $ANLUN субномер номер оборудования
 * @property string|null $TPLNR код технического места
 * @property string|null $BUKRS балансовая единица
 * @property string|null $DATAB дата начал действия оборудования
 * @property string|null $DATBI дата окончания действия оборудования
 * @property string|null $INBDT дата ввода в эксплуатацию
 * @property string $DATE_MODIFIED дата изменения записи
 */
class SapEquiOut extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_equi_out';
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
            [['equipment_id', 'equipment_title', 'EQTYP', 'DATE_MODIFIED'], 'required'],
            [['equipment_id', 'parent_equipment_id'], 'integer'],
            [['DATAB', 'DATBI', 'INBDT', 'DATE_MODIFIED'], 'safe'],
            [['equipment_title', 'TPLNR'], 'string', 'max' => 40],
            [['EQTYP'], 'string', 'max' => 1],
            [['inventory_number'], 'string', 'max' => 25],
            [['ANLNR'], 'string', 'max' => 12],
            [['ANLUN', 'BUKRS'], 'string', 'max' => 4],
            [['equipment_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'equipment_id' => 'Equipment ID',
            'parent_equipment_id' => 'Parent Equipment ID',
            'equipment_title' => 'Equipment Title',
            'EQTYP' => 'Eqtyp',
            'inventory_number' => 'Inventory Number',
            'ANLNR' => 'Anlnr',
            'ANLUN' => 'Anlun',
            'TPLNR' => 'Tplnr',
            'BUKRS' => 'Bukrs',
            'DATAB' => 'Datab',
            'DATBI' => 'Datbi',
            'INBDT' => 'Inbdt',
            'DATE_MODIFIED' => 'Date Modified',
        ];
    }
}
