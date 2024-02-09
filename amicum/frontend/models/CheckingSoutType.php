<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "checking_sout_type".
 *
 * @property int $id
 * @property string $title Наименование типа проверки СОУТ
 *
 * @property Sout[] $souts
 */
class CheckingSoutType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'checking_sout_type';
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
            'title' => 'Наименование типа проверки СОУТ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSouts()
    {
        return $this->hasMany(Sout::className(), ['sout_type_id' => 'id']);
    }
}
