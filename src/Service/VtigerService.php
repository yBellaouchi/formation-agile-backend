<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Exception;

class VtigerService
{
    private $client;
    private $baseUrl;
    private $adminUsername;
    private $adminAccessKey;
    private $adminSessionName;
    private $sessionName;
    private $currentUser;
    private $id;
    private $cache;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->baseUrl = $_SERVER['BASE_URL'];
        $this->adminUsername = $_SERVER['USER_NAME'];
        $this->adminAccessKey = $_SERVER['ACCESS_KEY'];
        $this->adminSessionName = $_SERVER['SESSION_NAME'];
        $this->id = $_SERVER['ID'];
        $redis = RedisAdapter::createConnection('redis://localhost:6379');
        $this->cache = new RedisAdapter($redis);
    }

    public function getChallenge($currentUser = null)
    {
        $response = $this->client->request(
            'GET',
            $this->baseUrl,
            [
                "query" => [
                    "operation" => "getchallenge",
                    "username" => $currentUser ? $currentUser : $this->adminUsername
                ]
            ]
        );
        $response = json_decode($response->getContent(), true);
        if ($response['success']) {
            return $response['result'];
        }
        throw new Exception($response['error']['message']);
    }
    public function login($currentUser, $accessKey)
    {
        $this->currentUser = $currentUser;
        $userNameRedisKey = str_replace("@", "", $currentUser);
        $cacheItem = $this->cache->getItem($userNameRedisKey);
        if ($cacheItem->get() !== null) {
            $this->sessionName = $cacheItem->get();
            return $this->sessionName;
        }
        $res = $this->getChallenge($currentUser);
        $expiresAfter = $res["expireTime"] - $res["serverTime"];
        $response = $this->client->request(
            'POST',
            $this->baseUrl,
            [
                'body' => [
                    'operation' => 'login',
                    'username' => $currentUser,
                    'accessKey' => md5($res['token'] . $accessKey)
                ],
            ]
        );
        $response->getContent();
        $response = $response->toArray();
        if ($response['success']) {
            $cacheItem->set($response['result']['sessionName']);
            $cacheItem->expiresAfter($expiresAfter);
            $this->cache->save($cacheItem);
            $this->sessionName = $response['result']['sessionName'];
            return $this->sessionName;
        }
        throw new Exception($response['error']['message']);
    }
    public function loginAsAdmin()
    {
        $res = $this->getChallenge();
        $response = $this->client->request(
            'POST',
            $this->baseUrl,
            [
                'body' => [
                    'operation' => 'login',
                    'username' => $this->adminUsername,
                    'accessKey' => md5($res['token'] . $this->adminAccessKey)
                ],
            ]
        );
        $response->getContent();
        $response = $response->toArray();
        if ($response['success']) {
            return [
                "sessionName" =>
                $response['result']['sessionName'],
                "expiresAfter" => $res["expireTime"] - $res["serverTime"]
            ];
        }
        throw new Exception($response['error']['message']);
    }

    public function retrieveById($elementType, $id)
    {
        $response = $this->client->request(
            'GET',
            $this->baseUrl,
            [
                'query' => [
                    'operation' => 'retrieve',
                    'sessionName' => $this->sessionName,
                    'id' => $id
                ]
            ]
        );
        $response = json_decode($response->getContent(), true);
        if ($response['success']) {
            return $this->convertNameToLabel($elementType, $response['result']);
        }
        throw new Exception($response['message']);
    }
    public function describe($elementType)
    {
        $response = $this->client->request(
            'GET',
            $this->baseUrl,
            [
                'query' => [
                    'operation' => 'describe',
                    'sessionName' => $this->sessionName,
                    'elementType' => $elementType
                ]
            ]
        );
        $response = json_decode($response->getContent(), true);

        if ($response['success']) {
            return $response['result']['fields'];
        } else {
            $response['error']['message'];
        }
    }
    public function convertNameToLabel($elementType, $element)
    {
        $keys = array_keys($element);
        $result = [];
        $fieldsDesc = $this->describe($elementType);

        foreach ($fieldsDesc as $fieldDesc) {
            $x = array_search($fieldDesc['name'], $keys);
            if ($x !== false) {
                $label = $fieldDesc['label'];
                $result[$label] = $element[$fieldDesc['name']];
            }
        }
        return $result;
    }

    public function convertLabelToName($obj, $elementType)
    {
        $describes = $this->describe($elementType);
        $keys = array_keys($obj);
        $result = [];

        foreach ($describes as $describe) {
            $label = $describe['label'];
            $x = array_search($label, $keys);
            if ($x !== false) {
                $name = $describe['name'];
                $result[$name] = $obj[$label];
            }
        }
        return $result;
    }

    public function create($elementType, $obj)
    {
        $response = $this->client->request(
            'POST',
            $this->baseUrl,
            [
                'body' => [
                    'operation' => 'create',
                    'sessionName' => $this->sessionName,
                    'element' => json_encode($this->convertLabelToName($obj, $elementType), true),
                    'elementType' => $elementType
                ]
            ]
        );
        $response = json_decode($response->getContent(), true);

        if ($response['success']) {
            return $this->convertNameToLabel($elementType, $response['result']);
        }
        throw new Exception($response['message']);
    }

    public function getOperations()
    {
        $response = $this->client->request(
            'GET',
            $this->baseUrl,
            [
                'query' =>  [
                    'operation' => 'listtypes',
                    'sessionName' => $this->sessionName
                ]
            ]
        );
        $response = json_decode($response->getContent(), true);
        if ($response['success']) {
            return $response;
        }
        throw new Exception($response['message']);
    }
    public function edit($elementType, $obj)
    {
        $response = $this->client->request(
            'POST',
            $this->baseUrl,
            [
                'body' => [
                    'operation' => 'update',
                    'sessionName' => $this->sessionName,
                    'element' => json_encode($this->convertLabelToName($obj, $elementType), true)
                ],
            ]
        );
        $response = json_decode($response->getContent(), true);

        if (false === $response["success"]) {
            throw new Exception($response["message"]);
        }
        return $this->convertNameToLabel($elementType, $response['result']);
    }

    public function delete($id)
    {
        $response = $this->client->request(
            'POST',
            $this->baseUrl,
            [
                'body' => [
                    'operation' => 'delete',
                    'sessionName' => $this->sessionName,
                    'id' =>  $id
                ]
            ]
        );
        $response = json_decode($response->getContent(), true);

        if ($response['success']) {
            return $response;
        }
        throw new Exception($response['message']);
    }

    public function getAll($elementType)
    {
        $query = "SELECT * FROM {$elementType} LIMIT 10;";
        // $query = "SELECT * FROM {$elementType} WHERE id = '57x26835' ;";
        $response = $this->client->request(
            'GET',
            $this->baseUrl,
            [
                'query' => [
                    'operation' => 'query',
                    'sessionName' => $this->sessionName,
                    'query' =>  $query
                ]
            ]
        );
        $response = json_decode($response->getContent(), true);
        if ($response['success']) {
            $elements = $response['result'];
            $elements = array_map(function ($element) {
                return $this->convertNameToLabel('Projets', $element);
            }, $elements);
            return $elements;
        }
        throw new Exception($response['message']);
    }
    public function labelToName($label)
    {
        $elements = $this->describe('Projets');
        foreach ($elements as $element) {
            if (array_search($label, $element) == "label") {
                return $element["name"];
            }
        }
    }
    public function retrieveBy($elementType, $fields, $values)
    {

        // $query = "SELECT * FROM {$elementType} LIMIT 10;";
        // $query = "SELECT * FROM {$elementType} WHERE id = '57x1614' AND name ='PR87_ADV_VLG';";
        $arr = [];

        for ($i = 0; $i < count($fields); $i++) {
            $arr[$fields[$i]] = $values[$i];
        }
        foreach ($arr as $key => $value) {
            $stmt[] = "$key" . "=" . "'$value'";
        }
        $stmt = implode(" And ", $stmt);
        $query = "SELECT * FROM {$elementType} WHERE $stmt LIMIT 10 ;";

        $response = $this->client->request(
            'GET',
            $this->baseUrl,
            [
                'query' => [
                    'operation' => 'query',
                    'sessionName' => $this->sessionName,
                    'query' =>  $query
                ]
            ]
        );
        $response = json_decode($response->getContent(), true);
        if ($response['success']) {
            $elements = $response['result'];
            return $elements;
        }
        throw new Exception($response['message']);
    }
}
