<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "type_check_knowledge".
 *
 * @property int $id идентификатор типа проверки знаний
 * @property string $title Наименование проверки 
 *
 * @property CheckKnowledge[] $checkKnowledges
 */
class TypeCheckKnowledge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_check_knowledge';
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
            'id' => 'идентификатор типа проверки знаний',
            'title' => 'Наименование проверки ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCheckKnowledges()
    {
        return $this->hasMany(CheckKnowledge::className(), ['type_check_knowledge_id' => 'id']);
    }
}
