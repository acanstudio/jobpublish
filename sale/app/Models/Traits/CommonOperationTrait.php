<?php

namespace App\Models\Traits;

trait CommonOperationTrait
{
    public function filterCondition($query, $params, $actionType)
    {
        $datas = $this->_selfQueryDatas($params, $actionType);
        $query = $this->dealSelfQuery($query, $datas, $params);

        $relateDatas = $this->_relateQueryDatas($params, $actionType);;
        $query = $this->dealRelateQuery($query, $relateDatas, $params);
        return $query;
    }

    protected function dealSelfQuery($query, $datas, $params)
    {
        //print_r($datas);print_r($params);
        foreach ($datas as $field => $data){
            if (!isset($params[$field])) {
                continue;
            }
            $value = $params[$field];
            if (empty($value)) {
                if (!isset($data['emptyValue']) || !in_array($value, $data['emptyValue'])) {
                    continue;
                }
            }
            $operation = $data['operation'] ?? '';
            switch ($operation) {
                case 'like':
                    $query->where($field, 'like', '%' . $value . '%');
                    break;
                case 'in':
                    $query->whereIn($field, $params[$field]);
                    break;
                default:
                    $query->where($field, $value);
            }
        }
        return $query;
    }

    protected function dealRelateQuery($query, $relateDatas, $params)
    {
        foreach ($relateDatas as $field => $elem) {
            if (empty($params[$field])) {
                continue;
            }
            $model = $elem['model'];
            $query = $query->whereHas($model, function ($query) use ($elem, $field, $params, $model) {
                if (in_array($model, ['userOnline'])){
                    $database = config('database.connections.lpsy.database');
                    $query = $query->from(\DB::raw("{$database}.el_user"));
                }
                switch ($elem['operation']) {
                case 'like':
                    $query->where($elem['relateField'], 'like', "%{$params[$field]}%");
                    break;
                case 'equal':
                    $query->where($elem['relateField'], $params[$field]);
                    break;
                }
            });
        }
        return $query;
    }

    protected function _selfQueryDatas(& $params, $actionType)
    {
        return [];
    }

    protected function _relateQueryDatas(& $params, $actionType)
    {
        return [];
    }
}
