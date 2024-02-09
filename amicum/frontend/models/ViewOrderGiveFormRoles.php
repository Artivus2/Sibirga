<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "view_order_give_form_roles".
 *
 * @property int $worker_object_id Ключ таблицы классификации работников по типовым объектам АМИКУМ
 * @property string $role_title название роли МГВМ ГРОЗ и т.д.
 * @property string $FIO
 */
class ViewOrderGiveFormRoles extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'view_order_give_form_roles';
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
            [['worker_object_id'], 'integer'],
            [['role_title'], 'string', 'max' => 255],
            [['FIO'], 'string', 'max' => 55],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'worker_object_id' => 'Worker Object ID',
            'role_title' => 'Role Title',
            'FIO' => 'Fio',
        ];
    }
}
