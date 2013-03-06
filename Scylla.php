<?php
/**
 * DBコネクション管理クラスScylla本体
 *
 * @package		Scylla
 * @author		setsuki (yukicon)
 * @copyright	Susugi Ningyoukan
 * @license		BSD
 **/

require_once __DIR__ . '/scylla_define.php';
require_once __DIR__ . '/ScyllaLegs.php';

class Scylla
{
	const NODE_OBJECT_NAME = 'Orthros';				// ノードとなるクラス名
		
	// ローカル変数
	protected $node_arr = array();
	protected $legs_arr = array();
	
	static protected $instance;					// 一般インスタンス
	
	/**
	 * コンストラクタ
	 */
	public function __construct()
	{
		// ノードクラスファイルを読み込む
		require_once __DIR__ . sprintf('/%s/%s.php', strtolower(static::NODE_OBJECT_NAME), static::NODE_OBJECT_NAME);
		if ($GLOBALS['Scylla']['CACHE']) {
			// キャッシュの設定があるならキャッシュのホストを設定
			$class_name = static::NODE_OBJECT_NAME;
			foreach ($GLOBALS['Scylla']['CACHE'] as $category => $category_info) {
				$class_name::setCacheHost($category_info['HOST_ARR'], $category_info['DEFAULT_EXPIRE'], $category);
			}
		}
	}
	
	/**
	 * シングルトン
	 * @return	Scylla			このクラスの実体
	 */
	static public function singleton()
	{
		if (!isset(self::$instance)) {
			$instanse_class_name = get_called_class();
			self::$instance = new $instanse_class_name();
		}
		
		return self::$instance;
	}
	
	// ========================================================================
	// DB接続関連のメソッド
	// ========================================================================
	
	/**
	 * DBへの接続を取得する
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	int			$server_id		データベースのサーバID
	 * @param	int			$server_type	サーバタイプ(SCYLLA_SERVER_TYPE_XXXX)
	 * @return	Orthros						ノードオブジェクト
	 */
	public function & getNode($db_group, $server_id = 1, $server_type = null)
	{
		if (is_null($server_type)) {
			// サーバタイプが指定されなかった場合は自動で選択
			if (isset($this->node_arr[$db_group][$server_id][SCYLLA_SERVER_TYPE_MASTER]) and 
				$this->node_arr[$db_group][$server_id][SCYLLA_SERVER_TYPE_MASTER]->isTransaction()) {
				// マスタに接続済みで、かつトランザクション中ならマスタ
				$server_type = SCYLLA_SERVER_TYPE_MASTER;
			} else {
				// それ以外ならスレーブを対象とする
				$server_type = SCYLLA_SERVER_TYPE_SLAVE;
			}
		}
		
		if (!isset($this->node_arr[$db_group][$server_id][$server_type])) {
			// まだ接続していないなら接続する
			$server_info = array();
			$base_server_info = array();
			$db_name = $GLOBALS['Scylla']['DB'][$db_group]['DB_NAME'];
			if ($GLOBALS['Scylla']['DB'][$db_group]['PARTITION']) {
				// 水平分割ありの場合
				$base_server_info = $GLOBALS['Scylla']['DB'][$db_group]['PART_ARR'][$server_id];
				$db_name = $GLOBALS['Scylla']['DB'][$db_group]['DB_NAME'] . $server_id;
			} else {
				$base_server_info = $GLOBALS['Scylla']['DB'][$db_group];
			}
			if (SCYLLA_SERVER_TYPE_SLAVE == $server_type) {
				// スレーブの場合はスレーブ番号を取得
				$slave_server_num = $this->getSlaveNum($db_group, $server_id);
				if (0 < $slave_server_num) {
					// 選ばれたなら対象のサーバを使用
						$server_info = $base_server_info['SLAVE'][$slave_server_num];
				} else {
					// スレーブが選ばれなかった場合はマスタを使用
					$server_type = SCYLLA_SERVER_TYPE_MASTER;
					$server_info = $base_server_info['MASTER'];
				}
			} else {
				// それ以外ならマスタ
				$server_info = $base_server_info['MASTER'];
			}
			
			// ノードオブジェクトを生成してDBに接続
			$db_host = $server_info['HOST'];
			$db_port = $server_info['PORT'];
			$db_user = $server_info['USER'];
			$db_pass = $server_info['PASS'];
			$node_class_name = static::NODE_OBJECT_NAME;
			$this->node_arr[$db_group][$server_id][$server_type] = new $node_class_name($db_host, $db_port, $db_name, $db_user, $db_pass);
			if (!empty($GLOBALS['Scylla']['PDO_SETTING'])) {
				// PDOの設定があるならセット
				$this->node_arr[$db_group][$server_id][$server_type]->setPDOAttribute($GLOBALS['Scylla']['PDO_SETTING']);
			}
			if (!empty($GLOBALS['Scylla']['DB'][$db_group]['CACHE_CATEGORY'])) {
				// キャッシュカテゴリの指定があるならセット
				$this->node_arr[$db_group][$server_id][$server_type]->setDefaultCacheCategory($GLOBALS['Scylla']['DB'][$db_group]['CACHE_CATEGORY']);
			}
		}
		
		return $this->node_arr[$db_group][$server_id][$server_type];
	}
	
	
	
