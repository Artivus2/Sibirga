<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "document_with_ecp".
 *
 * @property int $id
 * @property int $document_id
 * @property resource $signed_data
 * @property resource $signature
 *
 * @property Document $document
 */
class DocumentWithEcp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'document_with_ecp';
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
            [['document_id', 'signed_data', 'signature'], 'required'],
            [['document_id'], 'integer'],
            [['signed_data', 'signature'], 'string'],
            [['document_id'], 'exist', 'skipOnError' => true, 'targetClass' => Document::className(), 'targetAttribute' => ['document_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'document_id' => 'Document ID',
            'signed_data' => 'Signed Data',
            'signature' => 'Signature',
        ];
    }

    /**
     * Gets query for [[Document]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDocument()
    {
        return $this->hasOne(Document::className(), ['id' => 'document_id']);
    }
}
