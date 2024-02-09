<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "check_protocol".
 *
 * @property int $id
 * @property int $attachment_id Внешний идентификатор документа который относиться к проверке знаний
 * @property int $check_knowledge_id Внешний идентификатор проверки знаний
 *
 * @property Attachment $attachment
 * @property CheckKnowledge $checkKnowledge
 */
class CheckProtocol extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'check_protocol';
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
            [['attachment_id', 'check_knowledge_id'], 'required'],
            [['attachment_id', 'check_knowledge_id'], 'integer'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::className(), 'targetAttribute' => ['attachment_id' => 'id']],
            [['check_knowledge_id'], 'exist', 'skipOnError' => true, 'targetClass' => CheckKnowledge::className(), 'targetAttribute' => ['check_knowledge_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'attachment_id' => 'Внешний идентификатор документа который относиться к проверке знаний',
            'check_knowledge_id' => 'Внешний идентификатор проверки знаний',
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
    public function getCheckKnowledge()
    {
        return $this->hasOne(CheckKnowledge::className(), ['id' => 'check_knowledge_id']);
    }
}
