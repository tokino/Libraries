<?php

/**
 * DB操作系クラス
 *
 * Class DbProvider
 *
 * @author kino
 * @create 2016/01/27
 */
class DbProvider
{
	private $dsn;
	private $username;
	private $password;

	/**
	 * @var PDO $dbh Database connection
	 */
	private $dbh;

	/**
	 * DbProvider constructor.
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$this->dsn = sprintf('%s:dbname=%s host=%s port=%s',
			$config['db_type'],
			$config['db_name'],
			$config['db_host'],
			$config['db_port']
		);
		$this->username = $config['db_user'];
		$this->password = $config['db_pass'];
	}

	/**
	 * DBコネクション
	 * @throws Exception
	 */
	public function connect()
	{
		try {
			$this->dbh = new PDO($this->dsn, $this->username, $this->password);
		} catch(PDOException $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * コネクションクローズ
	 */
	public function close()
	{
		$this->dbh = null;
	}

	/**
	 * @param string $sql クエリ
	 * @param array $params クエリパラメーター
	 * @param bool $isDebug デバッグモード
	 * @return array
	 */
	public function sql($sql, array $params, $isDebug = false)
	{
		$sth = $this->dbh->prepare($sql);
		$sth->execute($params);

		if ($isDebug) {
			print_r($sth->errorInfo());
			$sth->debugDumpParams();
		}

		$res = $sth->fetchAll();
		if (count($res) == false) {
			return null;
		}

		return $res;
	}
}