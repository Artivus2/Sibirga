<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "Settings_DCS".
 *
 * @property int $id
 * @property string $title
 *
 * @property DCSPulIp[] $dCSPulIps
 * @property ConnectString[] $connectStrings
 */
class SettingsDCS extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
            return 'Settings_DCS';
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
            [['title'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDCSPulIps()
    {
        return $this->hasMany(DCSPulIp::className(), ['DCS_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConnectStrings()
    {
        return $this->hasMany(ConnectString::className(), ['Settings_DCS_id' => 'id']);
    }
}
