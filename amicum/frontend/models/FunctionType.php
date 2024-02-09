<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "function_type".
 *
 * @property int $id
 * @property string $title
 *
 * @property Func[] $funcs
 */
class FunctionType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'function_type';
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
            [['title'], 'string', 'max' => 4255],
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
    public function getFuncs()
    {
        return $this->hasMany(Func::className(), ['function_type_id' => 'id']);
    }
}
