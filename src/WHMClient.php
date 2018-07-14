<?php

namespace PreviewTechs\cPanelWHM;


use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle6\Client;
use Http\Client\HttpClient;
use PreviewTechs\cPanelWHM\Exceptions\ClientExceptions;

class WHMClient
{
    /**
     * @var string
     */
    protected $whmUser;
    /**
     * @var string
     */
    protected $apiToken;

    /**
     * @var string
     */
    protected $whmHost;

    /**
     * @var int
     */
    protected $whmPort;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * Client constructor.
     *
     * @param string $whmUser
     * @param $apiToken
     * @param $whmHost
     * @param int $whmPort
     */
    public function __construct($whmUser = "root", $apiToken, $whmHost, $whmPort = 2087)
    {
        $this->whmUser  = $whmUser;
        $this->apiToken = $apiToken;
        $this->whmHost  = $whmHost;
        $this->whmPort  = $whmPort;

        $client = Client::createWithConfig(['timeout' => 15]);
        $this->setHttpClient($client);
        $this->httpClient = $this->getHttpClient();
    }

    /**
     * @param HttpClient $client
     *
     * @return WHMClient
     */
    public function setHttpClient(HttpClient $client)
    {
        $this->httpClient = $client;

        return $this;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param $endpoint
     * @param $method
     * @param array $params
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws ClientExceptions
     * @throws \Http\Client\Exception
     */
    public function sendRequest($endpoint, $method, array $params = [])
    {
        $queryParams = http_build_query($params);
        $url         = sprintf("https://%s:%s%s", $this->whmHost, $this->whmPort, $endpoint);

        $request = new Request($method, $url . "?" . $queryParams);
        $request = $request->withHeader("Authorization", "whm {$this->whmUser}:{$this->apiToken}");

        $response = $this->httpClient->sendRequest($request);

        $data = [];
        if (strpos($response->getHeaderLine("Content-Type"), "application/json") === 0) {
            $data = json_decode((string)$response->getBody(), true);
        } elseif (strpos($response->getHeaderLine("Content-Type"), "text/plain") === 0) {
            $data = json_decode((string)$response->getBody(), true);
        }

        if ($response->getStatusCode() === 403) {
            if ( ! empty($data['cpanelresult']['error'])) {
                throw ClientExceptions::accessDenied($data['cpanelresult']['error'],
                    $data['cpanelresult']['data']['reason']);
            }
        }

        return $data;
    }
}