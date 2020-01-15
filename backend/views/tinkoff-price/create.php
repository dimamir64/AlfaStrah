<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\modules\ApiVtb\models\Price */
/* @var $productModel common\modules\ApiTinkoff\models\Product */

$this->title = 'Добавление страховой суммы в продукт';
$this->params['breadcrumbs'][] = ['label' => $productModel->Name, 'url' => ['/tinkoff-product/update', 'id' => $model->product_id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="price-create">

    <?php echo $this->render('_form', [
        'model' => $model,
        'productModel' => $productModel
    ]) ?>

</div>
