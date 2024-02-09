<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "xml_send_type".
 *
 * @property int $id
 * @property string $title
 *
 * @property XmlConfig[] $xmlConfigs
 */
class XmlSendType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'xml_send_type';
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
            [['title'], 'string', 'max' => 120],
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
        return $this->hasMany(XmlConfig::className(), ['xml_send_type_id' => 'id']);
    }
}
