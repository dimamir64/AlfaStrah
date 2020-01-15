<?php
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $updated integer */
/* @var $inserted integer */
/* @var $deleted integer */

$this->title = 'Территории';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Импорт';
?>
<div class="territory-import">
    <div class="well">
        <p>Обновлено территорий: <?= $updated ?></p>
        <p>Добавлено территорий: <?= $inserted ?></p>
        <p>Удалено территорий: <?= $deleted ?></p>
    </div>
</div>
