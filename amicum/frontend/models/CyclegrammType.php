<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "cyclegramm_type".
 *
 * @property int $id
 * @property string $title Наименование типа циклограммы
 *
 * @property Cyclegramm[] $cyclegramms
 */
class CyclegrammType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cyclegramm_type';
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
            'title' => 'Наименование типа циклограммы',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCyclegramms()
    {
        return $this->hasMany(Cyclegramm::className(), ['cyclegramm_type_id' => 'id']);
    }
}
