<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_repair".
 *
 * @property int $id Идентификатор самой таблицы (автоинкрементный)\\\\n
 * @property string $title Наименование вида ремонта 
 *
 * @property RepairMapSpecific[] $repairMapSpecifics
 * @property RepairMapTypical[] $repairMapTypicals
 */
class KindRepair extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_repair';
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
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор самой таблицы (автоинкрементный)\\\\\\\\n',
            'title' => 'Наименование вида ремонта ',
        ];
    }

    /**
     * Gets query for [[RepairMapSpecifics]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapSpecifics()
    {
        return $this->hasMany(RepairMapSpecific::className(), ['kind_repair_id' => 'id']);
    }

    /**
     * Gets query for [[RepairMapTypicals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRepairMapTypicals()
    {
        return $this->hasMany(RepairMapTypical::className(), ['kind_repair_id' => 'id']);
    }
}
