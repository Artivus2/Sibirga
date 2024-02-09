<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_user_update".
 *
 * @property int $id
 * @property int $worker_id Внешний идентификатор работника
 * @property string $email Адрес электронной почты
 * @property string $user_ad_id Логин пользователя AD
 * @property string $props_ad_upd  Реквизит AD UPN
 * @property string $date_modified Дата и время обновления записи
 */
class SapUserUpdate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_user_update';
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
            [['worker_id', 'user_ad_id', 'date_modified'], 'required'],
            [['worker_id'], 'integer'],
            [['date_modified'], 'safe'],
            [['email', 'user_ad_id', 'props_ad_upd'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'worker_id' => 'Внешний идентификатор работника',
            'email' => 'Адрес электронной почты',
            'user_ad_id' => 'Логин пользователя AD',
            'props_ad_upd' => ' Реквизит AD UPN',
            'date_modified' => 'Дата и время обновления записи',
        ];
    }
}
