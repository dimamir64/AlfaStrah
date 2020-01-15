<?php
/**
 * Copyright (c) kvk-group 2017.
 */

namespace frontend\controllers;

use common\components\ApiModule;
use common\components\Calculator\forms\prototype;
use common\models\Api;
use common\models\InsuranceType;
use common\models\Orders;
use common\models\ProgramResult;
use common\models\User;
use common\components\Calculator\filters\Filter;
use common\components\Calculator\models\travel\FilterSolution2param;
use common\components\Calculator\forms\TravelForm;
use frontend\models\PartnerForm;
use Yii;
use frontend\models\ContactForm;
use yii\db\ActiveRecord;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\Response;

/**
 * Class ApiController Контроллер АПИ
 * @package frontend\controllers
 */
class ApiController extends Controller
{
	/** @var InsuranceType */
	public $insuranceType;

    /**
     * @inheritdoc
     * @param \yii\base\Action $action
     *
     * @return bool
     * @throws HttpException
     */public function beforeAction( $action ) {
		$slug = Yii::$app->request->get('slug', null);
		$this->insuranceType = InsuranceType::findOne(['slug' => $slug]);
		if (!$this->insuranceType){
			throw new HttpException(404, 'Api not found.');
		}

		return parent::beforeAction( $action ); // TODO: Change the autogenerated stub
	}

    /**
     * Предварительные результаты поиска
     * @return string|Response
     */
    public function actionCalcPrepare()
	{
		if (Yii::$app->request->isAjax) {
			$form = prototype::getForm(\Yii::$app->request->post('form_type'));
			$form->load(\Yii::$app->request->post());
			$filter = new Filter(['form' => $form]);

			$this->layout = null;

			return $this->renderPartial($this->insuranceType->slug.'/calc-prepare', [
				'items' => $filter->getPropositions(),
				'form'  => $form
			]);
		} else return $this->redirect('/');
	}

    /**
     * Выбор программы страхования
     * @return string|Response
     */
    public function actionCalcChooseProgram()
    {
        $this->layout = null;
        $model = new ProgramResult();
        if ($data = Yii::$app->request->post('program', false)){
            $model->loadFromJson($data, TravelForm::SCENARIO_PREPAY);
        } else {
            $model->calc->load(Yii::$app->request->post());
        }
        $model->calc->programResult = $model;

        if (Yii::$app->request->isAjax) {
            return $this->renderPartial($this->insuranceType->slug.'/calc-choose-program', ['model' => $model]);
	    } else {
            return $this->render($this->insuranceType->slug.'/calc-choose-program', ['model' => $model]);
        }
    }

    /**
     * Корректировка стоимости программы по измененным параметрам
     * @return array|Response
     */
    public function actionCalcFixCost(){
		if (Yii::$app->request->isAjax) {
			$this->layout = null;

			$model = new ProgramResult();
			$model->loadFromJson(Yii::$app->request->post('program'), TravelForm::SCENARIO_PREPAY);
			$model->calc->load(Yii::$app->request->post());

			$api = Api::findOne(['id' => $model->api_id]);
			if ($api) {
				Yii::$app->response->format = Response::FORMAT_JSON;
				$program = $api->getModule()->getProgram($model->program_id);
				return [
					'price' => $api->getModule()->calcPrice($program, $model->calc, ApiModule::CALC_LOCAL),
					'cnt'   => $model->calc->travellersCount
				];
			}
		}
		return $this->redirect('/');
	}

    /**
     * Расчет стоимости полиса на стороне АПИ
     * @return array|Response
     */
    public function actionCalcApiCost(){
		if (Yii::$app->request->isAjax) {
			$this->layout = null;

			$model = new ProgramResult();
			$model->loadFromJson(Yii::$app->request->post('program'), TravelForm::SCENARIO_PREPAY);

			$api = Api::findOne(['id' => $model->api_id]);
			if ($api) {
				Yii::$app->response->format = Response::FORMAT_JSON;

				$program = $api->getModule()->getProgram($model->program_id);
				$price =  $api->getModule()->calcPrice($program, $model->calc, ApiModule::CALC_LOCAL);

				$model->cost = $price;
				return [
					'price' =>$price,
					'program' =>base64_encode(serialize($model))
				];
			}
		}
		return $this->redirect('/');
	}

