<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "user_workstation".
 *
 * @property int $id ключ роли пользователя
 * @property int $workstation_id ключ роли системы
 * @property int $user_id ключ пользователя
 *
 * @property User $user
 * @property Workstation $workstation
 */
class UserWorkstation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_workstation';
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
            [['workstation_id', 'user_id'], 'required'],
            [['workstation_id', 'user_id'], 'integer'],
            [['workstation_id', 'user_id'], 'unique', 'targetAttribute' => ['workstation_id', 'user_id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['workstation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Workstation::className(), 'targetAttribute' => ['workstation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'workstation_id' => 'Workstation ID',
            'user_id' => 'User ID',
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

    /**
     * Gets query for [[Workstation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkstation()
    {
        return $this->hasOne(Workstation::className(), ['id' => 'workstation_id']);
    }
}
