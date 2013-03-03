
◆◇◆◇◆◇◆◇◆◇◆◇◆◇◆◇◆◇◆
　　　　　Scylla
◆◇◆◇◆◇◆◇◆◇◆◇◆◇◆◇◆◇◆

■----------------------------■
　　　　　概要
■----------------------------■
Scylla(スキュラ)はマスタ分割に対応したDB接続管理ライブラリです。
設定ファイル次第で、マスタスレーブ構成、垂直分割、水平分割を扱う亊ができます。


■----------------------------■
　　　　　使い方
■----------------------------■
PDOを使っています。
必ず事前にインストールを済ませてください。

データノードとしてORマッパOrthrosを使っています。
Scyllaを使用する場合はこちらも落としてください。
https://github.com/setsuki/Orthros

-- はじめに
ScyllaのあるディレクトリにOrthrosを設置してください。
  scylla/orthros/Orthros.php

sampleディレクトリにサンプル設定ファイルがあるので参考にして、
設定ファイルを用意してください。

設定ファイルとScylla.php をrequireなりincludeなりしてください。
  require('./scylla_setting.php');
  require('./scylla/Scylla.php');

-- singletonでインスタンスを生成
  $db = Scylla::singleton();

-- 水平分割されていないDBにアクセス
  $data1 = $db->getNode('GROUP_1')->table('ex_tbl')->select();

-- 水平分割されたDBにアクセス
  $server_id = $this->getServerIdByTable('GROUP_2', 'user_partition_table', $user_id);
  $data2 = $this->getNode('GROUP_2', $server_id)->table('ex_tbl')->select();

-- 水平分割された複数のDBにアクセス
  $server_id_arr = $this->getServerIdArrayByTable('GROUP_2', 'user_partition_table', $user_id_arr);
  $db_multi = new ScyllaLegs($this->getPartitionDBNodeArray('GROUP_2', $server_id_arr));
  $data3 = $db_multi->table('ex_tbl')->select();

getNodeでOrthrosオブジェクトが返ってくるので、あとの使い方はOrthrosに準拠します。

そのままでも使えなくはないですが、
特にマスタ分割を行う場合はScyllaを継承したクラスを立てると扱いやすくなると思います。
これもサンプルがあるので参考にしてください。



■----------------------------■
　　　　　作者
■----------------------------■
setsuki とか yukicon とか Yuki Susugi とか名乗ってますが同じ人です。
https://github.com/setsuki
https://twitter.com/yukiconEx



■----------------------------■
　　　　　ライセンス
■----------------------------■
修正BSDライセンスです。
著作権表示さえしてくれれば好きに扱ってくれて構いません。
ただし無保証です。


