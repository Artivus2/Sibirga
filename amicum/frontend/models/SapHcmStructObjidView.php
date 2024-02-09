<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_hcm_struct_objid_view".
 *
 * @property int $hcm_struct_objid_id
 * @property int $zzorg
 * @property int $struct_id
 * @property int $objid
 * @property int $ind_top
 * @property int $fn
 * @property string $created_by
 * @property string $date_created
 * @property string $modified_by
 * @property string $date_modified
 */
class SapHcmStructObjidView extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_hcm_struct_objid_view';
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
            [['hcm_struct_objid_id', 'zzorg', 'struct_id', 'objid', 'ind_top', 'fn'], 'integer'],
            [['date_created', 'date_modified'], 'safe'],
            [['created_by', 'modified_by'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'hcm_struct_objid_id' => 'Hcm Struct Objid ID',
            'zzorg' => 'Zzorg',
            'struct_id' => 'Struct ID',
            'objid' => 'Objid',
            'ind_top' => 'Ind Top',
            'fn' => 'Fn',
            'created_by' => 'Created By',
            'date_created' => 'Date Created',
            'modified_by' => 'Modified By',
            'date_modified' => 'Date Modified',
        ];
    }
}
