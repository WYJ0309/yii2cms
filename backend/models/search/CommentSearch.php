<?php
/**
 * Author: lf
 * Blog: https://blog.feehi.com
 * Email: job@feehi.com
 * Created at: 2016-06-11 22:11
 */

namespace backend\models\search;

use backend\behaviors\TimeSearchBehavior;
use backend\components\search\SearchEvent;
use backend\models\Article;
use Yii;
use yii\data\ActiveDataProvider;

class CommentSearch extends \common\models\Comment
{

    public $articleTitle;

    public function behaviors()
    {
        return [
            TimeSearchBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['articleTitle', 'created_at', 'updated_at', 'nickname', 'content'], 'string'],
            [['aid', 'status'], 'integer'],
        ];
    }

    /**
     * @param $params
     * @return ActiveDataProvider
     * @throws \yii\base\InvalidConfigException
     */
    public function search($params)
    {
        $query = self::find()->with('article');
        /** @var ActiveDataProvider $dataProvider */
        $dataProvider = Yii::createObject([
            'class' => ActiveDataProvider::className(),
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    //'sort' => SORT_ASC,
                    'id' => SORT_DESC,
                ]
            ]
        ]);
        $this->load($params);
        if (! $this->validate()) {
            return $dataProvider;
        }
        $query->andFilterWhere(['like', 'nickname', $this->nickname])
            ->andFilterWhere(['status' => $this->status])
            ->andFilterWhere(['aid' => $this->aid])
            ->andFilterWhere(['like', 'content', $this->content]);

        if ($this->articleTitle != '') {
            $articles = Article::find()
                ->where(['like', 'title', $this->articleTitle])
                ->select(['id', 'title'])
                ->indexBy('id')
                ->asArray()
                ->all();
            $aidArray = [];
            foreach ($articles as $k => $v) {
                array_push($aidArray, $k);
            }
            $query->andFilterWhere(['aid' => $aidArray]);
        }

        $this->trigger(SearchEvent::BEFORE_SEARCH, Yii::createObject(['class' => SearchEvent::className(), 'query'=>$query]));
        return $dataProvider;
    }
}