<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "unity_config".
 *
 * @property int $id
 * @property int $mine_id
 * @property string $json_unity_config
 */
class UnityConfig extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'unity_config';
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
            [['mine_id', 'json_unity_config'], 'required'],
            [['mine_id'], 'integer'],
            [['json_unity_config'], 'safe'],
            [['mine_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mine_id' => 'Mine ID',
            'json_unity_config' => 'Json Unity Config',
        ];
    }
}
