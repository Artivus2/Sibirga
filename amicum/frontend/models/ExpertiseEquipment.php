<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "expertise_equipment".
 *
 * @property int $id
 * @property int $equipment_id Внешний идентификатор оборудования на котором была пройдена экспертиза
 * @property int $expertise_id Внешний идентификатор экспертизы
 *
 * @property Equipment $equipment
 * @property Expertise $expertise
 */
class ExpertiseEquipment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'expertise_equipment';
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
            [['equipment_id', 'expertise_id'], 'required'],
            [['equipment_id', 'expertise_id'], 'integer'],
            [['equipment_id', 'expertise_id'], 'unique', 'targetAttribute' => ['equipment_id', 'expertise_id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
            [['expertise_id'], 'exist', 'skipOnError' => true, 'targetClass' => Expertise::className(), 'targetAttribute' => ['expertise_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'equipment_id' => 'Внешний идентификатор оборудования на котором была пройдена экспертиза',
            'expertise_id' => 'Внешний идентификатор экспертизы',
        ];
    }

    /**
     * Gets query for [[Equipment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
    }

    /**
     * Gets query for [[Expertise]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExpertise()
    {
        return $this->hasOne(Expertise::className(), ['id' => 'expertise_id']);
    }
}
