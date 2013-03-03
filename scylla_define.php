<?php
/**
 * DBコネクション管理クラスScylla関係定義ファイル
 *
 * @package		Scylla
 * @author		setsuki (yukicon)
 * @copyright	Susugi Ningyoukan
 * @license		BSD
 **/


define('SCYLLA_SERVER_TYPE_MASTER', 1);						// マスタサーバ
define('SCYLLA_SERVER_TYPE_SLAVE', 2);						// スレーブサーバ

// 分割種別
define('SCYLLA_PARTITION_TYPE_DB_TABLE', 1);				// DBテーブル
define('SCYLLA_PARTITION_TYPE_MOD', 2);						// 剰余
define('SCYLLA_PARTITION_TYPE_RANGE', 3);					// 範囲
define('SCYLLA_PARTITION_TYPE_LIST', 4);					// リスト

