<?php

namespace backend\controllers;

use common\modules\ApiSberbank\models\Risk2internal;
use Yii;
use common\modules\ApiSberbank\models\Risk;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SberbankRiskController implements the CRUD actions for Risk model.
 */
class SberbankRiskController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Risk models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Risk::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Updates an existing Risk model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            $this->processRisks($model);
            $this->processTerritories($model);

            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Risk model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Risk model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Risk the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Risk::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    protected function processRisks(Risk $model){
        Risk2internal::deleteAll(['risk_id' => $model->id]);
        $risks = Yii::$app->request->post('risk', []);
        foreach ($risks as $risk_id){
            $risk = new Risk2internal();
            $risk->internal_id = $risk_id;
            $risk->risk_id     = $model->id;
            $risk->save();
        }
    }

    protected function processTerritories(Risk $model) {
        Risk2Territory::deleteAll(['risk_id' => $model->id]);
        $territories = Yii::$app->request->post('territory', []);
        foreach ($territories as $territory_id){
            $t2p = new Risk2Territory();
            $t2p->risk_id   = $model->id;
            $t2p->territory_id = $territory_id;
            $t2p->save();
        }
    }

}
