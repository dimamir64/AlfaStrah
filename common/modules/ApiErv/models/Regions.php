<?php
/**
 * Copyright (c) kvk-group 2017.
 */

namespace common\modules\ApiErv\models;

use common\models\GeoCountry;
use Yii;

/**
 * Регионы
 * This is the model class for table "api_erv_regions".
 *
 * @property integer $id
 * @property string $short_name
 * @property string $name
 * @property string $code
 *
 * @property Region2Country[] $apiErvRegion2countries
 * @property GeoCountry[] $countries
 */
class Regions extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api_erv_regions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['short_name', 'name', 'code'], 'required'],
            [['short_name'], 'string', 'max' => 10],
            [['name'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('backend', 'ID'),
            'short_name' => Yii::t('backend', 'Short Name'),
            'name' => Yii::t('backend', 'Name'),
            'code' => Yii::t('backend', 'Code'),
        ];
    }

    /**
     * таблица соответствия регионов станам
     * @return \yii\db\ActiveQuery
     */
    public function getApiErvRegion2countries()
    {
        return $this->hasMany(Region2Country::className(), ['region_id' => 'id']);
    }

    /**
     * страны
     * @return \yii\db\ActiveQuery
     */
    public function getCountries()
    {
        return $this->hasMany(GeoCountry::className(), ['id' => 'country_id'])->viaTable('api_erv_region2country', ['region_id' => 'id']);
    }
}
