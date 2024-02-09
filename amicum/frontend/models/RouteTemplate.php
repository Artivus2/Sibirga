<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "route_template".
 *
 * @property int $id Идентификатор шаблона маршрута
 * @property string $title Название маршрута
 * @property float $offset_end  интерполированное значение (ОТ 0 ДО 1) если марщрут начинается не с начала или конца эджа
 * @property int $company_department_id кому пренадлежит ключ департамента
 * @property float $offset_start интерполированное значение (ОТ 0 ДО 1) если марщрут начинается не с начала или конца эджа
 * @property int $status_id Внешний идентификатор справочника статусов
 * @property int $route_type_id
 * @property string $date_time
 * @property int|null $worker_id кто создал шаблон маршрута
 *
 * @property RouteType $routeType
 * @property Status $status
 * @property CompanyDepartment $companyDepartment
 * @property Worker $worker
 * @property RouteTemplateEdge[] $routeTemplateEdges
 */
class RouteTemplate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'route_template';
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
            [['title', 'offset_end', 'company_department_id', 'offset_start', 'status_id', 'route_type_id', 'date_time'], 'required'],
            [['offset_end', 'offset_start'], 'number'],
            [['company_department_id', 'status_id', 'route_type_id', 'worker_id'], 'integer'],
            [['date_time'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['route_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => RouteType::className(), 'targetAttribute' => ['route_type_id' => 'id']],
            [['status_id'], 'exist', 'skipOnError' => true, 'targetClass' => Status::className(), 'targetAttribute' => ['status_id' => 'id']],
            [['company_department_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyDepartment::className(), 'targetAttribute' => ['company_department_id' => 'id']],
            [['worker_id'], 'exist', 'skipOnError' => true, 'targetClass' => Worker::className(), 'targetAttribute' => ['worker_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'offset_end' => 'Offset End',
            'company_department_id' => 'Company Department ID',
            'offset_start' => 'Offset Start',
            'status_id' => 'Status ID',
            'route_type_id' => 'Route Type ID',
            'date_time' => 'Date Time',
            'worker_id' => 'Worker ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRouteType()
    {
        return $this->hasOne(RouteType::className(), ['id' => 'route_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(Status::className(), ['id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyDepartment()
    {
        return $this->hasOne(CompanyDepartment::className(), ['id' => 'company_department_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorker()
    {
        return $this->hasOne(Worker::className(), ['id' => 'worker_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEdge()
    {
        return $this->hasOne(Edge::className(), ['id' => 'edge_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRouteTemplateEdges()
    {
        return $this->hasMany(RouteTemplateEdge::className(), ['route_template_id' => 'id']);
    }
}
