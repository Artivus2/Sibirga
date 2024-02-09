<?php

namespace frontend\models;

use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $login
 * @property int $workstation_id
 * @property int $default
 * @property int $worker_id
 * @property string|null $email Эмейл пользователя
 * @property string|null $user_ad_id Логин пользователя из AD
 * @property string|null $props_ad_upd  Реквизит AD UPN
 * @property string|null $date_time_sync дата и время синхронизации
 * @property int|null $mine_id шахтное поле по умолчанию пользователя
 * @property string $auth_key
 * @property Mine $mine
 * @property Worker $worker
 * @property Workstation $workstation
 * @property UserPassword[] $userPasswords
 * @property UserWorkstation[] $userWorkstations
 * @property Workstation[] $workstations
 */
class User extends ActiveRecord implements IdentityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
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
            [['login', 'workstation_id', 'default'], 'required'],
            [['workstation_id', 'default', 'worker_id', 'mine_id'], 'integer'],
            [['date_time_sync'], 'safe'],
            [['login'], 'string', 'max' => 30],
            [['email', 'user_ad_id', 'props_ad_upd', 'auth_key'], 'string', 'max' => 255],
            [['login'], 'unique'],
            [['mine_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mine::className(), 'targetAttribute' => ['mine_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
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
            'login' => 'Login',
            'auth_key' => 'Auth Key',
            'workstation_id' => 'Workstation ID',
            'default' => 'Default',
            'worker_id' => 'Worker ID',
            'email' => 'Email',
            'user_ad_id' => 'User Ad ID',
            'props_ad_upd' => 'Props Ad Upd',
            'date_time_sync' => 'Date Time Sync',
            'mine_id' => 'Mine ID',
        ];
    }

    /**
     * Gets query for [[Mine]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMine()
    {
        return $this->hasOne(Mine::className(), ['id' => 'mine_id']);
    }

    /**
     * Gets query for [[Worker]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
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

    /**
     * Gets query for [[UserPasswords]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserPasswords()
    {
        return $this->hasMany(UserPassword::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[UserWorkstations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserWorkstations()
    {
        return $this->hasMany(UserWorkstation::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Workstations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWorkstations()
    {
        return $this->hasMany(Workstation::className(), ['id' => 'workstation_id'])->viaTable('user_workstation', ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserAccesses()
    {
        return $this->hasMany(UserAccess::className(), ['user_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
}