    /**
     * Перезагрузка блока готовых решений
     * @return array
     */
    public function actionCalcUpdateSolution()
	{
		if (Yii::$app->request->isAjax) {
			$this->layout = null;
			Yii::$app->response->format = Response::FORMAT_JSON;
			$result = [];
			$solution_params = [];

			$solution_id = Yii::$app->request->post('solution_id');
			if ($solution_id>0) {
				$params = FilterSolution2param::findAll(['filter_solution_id' => $solution_id]);
				foreach ($params as $one) {
					$solution_params[$one->param_id] = $one->value;
				}
			}


			$filters = \common\components\Calculator\models\travel\FilterParam::find()->orderBy(['sort_order' => SORT_ASC])->all();
			foreach($filters as $filter){
				/** @var $filter \common\components\Calculator\models\travel\FilterParam */
				if ($filter && $handler = $filter->getHandler()) {
					$handler->load($solution_params);
					$variant = $handler->loadVariant($solution_params);
					$variant = (is_subclass_of($variant, ActiveRecord::className()))?$variant->getAttributes():$variant;

					$result[] = [
						'id' => $filter->id,
						'slug' => $handler->slug,
						'checked' => (int)$handler->checked,
						'variant' => $variant,
					];
				}
			}
			
			return $result;
		}
	}

    /**
     * Обновление перечня готовых решений
     * @return string
     */
    public function actionCalcUpdateSolutionList()
	{
		return \common\components\Calculator\Calculator::calcPageForm();
	}

    /**
     * @return string|Response
     */
    public function actionCalcPay(){
	    if (Yii::$app->request->isAjax) {
		    $this->layout = null;

		    $model = new ProgramResult();
		    $model->loadFromJson(Yii::$app->request->post('program'), TravelForm::SCENARIO_PAYER);
		    $model->calc->load(Yii::$app->request->post());

		    $order = $model->getOrder();

		    return $this->renderPartial($this->insuranceType->slug.'/calc-pay', [
		    	'model' => $model,
			    'order' => $order
		    ]);
	    } else return $this->redirect('/');
	}

    /**
     * Завершение оформления заказа
     * @return string
     * @throws HttpException
     */
    public function actionCalcPaymentDone()
	{
		$error = Yii::$app->request->get('err');

		if (!$error){
			if (!Yii::$app->payu->checkResultUrl()){
				$error = 'Ошибка обработки запроса';
			}
		}

		$order = Orders::findOne(['id' => Yii::$app->request->get('order')]);
		if (!$order){
			$error = "Заказ не найден";
			throw new HttpException(404, $error);
		}

		if ($error){
			throw new HttpException(400, $error);
		} else {
			$client = User::findOne(['email' => $order->calc_form->payer->email]);
			if (!$client){
				$password = Yii::$app->security->generateRandomString(8);

				$client = new User();
				$client->email    = $order->calc_form->payer->email;
				$client->status   = User::STATUS_ACTIVE;
				$client->username = $client->email;
				$client->setPassword($password);
				$client->save();

				$client->afterSignup();

				$body = $this->renderFile('@frontend/views/email/registration.php', [
					'username' => $client->email,
					'password' => $password
				]);

				\Yii::$app->mailer->compose()
				                  ->setTo($client->email)
				                  ->setFrom([ env('ROBOT_EMAIL') => \Yii::$app->name . ' robot'])
				                  ->setSubject('Регистрация')
				                  ->setHtmlBody($body)
				                  ->send();
				Yii::$app->user->login($client);
			}

			/*
		    if ($order->status == Orders::STATUS_NEW) {
			    $order->api->getModule()->buyOrder($order);
		    }
			*/

			return $this->render($this->insuranceType->slug.'/calc-payment-done', [
				//'order'  => $order,
				'client' => $client,
				'link'   => $order->api->getModule()->getPoliceLink($order)
			]);
		}
	}

}