<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "injunction_img".
 *
 * @property int $id
 * @property string $img_path Путь до фотографии
 * @property int $injunction_violation_id
 *
 * @property InjunctionViolation $injunctionViolation
 */
class InjunctionImg extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'injunction_img';
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
            [['img_path'], 'required'],
            [['injunction_violation_id'], 'integer'],
            [['img_path'], 'string', 'max' => 255],
            [['injunction_violation_id'], 'exist', 'skipOnError' => true, 'targetClass' => InjunctionViolation::className(), 'targetAttribute' => ['injunction_violation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'img_path' => 'Img Path',
            'injunction_violation_id' => 'Injunction Violation ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionViolation()
    {
        return $this->hasOne(InjunctionViolation::className(), ['id' => 'injunction_violation_id']);
    }
}
