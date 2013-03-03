<?php
/**
 * Scylla設定サンプル
 *
 * @package		Scylla
 * @author		setsuki (yukicon)
 * @copyright	Susugi Ningyoukan
 * @license		BSD
 **/


// 指定なしで呼び出した場合に使うDBグループ
define('SCYLLA_DEFAULT_DB_GROUP', 'GROUP_1');

// DBの配列
$GLOBALS['Scylla']['DB'] = array(
	// 水平分割なし
	'GROUP_1' => array(
		'PARTITION' => false,
		'DB_NAME' => 'no_partition',
		'MASTER' => array(
			'HOST' => 'localhost',
			'PORT' => '3306',
			'USER' => 'user',
			'PASS' => 'pass',
		),
		'SLAVE_NUM' => 2,
		'SLAVE' => array(
			1 => array(
				'HOST' => 'localhost',
				'PORT' => '3306',
				'USER' => 'user',
				'PASS' => 'pass',
				'WEIGHT' => 100,
			),
			2 => array(
				'HOST' => 'localhost',
				'PORT' => '3306',
				'USER' => 'user',
				'PASS' => 'pass',
				'WEIGHT' => 50,
			),
		),
	),
	
	// テーブルによる水平分割
	'GROUP_2' => array(
		'PARTITION' => true,
		// 専用の設定 ----------
		'PARTITION_TYPE' => SCYLLA_PARTITION_TYPE_DB_TABLE,
		'PARTITION_TABLE_ARR' => array(
			'user_partition_table' => array(
				'DB_GROUP' => 'GROUP_1',
				'KEY_COL' => 'user_id',
				'SERVER_ID_COL' => 'server_id',
			),
			'log_partition_table' => array(
				'DB_GROUP' => 'GROUP_1',
				'KEY_COL' => 'log_id',
				'SERVER_ID_COL' => 'server_id',
			),
		),
		// 専用の設定ここまで ----------
		'DB_NAME' => 'table_partition',
		'PART_NUM' => 2,
		'PART_ARR' => array(
			1 => array(
				'MASTER' => array(
					'HOST' => 'localhost',
					'PORT' => '3306',
					'USER' => 'user',
					'PASS' => 'pass',
				),
				'SLAVE_NUM' => 2,
				'SLAVE' => array(
					1 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 100,
					),
					2 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 50,
					),
				),
			),
			2 => array(
				'MASTER' => array(
					'HOST' => 'localhost',
					'PORT' => '3306',
					'USER' => 'user',
					'PASS' => 'pass',
				),
				'SLAVE_NUM' => 2,
				'SLAVE' => array(
					1 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 100,
					),
					2 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 50,
					),
				),
			),
		),
	),
	
	// 剰余による水平分割
	'GROUP_3' => array(
		'PARTITION' => true,
		// 専用の設定 ----------
		'PARTITION_TYPE' => SCYLLA_PARTITION_TYPE_MOD,
		// 専用の設定ここまで ----------
		'DB_NAME' => 'mod_partition',
		'PART_NUM' => 2,
		'PART_ARR' => array(
			1 => array(
				'MASTER' => array(
					'HOST' => 'localhost',
					'PORT' => '3306',
					'USER' => 'user',
					'PASS' => 'pass',
				),
				'SLAVE_NUM' => 2,
				'SLAVE' => array(
					1 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 100,
					),
					2 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 50,
					),
				),
			),
			2 => array(
				'MASTER' => array(
					'HOST' => 'localhost',
					'PORT' => '3306',
					'USER' => 'user',
					'PASS' => 'pass',
				),
				'SLAVE_NUM' => 2,
				'SLAVE' => array(
					1 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 100,
					),
					2 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 50,
					),
				),
			),
		),
	),
	
	// 範囲による水平分割
	'GROUP_4' => array(
		'PARTITION' => true,
		// 専用の設定 ----------
		'PARTITION_TYPE' => SCYLLA_PARTITION_TYPE_RANGE,
		'PARTITION_RANGE_ARR' => array(
			'user_id' => array(
				1 => array(
					'MAX' => 30000,
				),
				2 => array(
					'MIN' => 30001,
					'MAX' => 60000,
				),
				3 => array(
					'MIN' => 60001,
				),
			),
			'log_id' => array(
				1 => array(
					'MAX' => 30000,
				),
				2 => array(
					'MIN' => 30001,
					'MAX' => 60000,
				),
				3 => array(
					'MIN' => 60001,
				),
			),
		),
		// 専用の設定ここまで ----------
		'DB_NAME' => 'range_partition',
		'PART_NUM' => 3,
		'PART_ARR' => array(
			1 => array(
				'MASTER' => array(
					'HOST' => 'localhost',
					'PORT' => '3306',
					'USER' => 'user',
					'PASS' => 'pass',
				),
				'SLAVE_NUM' => 2,
				'SLAVE' => array(
					1 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 100,
					),
					2 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 50,
					),
				),
			),
			2 => array(
				'MASTER' => array(
					'HOST' => 'localhost',
					'PORT' => '3306',
					'USER' => 'user',
					'PASS' => 'pass',
				),
				'SLAVE_NUM' => 2,
				'SLAVE' => array(
					1 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 100,
					),
					2 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 50,
					),
				),
			),
			3 => array(
				'MASTER' => array(
					'HOST' => 'localhost',
					'PORT' => '3306',
					'USER' => 'user',
					'PASS' => 'pass',
				),
				'SLAVE_NUM' => 2,
				'SLAVE' => array(
					1 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 100,
					),
					2 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 50,
					),
				),
			),
		),
	),
	
	// リストによる水平分割
	'GROUP_5' => array(
		'PARTITION' => true,
		// 専用の設定 ----------
		'PARTITION_TYPE' => SCYLLA_PARTITION_TYPE_LIST,
		'PARTITION_LIST_ARR' => array(
			'user_type' => array(
				1 => array(1, 2, 3),
				2 => array(4, 5, 6),
			),
			'log_type' => array(
				1 => array(1, 2, 3),
				2 => array(4, 5, 6),
			),
		),
		// 専用の設定ここまで ----------
		'DB_NAME' => 'range_partition',
		'PART_NUM' => 2,
		'PART_ARR' => array(
			1 => array(
				'MASTER' => array(
					'HOST' => 'localhost',
					'PORT' => '3306',
					'USER' => 'user',
					'PASS' => 'pass',
				),
				'SLAVE_NUM' => 2,
				'SLAVE' => array(
					1 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 100,
					),
					2 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 50,
					),
				),
			),
			2 => array(
				'MASTER' => array(
					'HOST' => 'localhost',
					'PORT' => '3306',
					'USER' => 'user',
					'PASS' => 'pass',
				),
				'SLAVE_NUM' => 2,
				'SLAVE' => array(
					1 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 100,
					),
					2 => array(
						'HOST' => 'localhost',
						'PORT' => '3306',
						'USER' => 'user',
						'PASS' => 'pass',
						'WEIGHT' => 50,
					),
				),
			),
		),
	),
);

// PDOの設定
$GLOBALS['Scylla']['PDO_SETTING'] = array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
);
