<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "nomenclature".
 *
 * @property int $id ключ номенклатуры
 * @property string $title наименование номенклатуры
 *
 * @property Material $material
 * @property Storage[] $storages
 */
class Nomenclature extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'nomenclature';
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
    public function getMaterial()
    {
        return $this->hasOne(Material::className(), ['nomenclature_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStorages()
    {
        return $this->hasMany(Storage::className(), ['nomenclature_id' => 'id']);
    }
}
