<?php

declare(strict_types=1);

class FordAPI
{
    private $username;
    private $password;
    private $VIN;
    private $accessToken;
    private $refresToken;
    private $expires;
    private $ExpiresIn;
    private $disableSSL;

    const BASE_ENDPOINT = 'https://usapi.cv.ford.com/api';
    const GUARD_ENDPOINT = 'https://api.mps.ford.com/api';
    const SSO_ENDPOINT = 'https://sso.ci.ford.com/oidc/endpoint/default/token';
    const OTA_ENDPOINT = 'https://www.digitalservices.ford.com/owner/api/v2/ota/status';

    const CLIENT_ID = '9fb503e0-715b-47e8-adfd-ad4b7770f73b';
    const REGION = '1E8C7794-FF5F-49BC-9596-A1E0C86C5B19';

    const DEFAULT_HEADERS = array(
        "Accept:*/*",
        "Accept-Language:en-us",
        "Content-Type:application/json",
        "fordpass-na/353 CFNetwork/1121.2.2 Darwin/19.3.0",
        "Accept-Encoding:gzip,deflate,br",
    );

    const API_HEADERS = array(
        "Content-Type:application/json"
    );

    const OTA_HEADERS = array(
        'Consumer-Key:Z28tZXUtZm9yZA==',
        'Referer:https://ford.com',
        'Origin:https://ford.com'
    );

    const AUTH_HEADERS = array(
        "Content-Type:application/x-www-form-urlencoded"
    );

    const RESERVATION_HEADERS = [
        "Content-Type:application/json"
    ];

    public function __construct(string $Region, string $Username = '', string $Password = '', string $AccessToken = '', string $RefreshToken = '', DateTime $Expires = null)
    {
        $this->username = $Username;
        $this->password = $Password;
        $this->Region = self::REGION;
        $this->accessToken = $AccessToken;
        $this->refreshToken = $RefreshToken;
        if ($Expires == null)
            $this->expires = new DateTime('now');
        else
            $this->expires = $Expires;
        $this->disableSSL = false;
    }

    public function EnableSSLCheck()
    {
        $this->disableSSL = false;
    }

    public function DisableSSLCheck()
    {
        $this->disableSSL = true;
    }

    public function GetToken()
    {
        $token = array('AccessToken' => $this->accessToken);
        $token['RefreshToken'] = $this->refreshToken;
        $token['Expires'] = $this->expires;
        $token['ExpiresIn'] = $this->expiresIn;

        return (object)$token;
    }

    // Retrieve access token
    public function Connect()
    {
        if (strlen($this->accessToken) == 0 || $this->expires < new DateTime('now')) {
            if (strlen($this->username) > 0 && strlen($this->password) > 0) {
                $url = self::SSO_ENDPOINT;
                $body = array('client_id' => self::CLIENT_ID);
                $body['grant_type'] = 'password';
                $body['username'] = $this->username;
                $body['password'] = $this->password;
            } else {
                throw new Exception('Error: Missing username and/or password');
            }
        } else {
            // Use existing token
            return;
        }

        try {
            $now = new DateTime('now');

            $headers = array_merge(self::DEFAULT_HEADERS, self::AUTH_HEADERS);

            $result = $this->request('post', $url, $headers, http_build_query($body));

            if ($result->httpcode == 200) {
                $headers = self::DEFAULT_HEADERS;

                $body = array("code" => $result->result->access_token);
                $url = self::GUARD_ENDPOINT . '/oauth2/v1/token';

                $result = $this->request('put', $url, $headers, json_encode($body));

                if ($result->httpcode == 200) {
                    $this->accessToken = $result->result->access_token;
                    $this->refreshToken = $result->result->refresh_token;
                    $this->expires = $now;
                    $this->expires->add(new DateInterval('PT' . (string)$result->result->expires_in . 'S')); // adds expiresIn to "now"
                    $this->expiresIn = $result->result->expires_in;
                } else {
                    if ($result->error) {
                        print_r($result);
                        throw new Exception(sprintf('%s failed (%d). The error was "%s"', $url, $result->httpcode, $result->errortext));
                    }

                    throw new Exception(sprintf('%s returned http status code %d', $url, $result->httpcode));
                }
            } else {
                if ($result->error) {
                    throw new Exception(sprintf('%s failed (%d). The error was "%s"', $url, $result->httpcode, $result->errortext));
                }

                throw new Exception(sprintf('%s returned http status code %d', $url, $result->httpcode));
            }
        } catch (Exception $e) {
                print_r($e);
            throw new Exception($e->getMessage());
        }
    }

