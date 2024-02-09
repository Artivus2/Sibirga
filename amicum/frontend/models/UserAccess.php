<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "user_access".
 *
 * @property int $id
 * @property int $user_id
 * @property int $access_id
 * @property int|null $read
 * @property int|null $write
 *
 * @property Access $access
 */
class UserAccess extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_access';
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
            [['user_id', 'access_id'], 'required'],
            [['user_id', 'access_id', 'read', 'write'], 'integer'],
            [['access_id'], 'exist', 'skipOnError' => true, 'targetClass' => Access::className(), 'targetAttribute' => ['access_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'access_id' => 'Access ID',
            'read' => 'Read',
            'write' => 'Write',
        ];
    }

    /**
     * Gets query for [[Access]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAccess()
    {
        return $this->hasOne(Access::className(), ['id' => 'access_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
