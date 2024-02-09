<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "user_password".
 *
 * @property int $id
 * @property int $user_id
 * @property string $date_time
 * @property string $password
 * @property string $check_sum
 *
 * @property User $user
 */
class UserPassword extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_password';
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
            [['user_id', 'date_time', 'password', 'check_sum'], 'required'],
            [['user_id'], 'integer'],
            [['date_time'], 'safe'],
            [['password', 'check_sum'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
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
            'date_time' => 'Date Time',
            'password' => 'Password',
            'check_sum' => 'Check Sum',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
