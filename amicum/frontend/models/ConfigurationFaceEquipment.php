<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "configuration_face_equipment".
 *
 * @property int $id
 * @property int $configuration_face_id
 * @property int $equipment_id
 *
 * @property ConfigurationFace $configurationFace
 * @property Equipment $equipment
 */
class ConfigurationFaceEquipment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'configuration_face_equipment';
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
            [['configuration_face_id', 'equipment_id'], 'required'],
            [['configuration_face_id', 'equipment_id'], 'integer'],
            [['configuration_face_id'], 'exist', 'skipOnError' => true, 'targetClass' => ConfigurationFace::className(), 'targetAttribute' => ['configuration_face_id' => 'id']],
            [['equipment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Equipment::className(), 'targetAttribute' => ['equipment_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'configuration_face_id' => 'Configuration Face ID',
            'equipment_id' => 'Equipment ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigurationFace()
    {
        return $this->hasOne(ConfigurationFace::className(), ['id' => 'configuration_face_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEquipment()
    {
        return $this->hasOne(Equipment::className(), ['id' => 'equipment_id']);
    }
}
