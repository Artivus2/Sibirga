<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_mishap".
 *
 * @property int $id
 * @property string $title наименование вида несчастного случая
 */
class KindMishap extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_mishap';
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
            'title' => 'наименование вида несчастного случая',
        ];
    }
}
