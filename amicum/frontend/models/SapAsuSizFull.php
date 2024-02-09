<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_asu_siz_full".
 *
 * @property int $n_nomencl
 * @property string $name_nomencl
 * @property int $unit_id
 * @property int $type_cost
 * @property string $name_cost
 * @property string $date_created
 * @property int $sign_winter
 * @property int $working_life
 */
class SapAsuSizFull extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_asu_siz_full';
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
            [['unit_id', 'type_cost', 'sign_winter', 'working_life'], 'integer'],
            [['date_created'], 'safe'],
            [['name_nomencl', 'name_cost'], 'string', 'max' => 128],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'n_nomencl' => 'N Nomencl',
            'name_nomencl' => 'Name Nomencl',
            'unit_id' => 'Unit ID',
            'type_cost' => 'Type Cost',
            'name_cost' => 'Name Cost',
            'date_created' => 'Date Created',
            'sign_winter' => 'Sign Winter',
            'working_life' => 'Working Life',
        ];
    }
}
