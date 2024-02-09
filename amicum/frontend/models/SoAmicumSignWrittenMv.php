<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "so_amicum_sign_written_mv".
 *
 * @property int $sign_written
 * @property string $name_written
 */
class SoAmicumSignWrittenMv extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'so_amicum_sign_written_mv';
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
            [['name_written'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'sign_written' => 'Sign Written',
            'name_written' => 'Name Written',
        ];
    }
}
