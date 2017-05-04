<?php

namespace Srmklive\SoundCloud\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException as HttpBadResponseException;
use GuzzleHttp\Exception\ClientException as HttpClientException;
use GuzzleHttp\Exception\ServerException as HttpServerException;
use Illuminate\Support\Collection;

class SoundCloud
{
    /**
     * SoundCloud App Client ID.
     *
     * @var string
     */
    private $clientId;

    /**
     * SoundCloud App Client Secret.
     *
     * @var string
     */
    private $clientSecret;

    /**
     * Redirect URL as set in SoundCloud App.
     *
     * @var string
     */
    private $redirectUrl;

    /**
     * HTTP API Client.
     *
     * @var \GuzzleHttp\Client
     */
    private $httpClient;

    /**
     * List of headers to be passed to each HTTP request.
     *
     * @var array
     */
    private $httpHeaders;

    /**
     * HTTP Request packet.
     *
     * @var \Illuminate\Support\Collection
     */
    private $httpRequest;

    /**
     * API Request URL.
     *
     * @var string
     */
    protected $httpRequestUrl;

    /**
     * SoundCloud constructor.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUrl
     */
    public function __construct($clientId, $clientSecret, $redirectUrl)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUrl = $redirectUrl;

        $this->httpClient = new HttpClient;
        $this->httpHeaders['Accept'] = 'application/json';
    }

    /**
     * Get Authorization URL to connect to a user's SoundCloud account.
     *
     * @return string
     */
    public function getAuthorizeUrl()
    {
        $this->buildHttpRequest(
            [
                'scope'         => 'non-expiring',
                'display'       => 'popup',
                'response_type' => 'code',
            ],
            ['client_secret']
        );

        $this->buildHttpRequestUrl('connect');

        return $this->httpRequestUrl;
    }

    /**
     * Login into user's SoundCloud account through username & password.
     *
     * @param string $username
     * @param string $password
     *
     * @return mixed
     */
    public function loginUsingCredentials($username, $password)
    {
        $this->buildHttpRequest([
            'username'      =>  $username,
            'password'      =>  $password,
            'grant_type'    =>  'password',
        ], ['redirect_uri']);

        $this->buildHttpRequestUrl('oauth2/token');

        return $this->doHttpRequest('post');
    }

    /**
     *
     *
     * @param string $type
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function doHttpRequest($type)
    {
        $bodyParam = ($type == 'get') ? 'query' : 'form_params';

        $options = [
            $bodyParam => $this->httpRequest->toArray()
        ];

        if (!empty($this->headers)) {
            $options['headers'] = $this->headers;
        }

        try {
            $response = $this->httpClient->$type(
                $this->httpRequestUrl,
                $options
            )->getBody();

            return \GuzzleHttp\json_decode($response, true);
        } catch (HttpClientException $e) {
            throw new \Exception($e->getMessage());
        } catch (HttpServerException $e) {
            throw new \Exception($e->getMessage());
        } catch (HttpBadResponseException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Build Http Request packet.
     *
     * @param array $request
     * @param array $skip
     *
     * @return void
     */
    protected function buildHttpRequest($request, $skip = [])
    {
        $this->httpRequest = new Collection([
            'client_id'         =>  $this->clientId,
            'client_secret'     =>  $this->clientSecret,
            'redirect_uri'      =>  $this->redirectUrl,
        ])->merge($request)->except($skip);
    }

    /**
     * Create SoundCloud API URI.
     *
     * @param string $path
     */
    protected function buildHttpRequestUrl($path)
    {
        $url = 'https://';
        $url .= (!preg_match('/connect/', $path)) ? 'api.' : '';
        $url .= 'soundcloud.com/'.$path;

        if (preg_match('/connect/', $path)) {
            $url .= !($this->httpRequest->isEmpty()) ? '?' . http_build_query(
                    $this->httpRequest->toArray()
                ) : '';
        }

        $this->httpRequestUrl = $url;
        unset($url);
    }
}