	/**
	 * DBへの接続の配列を取得する
	 * 水平分割用
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	int			$server_id_arr	データベースのサーバID配列
	 * @param	int			$server_type	サーバタイプ(SCYLLA_SERVER_TYPE_XXXX)
	 * @returu	array						ノードオブジェクトの配列
	 */
	public function & getPartitionDBNodeArray($db_group, $server_id_arr = array(), $server_type = null)
	{
		$return_node_arr = array();
		
		if (empty($server_id_arr)) {
			// サーバID配列が指定されなかった場合は全DBを返す
			for ($i = 1; $i <= $GLOBALS['Scylla']['DB'][$db_group]['PART_NUM']; $i++) {
				$return_node_arr[] =& $this->getNode($db_group, $i, $server_type);
			}
		} else {
			foreach ($server_id_arr as $server_id) {
				$return_node_arr[] =& $this->getNode($db_group, $server_id);
			}
		}
		
		return $return_node_arr;
	}
	
	
	
	/**
	 * 使用するスレーブの番号を返す
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	int			$server_id		データベースのサーバID
	* @return	int							使用するスレーブの番号
	 */
	protected function getSlaveNum($db_group, $server_id = 1)
	{
		if ($GLOBALS['Scylla']['DB'][$db_group]['PARTITION']) {
			// 水平分割ありの場合
			$base_server_info = $GLOBALS['Scylla']['DB'][$db_group]['PART_ARR'][$server_id];
		} else {
			$base_server_info = $GLOBALS['Scylla']['DB'][$db_group];
		}
		
		if (0 >= $base_server_info['SLAVE_NUM']) {
			// スレーブが無いなら0を返す
			return 0;
		}
		
		$total_slave_weight = 0;
		$slave_weight_arr = array();
		foreach ($base_server_info['SLAVE'] as $slave_num => $slave_info) {
			if (0 < $slave_info['WEIGHT']) {
				// 重みが設定されているなら配列に格納
				$slave_weight_arr[$slave_num] = $slave_info['WEIGHT'];
				$total_slave_weight = $slave_info['WEIGHT'];
			}
		}
		
		if (0 >= $total_slave_weight) {
			// 合計の重みが0以下なら0を返す
			return 0;
		}
		
		// 重みによる抽選をして返す
		$rand_num = mt_rand(1, $total_slave_weight);
		$total_num = 0;
		foreach ($slave_weight_arr as $slave_num => $weight) {
			$total_num += $weight;
			if ($rand_num >= $total_num) {
				return $slave_num;
			}
		}
		
		return 0;
	}
	
	
	
