<?php

namespace mradang\Oracle;

class Oracle
{
    private $conn;

    /**
     * host: 主机
     * port: 端口
     * database: 数据库名
     * username: 用户名
     * password: 密码
     * charset: 字符集
     */
    public function __construct(array $config)
    {
        $fields = ['host', 'port', 'database', 'username', 'password', 'charset'];
        extract($this->array_only($config, $fields));

        $connection_string = sprintf('//%s:%s/%s', $host, $port, $database);
        $this->conn = oci_connect($username, $password, $connection_string, $charset ?: 'utf8');
        oci_execute(oci_parse($this->conn, "ALTER SESSION SET NLS_DATE_FORMAT = 'yyyy-mm-dd hh24:mi:ss'"));
    }

    /**
     * 获取查询结果
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetch(string $sql, array $params = []): array
    {
        $stmt = oci_parse($this->conn, $sql);
        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, ":$key", $value);
        }
        $n = null;
        if (oci_execute($stmt)) {
            $n = oci_fetch_all($stmt, $results, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
        }
        oci_free_statement($stmt);
        if ($n) {
            $results = json_decode(json_encode($results, JSON_NUMERIC_CHECK + JSON_UNESCAPED_UNICODE), true);
            return $results;
        } else {
            return [];
        }
    }

    /**
     * 执行查询
     *
     * @param string $sql
     * @param array $params
     * @return bool
     */
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = oci_parse($this->conn, $sql);
        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, ":$key", $value);
        }
        $ret = oci_execute($stmt);
        oci_free_statement($stmt);
        return $ret;
    }

    /**
     * 分页查询
     *
     * @param string $sql
     * @param array $params
     * @param integer $page
     * @param integer $pagesize
     * @return array
     */
    public function pagination(string $sql, array $params, int $page, int $pagesize): array
    {
        $pagination_sql = "select * from (";
        $pagination_sql .= "select rownum rn, e.* from ($sql) e";
        $pagination_sql .= " where rownum <= " . ($page * $pagesize);
        $pagination_sql .= ") t";
        $pagination_sql .= " where t.rn > " . (($page - 1) * $pagesize);
        return $this->fetch($pagination_sql, $params);
    }

    /**
     * 统计行数
     *
     * @param string $sql
     * @param array $params
     * @return integer
     */
    public function count(string $sql, array $params = []): int
    {
        $count_sql = "select count(*) t from ($sql)";
        $ret = $this->fetch($count_sql, $params);
        $first_row = reset($ret);
        $t = reset($first_row);
        return $t ? intval($t) : 0;
    }

    private function array_only(array $input, array $keys)
    {
        return array_intersect_key($input, array_flip($keys));
    }
}
