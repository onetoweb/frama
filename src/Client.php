<?php

namespace Onetoweb\Frama;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;
use Onetoweb\Frama\Token;
use DateTime;

/**
 * Frama Api Client.
 * 
 * @author Jonathan van 't Ende <jvantende@onetoweb.nl>
 * @copyright Onetoweb. B.V.
 * 
 * @link https://developer.frama.nl/parcel/en/default.aspx
 */
class Client
{
    const VERSION = 3.0;
    
    /**
     * Methods.
     */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_DELETE = 'DELETE';
    
    /**
     * @var string
     */
    private $username;
    
    /**
     * @var string
     */
    private $password;
    
    /**
     * @var bool
     */
    private $testModus;
    
    /**
     * @var float
     */
    private $version;
    
    /**
     * @var Token
     */
    private $token;
    
    /**
     * @var callable 
     */
    private $updateTokenCallback;
    
    /**
     * @param string $username
     * @param string $password
     * @param bool $testModus = false
     * @param float $version = self::VERSION
     */
    public function __construct(string $username, string $password, bool $testModus = false, float $version = self::VERSION)
    {
        $this->username = $username;
        $this->password = $password;
        $this->testModus = $testModus;
        $this->version = $version;
    }
    
    /**
     * @return void
     */
    public function requestToken(): void
    {
        $response = $this->request(self::METHOD_GET, 'login');
        
        // set expires
        $expires = new Datetime();
        $expires->setTimestamp(time() + $response['expiresIn']);
        
        // set token
        $token = new Token($response['accessToken'], $expires);
        $this->setToken($token);
        
        // token update callback
        ($this->updateTokenCallback)($this->getToken());
    }
    
    /**
     * @param callable $updateTokenCallback
     */
    public function setUpdateTokenCallback(callable $updateTokenCallback): void
    {
        $this->updateTokenCallback = $updateTokenCallback;
    }
    
    /**
     * @param Token $token
     *
     * @return void
     */
    public function setToken(Token $token): void
    {
        $this->token = $token;
    }
    
    /**
     * @return Token
     */
    public function getToken(): ?Token
    {
        return $this->token;
    }
    
    /**
     * @param string $method
     * @param string $endpoint
     * @param array $data = []
     * @param array $query = []
     * 
     * @return array|null
     */
    public function request(string $method, string $endpoint, array $data = [], array $query = []): ?array
    {
        // build options
        $options[RequestOptions::HTTP_ERRORS] = false;
        
        // build request haders
        $headers = [
            'Cache-Control' => 'no-cache',
            'Connection' => 'close',
            'Accept' => 'application/json',
            'Api-Version' => $this->version
        ];
        
        // check token
        if ($endpoint != 'login') {
            
            if ($this->getToken() === null or $this->getToken()->isExpired()) {
                
                // request token
                $this->requestToken();
            }
            
            // add bearer token authorization header
            $headers['Authorization'] = "Bearer {$this->getToken()}";
            
        } else {
            
            // add authorization
            $options[RequestOptions::AUTH] = [
                $this->username,
                $this->password
            ];
        }
        
        //  add headers to request options
        $options[RequestOptions::HEADERS] = $headers;
        
        // add post data body
        if (count($data) > 0) {
            
            $options[RequestOptions::JSON] = $data;
            
        }
        
        // build query
        if (count($query) > 0) {
            $endpoint .= '?' . http_build_query($query);
        }
        
        // build guzzle client
        $guzzleClient = new GuzzleClient([
            'base_uri' => ($this->testModus ? 'https://sandbox.frama.nl' : 'https://restapi.frama.nl'),
        ]);
        
        // build guzzle request
        $result = $guzzleClient->request($method, $endpoint, $options);
        
        // get contents
        $contents = $result->getBody()->getContents();
        
        // return data
        return json_decode($contents, true);
    }
    
    /**
     * @param array $data = []
     *
     * @return array|null
     */
    public function createShipments(array $data): ?array
    {
        return $this->request(self::METHOD_POST, 'parcel/shipment', $data);
    }
    
    /**
     * @param array $data = []
     *
     * @return array|null
     */
    public function deleteShipments(array $data): ?array
    {
        return $this->request(self::METHOD_DELETE, 'parcel/shipment', $data);
    }
    
    /**
     * @param array $data = []
     *
     * @return array|null
     */
    public function getLabels(array $data): ?array
    {
        return $this->request(self::METHOD_POST, '/parcel/label', $data);
    }
}