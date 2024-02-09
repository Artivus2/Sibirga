<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "kind_fire_prevention_instruction".
 *
 * @property int $id идентификатор противопожарного инструктажа
 * @property string $title Наименование противопожарного инструктажа
 */
class KindFirePreventionInstruction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind_fire_prevention_instruction';
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
            'id' => 'идентификатор противопожарного инструктажа',
            'title' => 'Наименование противопожарного инструктажа',
        ];
    }
}
