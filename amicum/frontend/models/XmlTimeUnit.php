<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "xml_time_unit".
 *
 * @property int $id
 * @property string $title
 *
 * @property XmlConfig[] $xmlConfigs
 */
class XmlTimeUnit extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'xml_time_unit';
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
            [['id', 'title'], 'required'],
            [['id'], 'integer'],
            [['title'], 'string', 'max' => 45],
            [['id'], 'unique'],
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
    public function getXmlConfigs()
    {
        return $this->hasMany(XmlConfig::className(), ['time_unit_id' => 'id']);
    }
}
