<?php

namespace App\Service;

use DateTime;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
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
    private $tokenStorage;

    public function __construct(HttpClientInterface $client, TokenStorageInterface $tokenStorage)
    {
        $this->client = $client;
        $this->baseUrl = $_SERVER['BASE_URL'];
        $this->adminUsername = $_SERVER['USER_NAME'];
        $this->adminAccessKey = $_SERVER['ACCESS_KEY'];
        $this->adminSessionName = $_SERVER['SESSION_NAME'];
        $this->id = $_SERVER['ID'];
        $redis = RedisAdapter::createConnection('redis://localhost:6379');
        $this->cache = new RedisAdapter($redis);
        $this->tokenStorage = $tokenStorage;
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
    public function login()
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $currentUser = $user->getUsername();
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
                    'accessKey' => md5($res['token'] . $user->getUserAccessKey())
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

    public function retrieveById($elementType, $id, $selectedFields = [])
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
            if ($selectedFields == null) {
                return  $this->convertNameToLabel($elementType, $response['result']);
            } else {
                $res = [];
                $retrivedProject = $this->convertNameToLabel($elementType, $response['result']);
                foreach ($selectedFields as $field) {
                    $res[$field] = $retrivedProject[$field];
                }
                return $res;
            }
        }
        throw new Exception($response['error']['message']);
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
        throw new Exception($response['error']['message']);
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
        throw new Exception($response['error']['message']);
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
            throw new Exception($response['error']["message"]);
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
        throw new Exception($response['error']['message']);
    }

    public function getAll($elementType, $selectedFields = [], $dateInterval = null, $dateType = null)
    {
        $selectedFields = ['Nom du projet' , 'Assigné à','Created At', 'Modified At'];
        $stmt1 = $this->getStatementOfSelectedFields($selectedFields);

        $stmt2 =
        ($dateInterval != null and $dateType != null) ?
         "where " . $this->getStatementOfDate($dateInterval, $dateType) :
          "";
        $query = "SELECT $stmt1 FROM {$elementType} $stmt2 ORDER BY modifiedtime DESC limit 30;";
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
            // dd($elements);
            $elements = array_map(function ($element) {
                return $this->convertNameToLabel('Projets', $element);
            }, $elements);
            return $elements;
        }
        // dd($query);
        throw new Exception($response['error']['message']);
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
    public function retrieveBy(
        $elementType,
        $fields,
        $values,
        $selectedFields = [],
        $dateInterval = null,
        $dateType = null
    ) {
        $stmt1 = $this->getStatementOfSelectedFields($selectedFields);
        foreach ($fields as $key => $field) {
            $stmt[] = $this->labelToName($field) . " = " . $values[$key];
        }
        $stmt2 = implode(" And ", $stmt);

        $stmt3 =
        ($dateInterval != null and $dateType != null) ?
         "And " . $this->getStatementOfDate($dateInterval, $dateType) :
          "";
        $query = "SELECT $stmt1 FROM {$elementType} WHERE $stmt2 $stmt3  ORDER BY modifiedtime ASC LIMIT 20 ;";

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
        throw new Exception($response['error']['message']);
    }
    public function getStatementOfDate($dateInterval = null, $dateType = null)
    {
        $date = new DateTime();
            $mydate = $date->format('Y-m-d H:i:s');
            $mydate = strtotime($mydate);
        switch ($dateType) {
            case 'd':
                $mydate -= $dateInterval * 3600 * 24;
                break;
            case 'm':
                $mydate -= $dateInterval * 3600 * 24 * 30;
                break;
            case 'y':
                $mydate -=  $dateInterval * 3600 * 24 * 30 * 12;
                break;
        }
            $date->setTimestamp($mydate);
            $mydate = $date->format('Y-m-d H:i:s');
            $stmt2 = " modifiedtime  >= '$mydate'";
            return $stmt2;
    }
    public function getStatementOfSelectedFields($selectedFields)
    {
        if (count($selectedFields) > 0) {
            $selectedFields = array_map(function ($element) {
                return $this->labelToName($element);
            }, $selectedFields);
            return implode(", ", $selectedFields);
        } else {
            return "*";
        }
    }
}