	/**
	 * 現在既に接続済みのノードの配列を返す
	 * 
	 * @param	bool		$master_only_flg		マスタのみ取得するかどうかのフラグ
	 * @return	array								ノードオブジェクトの配列
	 */
	protected function getCurrentConnectionNode($master_only_flg = false)
	{
		$return_node_arr = array();
		foreach ($this->node_arr as $db_group => $node_arr) {
			foreach ($node_arr as $server_id => $db_node_info) {
				if ($master_only_flg and isset($db_node_info[SCYLLA_SERVER_TYPE_MASTER])) {
					// マスタのみの指定があり、かつ接続済みの場合はマスタのインスタンス
					$return_node_arr[] =& $this->node_arr[$db_group][$server_id][SCYLLA_SERVER_TYPE_MASTER];
				} else {
					$return_node_arr[] =& $this->getNode($db_group, $server_id);
				}
			}
		}
		return $return_node_arr;
	}
	
	
	
	/**
	 * 全接続を切断する
	 */
	public function disconnectAll()
	{
		unset($this->node_arr);
	}
	
	// ========================================================================
	// DB分割関連のメソッド
	// ========================================================================
	
	/**
	 * 対象のサーバIDを返す(DBテーブルによるパーティション用)
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	string		$table			IDテーブル名
	 * @param	int			$id				キーとなるID
	 * @return	int							対応したサーバID
	 */
	protected function getServerIdByTable($db_group, $table, $id)
	{
		static $server_id_info_arr = array();
		if (!isset($server_id_info_arr[$db_group][$table][$id])) {
			// 取得前の場合のみ対応したDBに接続してサーバID情報を取得
			$partition_info_db = $this->getNode($GLOBALS['Scylla']['DB'][$db_group]['PARTITION_TABLE_ARR'][$table]['DB_GROUP']);
			$key_col_name = $GLOBALS['Scylla']['DB'][$db_group]['PARTITION_TABLE_ARR'][$table]['KEY_COL'];
			$server_id_col_name = $GLOBALS['Scylla']['DB'][$db_group]['PARTITION_TABLE_ARR'][$table]['SERVER_ID_COL'];
			if (isset($GLOBALS['Scylla']['CACHE'])) {
				// キャッシュが使えるならキャッシュを使う
				$connection_info = $partition_info_db->table($table)->column(array($key_col_name, $server_id_col_name))->where(array($key_col_name => $id))->cache()->selectOne();
			} else {
				$connection_info = $partition_info_db->table($table)->column(array($key_col_name, $server_id_col_name))->where(array($key_col_name => $id))->selectOne();
			}
			if (empty($connection_info)) {
				throw new Exception(sprintf('[Scylla] DBテーブルからのサーバID取得失敗 対応したサーバ情報なし db_group=%s table=%s id=%s', $db_group, $table, $id));
			}
			
			// 値をstatic変数にキャッシュする
			$server_id_info_arr[$db_group][$table][$id] = $connection_info[$server_id_col_name];
		}
		
		return $server_id_info_arr[$db_group][$table][$id];
	}
	
	
	
	/**
	 * 対象のサーバIDの配列を返す(リストによるパーティション用)
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	string		$table			IDテーブル名
	 * @param	int			$id_arr			キーとなるIDの配列
	 * @return	int							対応したサーバID
	 */
	protected function getServerIdArrayByTable($db_group, $table, $id_arr)
	{
		$return_server_id_arr = array();
		foreach ($id_arr as $id) {
			$server_id = $this->getServerIdByTable($db_group, $table, $id);
			if (!in_array($server_id, $return_server_id_arr)) {
				// まだ対称の配列に含まれていない場合
				$return_server_id_arr[] = $server_id;
				if ($GLOBALS['Scylla']['DB'][$db_group]['PART_NUM'] <= count($return_server_id_arr)) {
					// 全接続となった場合は抜ける
					break;
				}
			}
		}
		return $return_server_id_arr;
	}
	
	
	
