<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "sap_hcm_hrsroot_pernr_view".
 *
 * @property int $zzorg
 * @property int $hcm_hrsroot_pernr_id
 * @property int $hrsroot_id
 * @property int $pernr
 * @property string $created_by
 * @property string $date_created
 * @property string $modified_by
 * @property string $date_modified
 * @property string $ind_candidat
 * @property int $ind_a6
 */
class SapHcmHrsrootPernrView extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sap_hcm_hrsroot_pernr_view';
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
            [['zzorg', 'hcm_hrsroot_pernr_id', 'hrsroot_id', 'pernr', 'ind_a6'], 'integer'],
            [['date_created', 'date_modified'], 'safe'],
            [['created_by', 'modified_by', 'ind_candidat'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'zzorg' => 'Zzorg',
            'hcm_hrsroot_pernr_id' => 'Hcm Hrsroot Pernr ID',
            'hrsroot_id' => 'Hrsroot ID',
            'pernr' => 'Pernr',
            'created_by' => 'Created By',
            'date_created' => 'Date Created',
            'modified_by' => 'Modified By',
            'date_modified' => 'Date Modified',
            'ind_candidat' => 'Ind Candidat',
            'ind_a6' => 'Ind A6',
        ];
    }
}
