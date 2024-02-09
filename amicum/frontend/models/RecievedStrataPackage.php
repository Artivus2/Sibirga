<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "recieved_strata_package".
 *
 * @property int $id
 * @property string $date_time
 * @property int $strata_gateway_id
 * @property int $strata_package_type_id
 * @property string $package
 *
 * @property StrataGateway $strataGateway
 * @property StrataPackageType $strataPackageType
 */
class RecievedStrataPackage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'recieved_strata_package';
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
            [['date_time', 'strata_gateway_id', 'strata_package_type_id', 'package'], 'required'],
            [['date_time'], 'safe'],
            [['strata_gateway_id', 'strata_package_type_id'], 'integer'],
            [['package'], 'string'],
            [['strata_gateway_id'], 'exist', 'skipOnError' => true, 'targetClass' => StrataGateway::className(), 'targetAttribute' => ['strata_gateway_id' => 'id']],
            [['strata_package_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => StrataPackageType::className(), 'targetAttribute' => ['strata_package_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_time' => 'Date Time',
            'strata_gateway_id' => 'Strata Gateway ID',
            'strata_package_type_id' => 'Strata Package Type ID',
            'package' => 'Package',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStrataGateway()
    {
        return $this->hasOne(StrataGateway::className(), ['id' => 'strata_gateway_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStrataPackageType()
    {
        return $this->hasOne(StrataPackageType::className(), ['id' => 'strata_package_type_id']);
    }
}
