<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "config_amicum".
 *
 * @property int $id
 * @property string $parameter
 * @property string $value
 * @property int $on_off
 */
class ConfigAmicum extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'config_amicum';
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
            [['parameter', 'value', 'on_off'], 'required'],
            [['on_off'], 'integer'],
            [['parameter', 'value'], 'string', 'max' => 255],
            [['parameter'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parameter' => 'Parameter',
            'value' => 'Value',
            'on_off' => 'On Off',
        ];
    }
}
