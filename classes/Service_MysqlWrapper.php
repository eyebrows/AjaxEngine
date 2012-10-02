<?php
//Should be used as a wrapper for all calls to mysql_query, so logging can be done if needs be
class Service_MysqlWrapper {

	public function query($sql) {
		$return = mysql_query($sql);
		$debug = ServiceManager::getService('debug');
		$debug->logSql($sql);
		return $return;
	}
}
?>