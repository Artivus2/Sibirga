<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_object_specific".
 *
 * @property string $main_id
 * @property int $object_id
 * @property string $title
 * @property string $table_address
 * @property int $object_type_id
 */
class ViewObjectSpecific extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_object_specific';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['main_id'], 'string'],
            [['object_id', 'object_type_id'], 'integer'],
            [['title', 'object_type_id'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['table_address'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'main_id' => 'Main ID',
            'object_id' => 'Object ID',
            'title' => 'Title',
            'table_address' => 'Table Address',
            'object_type_id' => 'Object Type ID',
        ];
    }
}
