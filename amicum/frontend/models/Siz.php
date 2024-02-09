<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "siz".
 *
 * @property int $id Идентификатор таблицы(автоинкрементный)
 * @property string $title Наименование СИЗ
 * @property int $unit_id Внешний ключ к справочнику единиц измерения
 * @property int $wear_period Срок носки средства индивидуальной защиты
 * @property int $season_id Внешний ключ к справочнику сезонов
 * @property string $comment Примечание к средству индивидуальной защиты
 * @property string $link_1c Ключ СИЗ из внешней системы 1С
 * @property int $siz_kind_id
 * @property int $document_id Внешний ключ к справочнику
 * @property int $siz_subgroup_id
 *
 * @property Document $document
 * @property Season $season
 * @property SizKind $sizKind
 * @property SizSubgroup $sizSubgroup
 * @property Unit $unit
 * @property SizStore[] $sizStores
 * @property CompanyDepartment[] $companyDepartments
 * @property WorkerSiz[] $workerSizs
 */
class Siz extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'siz';
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
            [['title', 'unit_id', 'wear_period', 'season_id', 'siz_kind_id'], 'required'],
            [['unit_id', 'wear_period', 'season_id', 'siz_kind_id', 'document_id', 'siz_subgroup_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['comment'], 'string', 'max' => 255],
            [['link_1c'], 'string', 'max' => 100],
            [['document_id'], 'exist', 'skipOnError' => true, 'targetClass' => Document::className(), 'targetAttribute' => ['document_id' => 'id']],
            [['season_id'], 'exist', 'skipOnError' => true, 'targetClass' => Season::className(), 'targetAttribute' => ['season_id' => 'id']],
            [['siz_kind_id'], 'exist', 'skipOnError' => true, 'targetClass' => SizKind::className(), 'targetAttribute' => ['siz_kind_id' => 'id']],
            [['siz_subgroup_id'], 'exist', 'skipOnError' => true, 'targetClass' => SizSubgroup::className(), 'targetAttribute' => ['siz_subgroup_id' => 'id']],
            [['unit_id'], 'exist', 'skipOnError' => true, 'targetClass' => Unit::className(), 'targetAttribute' => ['unit_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор таблицы(автоинкрементный)',
            'title' => 'Наименование СИЗ',
            'unit_id' => 'Внешний ключ к справочнику единиц измерения',
            'wear_period' => 'Срок носки средства индивидуальной защиты',
            'season_id' => 'Внешний ключ к справочнику сезонов',
            'comment' => 'Примечание к средству индивидуальной защиты',
            'link_1c' => 'Ключ СИЗ из внешней системы 1С',
            'siz_kind_id' => 'Siz Kind ID',
            'document_id' => 'Внешний ключ к справочнику ',
            'siz_subgroup_id' => 'Siz Subgroup ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDocument()
    {
        return $this->hasOne(Document::className(), ['id' => 'document_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSeason()
    {
        return $this->hasOne(Season::className(), ['id' => 'season_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSizKind()
    {
        return $this->hasOne(SizKind::className(), ['id' => 'siz_kind_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSizSubgroup()
    {
        return $this->hasOne(SizSubgroup::className(), ['id' => 'siz_subgroup_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUnit()
    {
        return $this->hasOne(Unit::className(), ['id' => 'unit_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSizStores()
    {
        return $this->hasMany(SizStore::className(), ['siz_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartments()
    {
        return $this->hasMany(CompanyDepartment::className(), ['id' => 'company_department_id'])->viaTable('siz_store', ['siz_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkerSizs()
    {
        return $this->hasMany(WorkerSiz::className(), ['siz_id' => 'id']);
    }
}
