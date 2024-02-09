<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_ref_error_direction_mv".
 *
 * @property int $ref_error_direction_id
 * @property string $name
 * @property string $date_beg
 * @property string $date_end
 * @property string $created_by
 * @property string $date_created
 * @property string $modified_by
 * @property string $date_modified
 * @property int $parent_id
 * @property int $sort_order
 */
class SapRefErrorDirectionMv extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_ref_error_direction_mv';
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
            [['ref_error_direction_id'], 'required'],
            [['ref_error_direction_id', 'parent_id', 'sort_order'], 'integer'],
            [['date_beg', 'date_end', 'date_created', 'date_modified'], 'safe'],
            [['name'], 'string', 'max' => 545],
            [['created_by', 'modified_by'], 'string', 'max' => 455],
            [['ref_error_direction_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ref_error_direction_id' => 'Ref Error Direction ID',
            'name' => 'Name',
            'date_beg' => 'Date Beg',
            'date_end' => 'Date End',
            'created_by' => 'Created By',
            'date_created' => 'Date Created',
            'modified_by' => 'Modified By',
            'date_modified' => 'Date Modified',
            'parent_id' => 'Parent ID',
            'sort_order' => 'Sort Order',
        ];
    }
}
