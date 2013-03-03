<?php
/**
 * DBコネクション管理クラスScylla継承サンプル
 *
 * @package		Scylla
 * @author		setsuki (yukicon)
 * @copyright	Susugi Ningyoukan
 * @license		BSD
 **/

class SampleScylla extends Scylla
{
	const NODE_OBJECT_NAME = 'SampleOrthros';				// ノードとなるクラス名
	
	// ========================================================================
	// GROUP_1(水平分割なし)
	// ========================================================================
	public function group_1()
	{
		return $this->getNode('GROUP_1');
	}
	
	
	
	public function group_1_master()
	{
		return $this->getNode('GROUP_1', 1, true);
	}
	
	
	
	public function group_1_slave()
	{
		return $this->getNode('GROUP_1', 1, false);
	}
	
	
	
	public function group_1_begin()
	{
		return $this->getNode('GROUP_1', 1, true)->beginTransaction();
	}
	
	
	// ========================================================================
	// GROUP_2(テーブルによる水平分割)
	// ========================================================================
	
	public function group_2_user($user_id)
	{
		$server_id = $this->getServerIdByTable('GROUP_2', 'user_partition_table', $user_id);
		return $this->getNode('GROUP_2', $server_id);
	}
	
	
	
	public function group_2_user_multi($user_id_arr)
	{
		$server_id_arr = $this->getServerIdArrayByTable('GROUP_2', 'user_partition_table', $user_id_arr);
		return new ScyllaLegs($this->getPartitionDBNodeArray('GROUP_2', $server_id_arr));
	}
	
	
	
	public function group_2_user_all()
	{
		$server_id_arr = $this->getServerIdArrayByTable('GROUP_2', 'user_partition_table', array());
		return new ScyllaLegs($this->getPartitionDBNodeArray('GROUP_2', $server_id_arr));
	}
	
	
	
	public function group_2_log($log_id)
	{
		$server_id = $this->getServerIdByTable('GROUP_2', 'log_partition_table', $log_id);
		return $this->getNode('GROUP_2', $server_id);
	}
	
	
	
	public function generateUserId()
	{
		return $this->generateId('GROUP_2', 'user_partition_table');
	}
	
	
	// ========================================================================
	// GROUP_3(剰余による水平分割)
	// ========================================================================
	
	public function group_3($id)
	{
		$server_id = $this->getServerIdByMod('GROUP_3', $id);
		return $this->getNode('GROUP_3', $server_id);
	}
	
	
	
	public function group_3_multi($id_arr)
	{
		$server_id_arr = $this->getServerIdArrayByMod('GROUP_3', $id_arr);
		return new ScyllaLegs($this->getPartitionDBNodeArray('GROUP_3', $server_id_arr));
	}
	
	
	
	public function group_3_all()
	{
		$server_id_arr = $this->getServerIdArrayByMod('GROUP_3', array());
		return new ScyllaLegs($this->getPartitionDBNodeArray('GROUP_3', $server_id_arr));
	}
	
	
	// ========================================================================
	// GROUP_4(範囲による水平分割)
	// ========================================================================
	
	public function group_4_user($user_id)
	{
		$server_id = $this->getServerIdByRange('GROUP_4', 'user_id', $user_id);
		return $this->getNode('GROUP_4', $server_id);
	}
	
	
	
	public function group_4_user_multi($user_id_arr)
	{
		$server_id_arr = $this->getServerIdArrayByRange('GROUP_4', 'user_id', $user_id_arr);
		return new ScyllaLegs($this->getPartitionDBNodeArray('GROUP_4', $server_id_arr));
	}
	
	
	
	public function group_4_user_all()
	{
		$server_id_arr = $this->getServerIdArrayByRange('GROUP_4', 'user_id', array());
		return new ScyllaLegs($this->getPartitionDBNodeArray('GROUP_4', $server_id_arr));
	}
	
	
	
	// ========================================================================
	// GROUP_5(リストによる水平分割)
	// ========================================================================
	
	public function group_5_user($user_type)
	{
		$server_id = $this->getServerIdByList('GROUP_5', 'user_type', $user_type);
		return $this->getNode('GROUP_5', $server_id);
	}
	
	
	
	public function group_5_user_multi($user_type_arr)
	{
		$server_id_arr = $this->getServerIdArrayByList('GROUP_5', 'user_type', $user_type_arr);
		return new ScyllaLegs($this->getPartitionDBNodeArray('GROUP_5', $server_id_arr));
	}
	
	
	
	public function group_5_user_all()
	{
		$server_id_arr = $this->getServerIdArrayByList('GROUP_5', 'user_type', array());
		return new ScyllaLegs($this->getPartitionDBNodeArray('GROUP_5', $server_id_arr));
	}
}
