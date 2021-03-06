<?php

namespace Royalcms\Component\Database\Connectors;

use Royalcms\Component\Support\Arr;
use Royalcms\Component\Database\Connectors\Connector;
use Royalcms\Component\Database\Connectors\ConnectorInterface;
use Royalcms\Component\Database\CoroutineMySQL;
use Royalcms\Component\Support\Str;

class CoroutineMySQLConnector extends Connector implements ConnectorInterface
{
    /**
     * @param string $dsn
     * @param array $config
     * @param array $options
     * @return CoroutineMySQL
     * @throws \Throwable
     */
    public function createConnection($dsn, array $config, array $options)
    {
        try {
            $mysql = $this->connect($config);
        } catch (\Exception $e) {
            $mysql = $this->_tryAgainIfCausedByLostConnection($e, $config);
        }

        return $mysql;
    }

    /**
     * @param \Throwable $e
     * @param array $config
     * @return CoroutineMySQL
     * @throws \Throwable
     */
    protected function _tryAgainIfCausedByLostConnection($e, array $config)
    {
        if ($this->causedByLostConnection($e) || Str::contains($e->getMessage(), 'is closed')) {
            return $this->connect($config);
        }
        throw $e;
    }

    /**
     * @param array $config
     * @return CoroutineMySQL
     */
    public function connect(array $config)
    {
        $connection = new CoroutineMySQL();
        $connection->connect([
            'host'        => Arr::get($config, 'host', '127.0.0.1'),
            'port'        => Arr::get($config, 'port', 3306),
            'user'        => Arr::get($config, 'username', 'root'),
            'password'    => Arr::get($config, 'password', 'root'),
            'database'    => Arr::get($config, 'database', 'test'),
            'timeout'     => Arr::get($config, 'timeout', 5),
            'charset'     => Arr::get($config, 'charset', 'utf8mb4'),
            'strict_type' => Arr::get($config, 'strict', false),
        ]);
        if (isset($config['timezone'])) {
            $connection->prepare('set time_zone="' . $config['timezone'] . '"')->execute();
        }
        if (isset($config['strict'])) {
            if ($config['strict']) {
                $connection->prepare("set session sql_mode='STRICT_ALL_TABLES,ANSI_QUOTES'")->execute();
            } else {
                $connection->prepare("set session sql_mode='ANSI_QUOTES'")->execute();
            }
        }
        return $connection;
    }
}