	/**
	 * 対象のサーバIDを返す(MODによるパーティション用)
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	int			$id				キーとなるID
	 * @return	int							対応したサーバID
	 */
	protected function getServerIdByMod($db_group, $id)
	{
		return ($id % $GLOBALS['Scylla']['DB'][$db_group]['PART_NUM']) + 1;
	}
	
	
	
	/**
	 * 対象のサーバIDの配列を返す(リストによるパーティション用)
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	int			$id_arr			キーとなるIDの配列
	 * @return	int							対応したサーバID
	 */
	protected function getServerIdArrayByMod($db_group, $id_arr)
	{
		$return_server_id_arr = array();
		foreach ($id_arr as $id) {
			$server_id = $this->getServerIdByMod($db_group, $id);
			if (!in_array($server_id, $return_server_id_arr)) {
				// まだ対称の配列に含まれていない場合
				$return_server_id_arr[] = $server_id;
				if ($GLOBALS['Scylla']['DB'][$db_group]['PART_NUM'] <= count($return_server_id_arr)) {
					// 全接続となった場合は抜ける
					break;
				}
			}
		}
		return $return_server_id_arr;
	}
	
	
	
	/**
	 * 対象のサーバIDを返す(範囲によるパーティション用)
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	string		$id_name		キーID名
	 * @param	int			$id				キーとなるID
	 * @return	int							対応したサーバID
	 */
	protected function getServerIdByRange($db_group, $id_name, $id)
	{
		$return_server_id = 1;
		// どのサーバの範囲に入るかを決定する
		foreach ($GLOBALS['Scylla']['DB'][$db_group]['PARTITION_RANGE_ARR'][$id_name] as $server_id => $range_info) {
			if (isset($range_info['MIN']) and isset($range_info['MAX'])) {
				// 最小最大ともにセットされている場合
				if ($range_info['MIN'] <= $id and $id <= $range_info['MAX']) {
					// 範囲内の場合はこれで決定
					$return_server_id = $server_id;
					break;
				}
			} else if (isset($range_info['MAX'])) {
				// 最大だけがセットされている場合
				if ($id <= $range_info['MAX']) {
					// 最大値以下ならこれで決定
					$return_server_id = $server_id;
					break;
				}
			} else if (isset($range_info['MIN'])) {
				// 最小だけがセットされている場合
				if ($id >= $range_info['MIN']) {
					// 最小値以上ならこれで決定
					$return_server_id = $server_id;
					break;
				}
			}
		}
		
		return $return_server_id;
	}
	
	
	
	/**
	 * 対象のサーバIDの配列を返す(リストによるパーティション用)
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	string		$id_name		キーID名
	 * @param	int			$id_arr			キーとなるIDの配列
	 * @return	int							対応したサーバID
	 */
	protected function getServerIdArrayByRange($db_group, $id_name, $id_arr)
	{
		$return_server_id_arr = array();
		foreach ($id_arr as $id) {
			$server_id = $this->getServerIdByRange($db_group, $id_name, $id);
			if (!in_array($server_id, $return_server_id_arr)) {
				// まだ対称の配列に含まれていない場合
				$return_server_id_arr[] = $server_id;
				if ($GLOBALS['Scylla']['DB'][$db_group]['PART_NUM'] <= count($return_server_id_arr)) {
					// 全接続となった場合は抜ける
					break;
				}
			}
		}
		return $return_server_id_arr;
	}
	
	
	
