<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_role_update".
 *
 * @property int $id_count
 * @property int $id
 * @property string $title
 * @property int $num_sync
 * @property int $status
 */
class SapRoleUpdate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_role_update';
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
            [['id', 'num_sync', 'status'], 'integer'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_count' => 'Id Count',
            'id' => 'ID',
            'title' => 'Title',
            'num_sync' => 'Num Sync',
            'status' => 'Status',
        ];
    }
}