    // Refresh access token
    public function RefreshToken()
    {
        if (strlen($this->refreshToken) == 0) {
            $this->Connect();
            return;
        }

        $headers = array_merge(self::DEFAULT_HEADERS, self::API_HEADERS);
        $body = array('refresh_token' => $this->refreshToken);
        $url = self::GUARD_ENDPOINT . '/oauth2/v1/refresh';

        try {
            $now = new DateTime('now');

            $result = $this->request('put', $url, $headers, json_encode($body));

            if ($result->httpcode == 200) {
                $this->accessToken = $result->result->access_token;
                $this->refreshToken = $result->result->refresh_token;
                $this->expires = $now;
                $this->expires->add(new DateInterval('PT' . (string)$result->result->expires_in . 'S')); // adds expiresIn to "now"
                $this->expiresIn = $result->result->expires_in;
            } else if ($result->httpcode == 401) {
                $this->Connect();
                return;
            } else {
                if ($result->error) {
                    throw new Exception(sprintf('%s failed (%d). The error was "%s"', $url, $result->httpcode, $result->errortext));
                }

                throw new Exception(sprintf('%s returned http status code %d', $url, $result->httpcode));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // Get the status of the vehicle
    public function Status(string $VIN)
    {
        $this->Connect();

        $params = array("lrdt" => "01-01-1970 00:00:00");
        $headers = array_merge(self::DEFAULT_HEADERS, self::API_HEADERS, self::OTA_HEADERS);
        $url = "https://www.digitalservices.ford.com/owner/api/v2/vehicle/order-status?vin=" . $VIN . "&countryCode=DEU";

        try {
            $result = $this->request('get', $url, $headers);

            return $result;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    // Get the status of the vehicle
    public function getVIN(string $reservationNumber)
    {
        $this->Connect();
        $headers = array_merge(self::RESERVATION_HEADERS);
        $url = "https://www.authagent.ford.com/api/secure-purchase/gep/DEU/reservations/" . $reservationNumber . "?lang=de";

        try {
            $result = $this->request('get', $url, $headers, [], true);
            return $result;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    private function request($Type, $Url, $Headers, $Data = null, $true=false)
    {
        $ch = curl_init();

        switch (strtolower($Type)) {
            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case 'get':
                // Default for cURL
                break;
            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        if ($Data != null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $Data);
            $Headers[] = 'Content-Length:' . (string)strlen($Data);
        } else {
            $Headers[] = 'Content-Length:0';
        }

        $Headers[] = 'Application-Id:' . $this->Region;

        if (strlen($this->accessToken) > 0 && $this->expires > new DateTime('now')) {
//            $Headers[] = 'auth-token:'. $this->accessToken;
            $Headers[] = 'Auth-Token:' . $this->accessToken;
        }

        $Headers[] = 'X-Identity-Authorization: Bearer ' . $this->accessToken;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $Headers);

        if ($this->disableSSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_URL, $Url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        if ($result === false) {
            $response = array('error' => true);
            $response['errortext'] = curl_error($ch);
            $response['httpcode'] = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

            return (object)$response;
        }

        $response = array('error' => false);
        $response['httpcode'] = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $response['result'] = (object)null;

        $return = (object)$response;
        $return->result = $this->isJson($result) ? json_decode($result) : $result;

        return $return;
    }

    private function isJson(string $Data)
    {
        json_decode($Data);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}