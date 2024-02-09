<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_parameter".
 *
 * @property int $id
 * @property string $title
 *
 * @property Parameter[] $parameters
 */
class KindParameter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_parameter';
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
     * @return \yii\db\ActiveQuery
     */
    public function getParameters()
    {
        return $this->hasMany(Parameter::className(), ['kind_parameter_id' => 'id']);
    }
}