	/**
	 * 対象のサーバIDを返す(リストによるパーティション用)
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	string		$id_name		キーID名
	 * @param	int			$id				キーとなるID
	 * @return	int							対応したサーバID
	 */
	protected function getServerIdByList($db_group, $id_name, $id)
	{
		$return_server_id = 1;
		// どのリストに含まれるかを決定する
		foreach ($GLOBALS['Scylla']['DB'][$db_group]['PARTITION_LIST_ARR'][$id_name] as $server_id => $list_info) {
			if (in_array($id, $list_info)) {
				// 含まれているならこれで決定
				$return_server_id = $server_id;
				break;
			}
		}
		
		return $return_server_id;
	}
	
	
	
	/**
	 * 対象のサーバIDの配列を返す(リストによるパーティション用)
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	string		$id_name		キーID名
	 * @param	int			$id_arr			キーとなるIDの配列
	 * @return	int							対応したサーバID
	 */
	protected function getServerIdArrayByList($db_group, $id_name, $id_arr)
	{
		$return_server_id_arr = array();
		foreach ($id_arr as $id) {
			$server_id = $this->getServerIdByList($db_group, $id_name, $id);
			if (!in_array($server_id, $return_server_id_arr)) {
				// まだ対称の配列に含まれていない場合
				$return_server_id_arr[] = $server_id;
				if ($GLOBALS['Scylla']['DB'][$db_group]['PART_NUM'] <= count($return_server_id_arr)) {
					// 全接続となった場合は抜ける
					break;
				}
			}
		}
		return $return_server_id_arr;
	}
	
	
	
	/**
	 * 新しいIDを生成し、サーバIDを割り当てて返す(DBテーブルによるパーティション用、AUTO_INCREMENTの場合に使用)
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	string		$table			IDテーブル名
	 * @return	int							発行されたID
	 */
	public function generateId($db_group, $table)
	{
		// 対応したDBに接続
		$partition_info_db = $this->getNode($GLOBALS['Scylla']['DB'][$db_group]['PARTITION_TABLE_ARR'][$table]['DB_GROUP'], 1, SCYLLA_SERVER_TYPE_MASTER);
		$server_id_col_name = $GLOBALS['Scylla']['DB'][$db_group]['PARTITION_TABLE_ARR'][$table]['SERVER_ID_COL'];
		
		// 割り当てるサーバIDを選択
		$server_id = $this->getNextServerId($db_group, $table);
		// 選択されたサーバIDに割り当てとしてインサート
		$partition_info_db->table($table)->insert(array($server_id_col_name => $server_id));
		$generated_id = $partition_info_db->lastInsertId();
		
		return $generated_id;
	}
	
	
	
	/**
	 * 指定されたIDにサーバIDを割り当てて返す(DBテーブルによるパーティション用、既に他でIDが発行されている場合に使用)
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	string		$table			IDテーブル名
	 * @param	int			$id				キーとなるID
	 * @return	int							対応したサーバID
	 */
	public function assignmentServerId($db_group, $table, $id)
	{
		// 対応したDBに接続
		$partition_info_db = $this->getNode($GLOBALS['Scylla']['DB'][$db_group]['PARTITION_TABLE_ARR'][$table]['DB_GROUP'], 1, SCYLLA_SERVER_TYPE_MASTER);
		$key_col_name = $GLOBALS['Scylla']['DB'][$db_group]['PARTITION_TABLE_ARR'][$table]['KEY_COL'];
		$server_id_col_name = $GLOBALS['Scylla']['DB'][$db_group]['PARTITION_TABLE_ARR'][$table]['SERVER_ID_COL'];
		
		// 割り当てるサーバIDを選択
		$server_id = $this->getNextServerId($db_group, $table);
		// 選択されたサーバIDに割り当ててインサート
		$partition_info_db->table($table)->insert(array($key_col_name => $id, $server_id_col_name => $server_id));
		
		return $server_id;
	}
	
	
	
