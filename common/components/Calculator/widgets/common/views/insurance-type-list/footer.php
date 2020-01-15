<?php
  /**
   * @var $models \common\models\InsuranceType[]
   * @var $this \yii\web\View
   */
?>
<ul class="sitemap__item">
	<?php foreach ($models as $model) { ?>
    <?php if ($model->programPage){ ?>
  		<li class="sitemap__link"><a class="link link_color_gray" href="<?= $model->programPage->createUrl() ?>"><?= $model->name ?></a></li>
    <?php } ?>
	<?php } ?>
</ul>