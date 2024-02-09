<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_ref_norm_doc_mv".
 *
 * @property int $ref_norm_doc_id
 * @property int $parent_id
 * @property string $name
 * @property string $date_beg
 * @property string $date_end
 * @property string $created_by
 * @property string $date_created
 * @property string $modified_by
 * @property string $date_modified
 */
class SapRefNormDocMv extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_ref_norm_doc_mv';
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
            [['ref_norm_doc_id'], 'required'],
            [['ref_norm_doc_id', 'parent_id'], 'integer'],
            [['date_beg', 'date_end', 'date_created', 'date_modified'], 'safe'],
            [['name', 'created_by', 'modified_by'], 'string', 'max' => 455],
            [['ref_norm_doc_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ref_norm_doc_id' => 'Ref Norm Doc ID',
            'parent_id' => 'Parent ID',
            'name' => 'Name',
            'date_beg' => 'Date Beg',
            'date_end' => 'Date End',
            'created_by' => 'Created By',
            'date_created' => 'Date Created',
            'modified_by' => 'Modified By',
            'date_modified' => 'Date Modified',
        ];
    }
}
