<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "fire_fighting_equipment_documents".
 *
 * @property int $id Идентификатор связки средства пожарной безопасности и документа
 * @property int $fire_fighting_equipment_specific_id Внешний идентификатор средства пожарной безопасности
 * @property int $attachment_id Внешний идентификатор документа на средство пожарной безопасности
 *
 * @property Attachment $attachment
 * @property FireFightingEquipmentSpecific $fireFightingEquipmentSpecific
 */
class FireFightingEquipmentDocuments extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fire_fighting_equipment_documents';
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
            [['fire_fighting_equipment_specific_id', 'attachment_id'], 'required'],
            [['fire_fighting_equipment_specific_id', 'attachment_id'], 'integer'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['fire_fighting_equipment_specific_id'], 'exist', 'skipOnError' => true, 'targetClass' => FireFightingEquipmentSpecific::className(), 'targetAttribute' => ['fire_fighting_equipment_specific_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор связки средства пожарной безопасности и документа',
            'fire_fighting_equipment_specific_id' => 'Внешний идентификатор средства пожарной безопасности',
            'attachment_id' => 'Внешний идентификатор документа на средство пожарной безопасности',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::className(), ['id' => 'attachment_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFireFightingEquipmentSpecific()
    {
        return $this->hasOne(FireFightingEquipmentSpecific::className(), ['id' => 'fire_fighting_equipment_specific_id']);
    }
}
