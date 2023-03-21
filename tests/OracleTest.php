<?php

namespace mradang\Oracle\Test;

use mradang\Oracle\Oracle;
use PHPUnit\Framework\TestCase;

/**
 * @covers Oracle
 */
class OracleTest extends TestCase
{
    public function testBasicFeatures()
    {
        $config = [
            'host' => $_ENV['DB_HOST'],
            'port' => $_ENV['DB_PORT'],
            'database' => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
            'charset' => $_ENV['DB_CHARSET'],
        ];
        $db = new Oracle($config);

        $sql = "select * from scott.emp";
        $ret = $db->fetch($sql);
        $this->assertGreaterThan(0, count($ret));

        $sql = "select * from scott.emp where job = :job";
        $ret = $db->fetch($sql, ['job' => 'CLERK']);
        $this->assertGreaterThan(0, count($ret));
        $this->assertEquals('CLERK', reset($ret)['JOB'] ?? '');

        $sql = "delete scott.emp where job = :job";
        $ret = $db->execute($sql, ['job' => 'abc']);
        $this->assertTrue($ret);

        $sql = "select * from scott.emp";
        $ret = $db->pagination($sql, [], 2, 1);
        $this->assertEquals(1, count($ret));
        $this->assertEquals(2, reset($ret)['RN']);

        $ret = $db->pagination($sql, [], 2, 2);
        $this->assertEquals(2, count($ret));
        $this->assertEquals(3, reset($ret)['RN']);

        $sql = "select * from scott.emp where job = :job";
        $ret = $db->count($sql, ['job' => 'CLERK']);
        $this->assertGreaterThan(0, $ret);
    }
}
