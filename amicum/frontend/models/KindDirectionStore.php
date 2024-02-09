<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_direction_store".
 *
 * @property int $id
 * @property string|null $title наименование направления движения (1 - списание) (2 - приянтие)
 *
 * @property Storage[] $storages
 */
class KindDirectionStore extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_direction_store';
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
     * Gets query for [[Storages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStorages()
    {
        return $this->hasMany(Storage::className(), ['kind_direction_store_id' => 'id']);
    }
}
