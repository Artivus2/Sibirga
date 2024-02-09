<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "research_index".
 *
 * @property int $id
 * @property string $title Наименование показателя исследования
 * @property int $research_type_id Тип показателя исследования
 *
 * @property ResearchType $researchType
 */
class ResearchIndex extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'research_index';
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
            [['title', 'research_type_id'], 'required'],
            [['research_type_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['research_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ResearchType::className(), 'targetAttribute' => ['research_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Наименование показателя исследования',
            'research_type_id' => 'Тип показателя исследования',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResearchType()
    {
        return $this->hasOne(ResearchType::className(), ['id' => 'research_type_id']);
    }
}
