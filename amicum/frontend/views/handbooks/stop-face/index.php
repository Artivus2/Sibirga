<?php
/* @var $this yii\web\View */

use yii\web\View;
use yii\data\SqlDataProvider;

$this->title = 'Справочник простоев';
if(Yii::$app->getSession()->hasFlash('error'))
{
    echo Yii::$app->getSession()->getFlash('error');
}

$count = Yii::$app->db->createCommand('SELECT COUNT(*) FROM stop_face')->queryScalar();

$provider = new SqlDataProvider([
    'sql' => 'SELECT * FROM stop_face',
    'pagination' => [
        'pageSize' => 10,
    ],
    'sort' => [
        'attributes' => [
            'id',
            'name',
            'phone',
        ],
    ],
]);
$models = $provider->getModels();

echo \yii\grid\GridView::widget([
    'dataProvider'=> $provider,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],
        'id',
        'title',
        'event_id',
        'description',
        'date_time_start',
        'date_time_end',
        'performer_id',
        'dispatcher_id',
    ],
]);
?>