	/**
	 * 次に割り振るサーバIDを取得する
	 * 
	 * @param	string		$db_group		データベースのグループ
	 * @param	string		$table			IDテーブル名
	 */
	protected function getNextServerId($db_group, $table)
	{
		// 対応したDBに接続
		$partition_info_db = $this->getNode($GLOBALS['Scylla']['DB'][$db_group]['PARTITION_TABLE_ARR'][$table]['DB_GROUP'], 1, SCYLLA_SERVER_TYPE_MASTER);
		$server_id_col_name = $GLOBALS['Scylla']['DB'][$db_group]['PARTITION_TABLE_ARR'][$table]['SERVER_ID_COL'];
		
		// サーバIDごと登録数を取得
		$tmp_server_id_count_info = $partition_info_db->table($table)->
			column(array('COUNT(*) AS cnt', $server_id_col_name))->
			group(array($server_id_col_name))->
			select();
		// サーバIDをキーにした配列にまとめる
		$server_id_count_info = array();
		foreach ($tmp_server_id_count_info as $count_info) {
			$server_id_count_info[$count_info[$server_id_col_name]] = $count_info['cnt'];
		}
		// 最も割り当ての少ないサーバを探す
		$target_server_id = 1;
		$min_count = PHP_INT_MAX;
		for ($i = 1; $i <= $GLOBALS['Scylla']['DB'][$db_group]['PART_NUM']; $i++) {
			if (!isset($server_id_count_info[$i])) {
				// 1つも割り振られていないサーバがあるならこれで決定
				$target_server_id = $i;
				break;
			}
			if ($min_count > $server_id_count_info[$i]) {
				// 今回のサーバの方が割り当て数が少ないならこれを候補にする
				$min_count = $server_id_count_info[$i];
				$target_server_id = $i;
			}
		}
		
		return $target_server_id;
	}
	
	
	
	/**
	 * 全DB接続のトランザクションをコミットする
	 */
	public function allCommit()
	{
		// マスタに接続している全インスタンスを取得
		$node_arr = $this->getCurrentConnectionNode(true);
		$commited_arr = array();
		// 一斉にコミット
		try {
			foreach ($node_arr as $db) {
				if ($db->isTransaction()) {
					// トランザクション中ならコミット
					$db->commit();
					$commited_arr[] = sprintf('%s:%s', $db->db_host, $db->db_name);				// コミットしたものを配列に格納
				}
			}
		} catch (Exception $e) {
			if (0 < count($commited_arr)) {
				// 1度以上コミットした後にここに来るということはデータ不整合の可能性あり
				$uncommited_arr = array();
				foreach ($node_arr as $db) {
					if ($db->isTransaction()) {
						// トランザクション中なら配列に含める
						$uncommited_arr[] = sprintf('%s:%s', $db->db_host, $db->db_name);		// コミットされなかったものを配列に格納
					}
				}
			}
			throw new Exception(sprintf('[Scylla][MultiCommitFaild] 一部コミットに失敗 データ不整合の可能性あり commited[%s] uncommited[%s] trace=%s exception_message=%s', implode(',', $commited_arr), implode(',', $uncommited_arr), $e->getTrace(), $e->getMessage()));
		}
	}
	
	
	
	/**
	 * 全DB接続のトランザクションをロールバックする
	 */
	public function allRollback()
	{
		// マスタに接続している全インスタンスを取得
		$node_arr = $this->getCurrentConnectionNode(true);
		foreach ($node_arr as $db) {
			if ($db->isTransaction()) {
				// トランザクション中ならロールバック
				$db->rollBack();
			}
		}
	}
	
	// ========================================================================
	// その他補助メソッド
	// ========================================================================
	
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
			case 'beginTransaction':
				// トランザクションをかけるメソッドの場合は明示的にマスタに接続する
				return call_user_func_array(array($this->getNode(SCYLLA_DEFAULT_DB_GROUP, 1, true), $func_name), $args);
			default:
				// デフォルトDBへそのまま投げる
				return call_user_func_array(array($this->getNode(SCYLLA_DEFAULT_DB_GROUP), $func_name), $args);
		}
	}	
}
