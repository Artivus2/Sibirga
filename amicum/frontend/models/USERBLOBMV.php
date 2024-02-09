<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "USER_BLOB_MV".
 *
 * @property int $USER_BLOB_ID
 * @property resource $BLOB_OBJ
 * @property string $FILE_NAME
 * @property string $CREATED_BY
 * @property string $DATE_CREATED
 * @property string $MODIFIED_BY
 * @property string $DATE_MODIFIED
 * @property string $TNAME
 * @property int $TID
 */
class USERBLOBMV extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'USER_BLOB_MV';
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
            [['USER_BLOB_ID'], 'required'],
            [['USER_BLOB_ID', 'TID'], 'integer'],
            [['BLOB_OBJ'], 'string'],
            [['DATE_CREATED', 'DATE_MODIFIED'], 'safe'],
            [['FILE_NAME'], 'string', 'max' => 200],
            [['CREATED_BY', 'MODIFIED_BY', 'TNAME'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'USER_BLOB_ID' => 'User Blob ID',
            'BLOB_OBJ' => 'Blob Obj',
            'FILE_NAME' => 'File Name',
            'CREATED_BY' => 'Created By',
            'DATE_CREATED' => 'Date Created',
            'MODIFIED_BY' => 'Modified By',
            'DATE_MODIFIED' => 'Date Modified',
            'TNAME' => 'Tname',
            'TID' => 'Tid',
        ];
    }
}
