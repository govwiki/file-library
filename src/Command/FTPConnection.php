<?php

namespace App\Command;

/**
 * Class FTPConnection
 *
 * @package App\Command
 */
class FTPConnection
{

    /**
     * @var resource
     */
    private $connection;

    /**
     * FTPConnectionPool constructor.
     *
     * @param string      $host     FTP host.
     * @param string|null $user     Username used for authentication.
     * @param string|null $password Password used for authentication.
     * @param integer     $port     FTP port.
     */
    public function __construct(
        string $host,
        string $user = null,
        string $password = null,
        int $port = 21
    ) {
        $ftpConn = \ftp_connect($host, $port);
        if (! \is_resource($ftpConn)) {
            throw new \LogicException(\error_get_last()['message']);
        }

        if ((($user !== null) || ($password !== null)) && ! \ftp_login($ftpConn, $user, $password)) {
            throw new \LogicException('Invalid username or password');
        }

        \ftp_pasv($ftpConn, true);

        $this->connection = $ftpConn;
    }

    /**
     * FTPConnectionPool destructor.
     */
    public function __destruct()
    {
        \ftp_close($this->connection);
    }

    /**
     * @param string $path Requested file.
     *
     * @return resource
     */
    public function getFile(string $path)
    {
        $file = \fopen('php://memory', 'r+b');

        if (! \ftp_fget($this->connection, $file, $path, \FTP_BINARY) || ! \is_resource($file)) {
            throw new \RuntimeException(\sprintf('Can\'t get file "%s"', $path));
        }

        return $file;
    }

    /**
     * @param string $path A path to required directory.
     *
     * @return string[]
     */
    public function listFiles(string $path): array
    {
        return \ftp_nlist($this->connection, $path);
    }
}
