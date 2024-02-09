<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_object".
 *
 * @property int $id
 * @property string $title
 * @property string $kind_object_type Место/прочее
 * @property string $kind_object_ico
 *
 * @property ObjectType[] $objectTypes
 */
class KindObject extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_object';
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
            [['title', 'kind_object_type', 'kind_object_ico'], 'required'],
            [['title', 'kind_object_ico'], 'string', 'max' => 255],
            [['kind_object_type'], 'string', 'max' => 45],
            [['title'], 'unique'],
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
            'kind_object_type' => 'Место/прочее',
            'kind_object_ico' => 'Kind Object Ico',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getObjectTypes()
    {
        return $this->hasMany(ObjectType::className(), ['kind_object_id' => 'id']);
    }
}
