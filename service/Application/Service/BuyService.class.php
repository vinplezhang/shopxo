<?php

namespace Service;

/**
 * 购买服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class BuyService
{
    /**
     * 购物车添加
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-08-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CartAdd($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'goods_id',
                'error_msg'         => '商品id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'stock',
                'error_msg'         => '购买数量有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => '用户信息有误',
            ],
        ];
        $ret = params_checked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取商品
        $goods_id = intval($params['goods_id']);
        $goods = M('Goods')->where(['id'=>$goods_id, 'is_shelves'=>1, 'is_delete_time'=>0])->find();
        if(empty($goods))
        {
            return DataReturn('商品不存在或已删除', -2);
        }

        // 属性处理
        $attr = [];
        if(!empty($params['attr']) && is_array($params['attr']))
        {
            foreach($params['attr'] as $k=>$v)
            {
                $attr_type_name = M('GoodsAttributeType')->where(['goods_id'=>$goods_id, 'id'=>$k])->getField('name');
                $attr_name = M('GoodsAttribute')->where(['goods_id'=>$goods_id, 'id'=>$v])->getField('name');
                if(!empty($attr_type_name) && !empty($attr_name))
                {
                    $attr[] = [
                        'attr_type_id'     => $k,
                        'attr_type_name'   => $attr_type_name,
                        'attr_id'          => $v,
                        'attr_name'        => $attr_name,
                    ];
                }
            }
        }

        // 添加购物车
        $data = [
            'user_id'       => $params['user']['id'],
            'goods_id'      => $goods_id,
            'title'         => $goods['title'],
            'images'        => $goods['images'],
            'original_price'=> $goods['original_price'],
            'price'         => $goods['price'],
            'stock'         => intval($params['stock']),
            'attribute'     => count($attr) == 0 ? '' : json_encode($attr),
        ];

        // 存在则更新
        $m = M('Cart');
        $where = ['user_id'=>$data['user_id'], 'goods_id'=>$data['goods_id'], 'attribute'=>$data['attribute']];
        $temp = $m->where($where)->find();
        if(empty($temp))
        {
            $data['add_time'] = time();
            if($m->add($data) > 0)
            {
                return DataReturn(L('common_join_success'), 0);
            }
        } else {
            $data['upd_time'] = time();
            $data['stock'] += $temp['stock'];
            if($m->where($where)->save($data))
            {
                return DataReturn(L('common_join_success'), 0);
            }
        }
        
        return DataReturn(L('common_join_error'), -100);
    }
}
?>