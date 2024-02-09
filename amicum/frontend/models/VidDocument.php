<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "vid_document".
 *
 * @property int $id Идентификатор таблицы(автоинкрементный)
 * @property string $title Наименование вида документа
 *
 * @property Document[] $documents
 * @property DocumentEventPb[] $documentEventPbs
 */
class VidDocument extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'vid_document';
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
            [['title'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы(автоинкрементный)',
            'title' => 'Наименование вида документа',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocuments()
    {
        return $this->hasMany(Document::className(), ['vid_document_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentEventPbs()
    {
        return $this->hasMany(DocumentEventPb::className(), ['vid_document_id' => 'id']);
    }
}
