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
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    
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
     */
    public function __construct(string $username, string $password, bool $testModus = false)
    {
        $this->username = $username;
        $this->password = $password;
        $this->testModus = $testModus;
    }
    
    /**
     * @return void
     */
    public function requestToken(): void
    {
        $token = $this->request(self::METHOD_GET, '/api/v2/Login');
        
        // set expires
        $expires = new Datetime();
        $expires->setTimestamp(time() + Token::EXPIRES_IN);
        
        // set token
        $token = new Token($token['token'], $expires);
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
     * @param string $method = self::METHOD_GET
     * @param string $endpoint
     * @param array $data = []
     * @param array $query = []
     * @param bool $json = true
     * 
     * @return mixed array|string|null
     */
    public function request(string $method = self::METHOD_GET, string $endpoint, array $data = [], array $query = [], bool $json = true)
    {
        // build request haders
        $headers = [
            'Cache-Control' => 'no-cache',
            'Connection' => 'close',
            'Accept' => $json ? 'application/json' : 'application/pdf',
        ];
        
        // check token
        if ($endpoint !== '/api/v2/Login') {
            
            if ($this->getToken() === null or $this->getToken()->isExpired()) {
                
                // request token
                $this->requestToken();
                
            }
            
            // add bearer token authorization header
            $headers['Authorization'] = "Bearer {$this->getToken()}";
            
        } else {
            
            // add basic authorization header
            $headers['Authorization'] = 'Basic ' . base64_encode($this->username . ':' . $this->password);
            
        }
        
        //  add headers to request options
        $options[RequestOptions::HEADERS] = $headers;
        
        // add post data body
        if (in_array($method, [self::METHOD_POST])) {
            
            $options[RequestOptions::JSON] = $data;
            
        }
        
        // build query
        if (count($query) > 0) {
            $endpoint .= '?' . http_build_query($query);
        }
        
        // build guzzle client
        $guzzleClient = new GuzzleClient([
            'base_uri' => ($this->testModus ? 'https://resttest.frama.nl' : 'https://rest.frama.nl')
        ]);
        
        // build guzzle request
        $result = $guzzleClient->request($method, $endpoint, $options);
        
        // get contents
        $contents = $result->getBody()->getContents();
        
        // return data
        if ($json) {
            return json_decode($contents, true);
        }
        
        return $contents;
    }
    
    /**
     * @param array $data = []
     *
     * @return array|null
     */
    public function createShipment(array $data)
    {
        return $this->request(self::METHOD_POST, '/api/v2/shipment', $data);
    }
    
    /**
     * @param int $shipmentId
     *
     * @return string|null
     */
    public function getLabel(int $shipmentId)
    {
        return $this->request(self::METHOD_GET, "/api/v2/shipment/GetLabel/$shipmentId", [], [], false);
    }
    
    /**
     * @return string|null
     */
    public function getLabels()
    {
        return $this->request(self::METHOD_GET, "/api/v2/shipment/GetLabels", [], [], false);
    }
    
    /**
     * @return string|null
     */
    public function getLabelsAllTypes()
    {
        return $this->request(self::METHOD_GET, "/api/v2/shipment/GetLabelsAllTypes");
    }
}