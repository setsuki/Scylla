<?php
/**
 * DBコネクション管理クラスScylla複数ノード取り扱い用クラス
 *
 * @package		Scylla
 * @author		setsuki (yukicon)
 * @copyright	Susugi Ningyoukan
 * @license		BSD
 **/

class ScyllaLegs
{
	protected $node_arr = array();
	
	/**
	 * コンストラクタ
	 * @param	array	$node_arr			ノードオブジェクトの配列
	 */
	public function __construct($node_arr)
	{
		$this->node_arr = $node_arr;
	}
	
	
	
	/**
	 * 扱いやすくするために定義
	 * 
	 * @param	string		$func_name			関数名
	 * @param	array		$args				引数の配列
	 * @return	mixed							
	 */
	public function __call($func_name, $args = array())
	{
		switch ($func_name) {
			// クエリ生成用のメソッド(Orthrosに合わせる)
			case 'table':
			case 'column':
			case 'where':
			case 'join':
			case 'order':
			case 'group':
			case 'limit':
			case 'lock':
			case 'cache':
				// 全接続に対して実行(返り値は取らない)
				foreach ($this->node_arr as $db) {
					call_user_func_array(array($db, $func_name), $args);
				}
				// 自分を返す
				return $this;
				break;
			default:
				$tmp_data_arr = array();
				// 全接続に対して実行
				foreach ($this->node_arr as $db) {
					$tmp_data_arr[] = call_user_func_array(array($db, $func_name), $args);
				}
				
				$tmp_data = reset($tmp_data_arr);
				// レスポンスの型によって振り分け
				if (!isset($tmp_data)) {
					// 何も返すものが無い場合
					return null;
				} else if (is_bool($tmp_data)) {
					// boolean の場合は and した結果を返す
					if (!in_array(false, $tmp_data_arr)) {
						// 一つもfalse が無いならtrue
						return true;
					} else {
						// 一つでもfalseがあったらfalse
						return false;
					}
				} else if (is_numeric($tmp_data)) {
					// 数値の場合は和を返す
					$sum = 0;
					foreach ($tmp_data_arr as $tmp_data) {
						$sum += $tmp_data;
					}
					return $sum;
				} else if (is_array($tmp_data)) {
					// 配列の場合はマージして返す
					$data = array();
					foreach ($tmp_data_arr as $tmp_data) {
						$data = array_merge($data, $tmp_data);
					}
					return $data;
				} else {
					// どれにも当てはまらない場合は配列に格納して返す
					$data = array();
					foreach ($tmp_data_arr as $tmp_data) {
						$data[] = $tmp_data;
					}
					return $data;
				}
		}
		
		return null;
	}
}
