<?php

namespace App\Command;

use GuzzleHttp\Psr7\Stream;
use Sabre\DAV\Client;
use Sabre\HTTP\Request;

/**
 * Class WebDavConnection
 *
 * @package App\Command
 */
class WebDavConnection
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $basePath;

    /**
     * FTPConnectionPool constructor.
     *
     * @param string      $url      WebDav url.
     * @param string      $basePath Base path to synced folder.
     * @param string|null $user     Username used for authentication.
     * @param string|null $password Password used for authentication.
     */
    public function __construct(
        string $url,
        string $basePath = '/',
        string $user = null,
        string $password = null
    ) {
       $settings = [
           'baseUri' => $url,
           'authType' => Client::AUTH_BASIC,
           'encoding' => Client::ENCODING_GZIP | Client::ENCODING_DEFLATE,
       ];

       if ($user !== null) {
           $settings['userName'] = $user;
       }

       if ($password !== null) {
           $settings['password'] = $password;
       }

       $this->client = new Client($settings);
       $this->basePath = \rtrim($basePath, '/');
    }

    /**
     * @param string $path Path to fetched file.
     *
     * @return Stream
     */
    public function getFile(string $path): Stream
    {
        //
        // For some reasons low level http request sending code don't do it for us,
        // so we should replace all spaces symbols with they code.
        //
        // We got 404 if we don't do it.
        //
        $absPath = \str_replace(' ', '%20', $this->client->getAbsoluteUrl($this->buildPath($path)));
        $response = $this->client->send(new Request('GET', $absPath));

        return new Stream($response->getBodyAsStream());
    }

    /**
     * @param string $path A path to required directory.
     *
     * @return string[]
     */
    public function listFiles(string $path): array
    {
        $answer = $this->client->propFind($this->buildPath($path), [
            '{DAV:}displayname',
            '{DAV:}iscollection'
        ], 1);

        //
        // Remove first element which is requested dir.
        //
        \array_shift($answer);

        $files = [];

        foreach ($answer as $name => $props) {
            $fileName = \basename($name);

            if (($fileName[0] !== '.') && ((int) $props['{DAV:}iscollection'] === 0)) {
                $files[] = \str_replace($this->basePath, '', \urldecode($name));
            }
        }

        return $files;
    }

    /**
     * @param string $relativePath Relative path to file.
     *
     * @return string
     */
    private function buildPath(string $relativePath): string
    {
        return $this->basePath .'/' . \trim($relativePath, '/');
    }
}
