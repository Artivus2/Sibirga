<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "injunction_violation".
 *
 * @property int $id
 * @property int $probability Вероятность нарушения
 * @property int $gravity Опасность нарушения
 * @property string $correct_period Срок устранения нарушения
 * @property int $injunction_id Внешний ключ предписаний
 * @property int $place_id Внешний ключ мест
 * @property int $violation_id Внешний ключ нарушений
 * @property int $paragraph_pb_id Внешний ключ пункта ПБ
 * @property int $document_id Документ
 * @property int $instruct_id_ip ключ пункта предписания sap
 * @property string $date_time_sync дата синхронизации
 * @property int $instruct_rtn_id
 * @property string $date_time_sync_rostex
 * @property int $reason_danger_motion_id Причина опасного действия
 * @property string $instruct_pab_id
 * @property string $date_time_sync_pab
 * @property string $instruct_nn_id
 * @property string $date_time_sync_nn
 *
 * @property CorrectMeasures[] $correctMeasures
 * @property InjunctionImg[] $injunctionImgs
 * @property Document $document
 * @property Injunction $injunction
 * @property ParagraphPb $paragraphPb
 * @property Place $place
 * @property ReasonDangerMotion $reasonDangerMotion
 * @property Violation $violation
 * @property InjunctionViolationStatus[] $injunctionViolationStatuses
 * @property StopPb[] $stopPbs
 * @property Violator[] $violators
 */
class InjunctionViolation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'injunction_violation';
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
            [['probability', 'gravity', 'injunction_id', 'place_id', 'violation_id', 'document_id'], 'required'],
            [['probability', 'gravity', 'injunction_id', 'place_id', 'violation_id', 'paragraph_pb_id', 'document_id', 'instruct_id_ip', 'instruct_rtn_id', 'reason_danger_motion_id'], 'integer'],
            [['correct_period', 'date_time_sync', 'date_time_sync_rostex', 'date_time_sync_pab', 'date_time_sync_nn'], 'safe'],
            [['instruct_pab_id', 'instruct_nn_id'], 'string', 'max' => 255],
            [['document_id'], 'exist', 'skipOnError' => true, 'targetClass' => Document::className(), 'targetAttribute' => ['document_id' => 'id']],
            [['injunction_id'], 'exist', 'skipOnError' => true, 'targetClass' => Injunction::className(), 'targetAttribute' => ['injunction_id' => 'id']],
            [['paragraph_pb_id'], 'exist', 'skipOnError' => true, 'targetClass' => ParagraphPb::className(), 'targetAttribute' => ['paragraph_pb_id' => 'id']],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::className(), 'targetAttribute' => ['place_id' => 'id']],
            [['reason_danger_motion_id'], 'exist', 'skipOnError' => true, 'targetClass' => ReasonDangerMotion::className(), 'targetAttribute' => ['reason_danger_motion_id' => 'id']],
            [['violation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Violation::className(), 'targetAttribute' => ['violation_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'probability' => 'Вероятность нарушения',
            'gravity' => 'Опасность нарушения',
            'correct_period' => 'Срок устранения нарушения',
            'injunction_id' => 'Внешний ключ предписаний',
            'place_id' => 'Внешний ключ мест',
            'violation_id' => 'Внешний ключ нарушений',
            'paragraph_pb_id' => 'Внешний ключ пункта ПБ',
            'document_id' => 'Документ',
            'instruct_id_ip' => 'ключ пункта предписания sap',
            'date_time_sync' => 'дата синхронизации',
            'instruct_rtn_id' => 'Instruct Rtn ID',
            'date_time_sync_rostex' => 'Date Time Sync Rostex',
            'reason_danger_motion_id' => 'Причина опасного действия',
            'instruct_pab_id' => 'Instruct Pab ID',
            'date_time_sync_pab' => 'Date Time Sync Pab',
            'instruct_nn_id' => 'Instruct Nn ID',
            'date_time_sync_nn' => 'Date Time Sync Nn',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCorrectMeasures()
    {
        return $this->hasMany(CorrectMeasures::className(), ['injunction_violation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionImgs()
    {
        return $this->hasMany(InjunctionImg::className(), ['injunction_violation_id' => 'id']);
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
    public function getInjunction()
    {
        return $this->hasOne(Injunction::className(), ['id' => 'injunction_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParagraphPb()
    {
        return $this->hasOne(ParagraphPb::className(), ['id' => 'paragraph_pb_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::className(), ['id' => 'place_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReasonDangerMotion()
    {
        return $this->hasOne(ReasonDangerMotion::className(), ['id' => 'reason_danger_motion_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getViolation()
    {
        return $this->hasOne(Violation::className(), ['id' => 'violation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInjunctionViolationStatuses()
    {
        return $this->hasMany(InjunctionViolationStatus::className(), ['injunction_violation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStopPbs()
    {
        return $this->hasMany(StopPb::className(), ['injunction_violation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getViolators()
    {
        return $this->hasMany(Violator::className(), ['injunction_violation_id' => 'id']);
    }


    /****************************************************** СОЗДАНЫ ВРУЧНУЮ *******************************************/
    public function getInjunctionImg()
    {
        return $this->hasOne(InjunctionImg::className(), ['injunction_violation_id' => 'id']);
    }

    // Добавлена вручную последний статус предписания по дате
    public function getInjunctionViolationStatus()
    {
        return $this->hasOne(InjunctionViolationStatus::className(), ['injunction_violation_id' => 'id'])->orderBy('injunction_violation_status.date_time DESC')->limit(1);
    }
}
