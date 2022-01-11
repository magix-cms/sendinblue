<?php
require_once(__DIR__ . '/vendor/autoload.php');
class SendinBlueManager {
    /**
     * @var
     */
    protected $config;

    /**
     * @var string $api_key
     */
    protected $api_key = '';

    /**
     * @var bool $debug
     */
    public $debug = false;

    /**
     * @param string $key
     */
    public function __construct(string $key, bool $debug = false) {
        $this->api_key = $key;
        $this->debug = $debug;
        $this->init();
    }

    /**
     *
     */
    private function init() {
        if(!empty($this->api_key)) {
            // Configure API key authorization: api-key
            $this->config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->api_key);

            $apiInstance  = new SendinBlue\Client\Api\AccountApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
                new GuzzleHttp\Client(),
                $this->config
            );

            if($this->debug) {
                try {
                    $result = $apiInstance ->getAccount();
                    print_r($result);
                } catch (Exception $e) {
                    echo 'Exception when calling AccountApi->getAccount: ', $e->getMessage(), PHP_EOL;
                }
            }
        }
    }

    /**
     * @param int $limit | Number of documents per page
     * @param int $offset | Index of the first document of the page
     * @param string $sort | Sort the results in the ascending/descending order of record creation. Default order is **descending** if `sort` is not passed
     * @return array|\SendinBlue\Client\Model\GetLists
     * @throws \SendinBlue\Client\ApiException
     */
    public function getLists(int $limit = 10, int $offset = 0, $sort = "desc") {
        $lists = [];
        $apiInstance = new SendinBlue\Client\Api\ContactsApi(
        // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
        // This is optional, `GuzzleHttp\Client` will be used as default.
            new GuzzleHttp\Client(),
            $this->config
        );

        if($this->debug) {
            try {
                $lists = $apiInstance ->getLists($limit, $offset, $sort);
                print_r($lists);
            } catch (Exception $e) {
                echo 'Exception when calling ContactsApi->getLists: ', $e->getMessage(), PHP_EOL;
            }
        }
        else {
            $lists = $apiInstance ->getLists($limit, $offset, $sort);
        }

        return $lists;
    }

    /**
     * @param int $listId | Id of the list
     * @param int $limit | Number of documents per page
     * @param int $offset | Index of the first document of the page
     * @param string $sort | Sort the results in the ascending/descending order of record creation. Default order is **descending** if `sort` is not passed
     * @param string $modifiedSince | Filter (urlencoded) the contacts modified after a given UTC date-time (YYYY-MM-DDTHH:mm:ss.SSSZ). Prefer to pass your timezone in date-time format for accurate result.
     */
    public function getMembers(int $listId, int $limit = 25, int $offset = 0, string $sort = "desc", string $modifiedSince = '') {
        $membersList = [
            'total_items' => 0,
            'members' => [],
        ];
        $apiInstance = new SendinBlue\Client\Api\ContactsApi(
        // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
        // This is optional, `GuzzleHttp\Client` will be used as default.
            new GuzzleHttp\Client(),
            $this->config
        );

        if($this->debug) {
            try {
                $membersList = $apiInstance->getContactsFromList($listId, $modifiedSince, $limit, $offset, $sort);
                print_r($membersList);
            } catch (Exception $e) {
                echo 'Exception when calling ContactsApi->getContactsFromList: ', $e->getMessage(), PHP_EOL;
            }
        }
        else {
            $membersList = $apiInstance->getContactsFromList($listId, $modifiedSince, $limit, $offset, $sort);
        }
        return $membersList;
    }

    /**
     * @param int $listId
     * @param string $email
     * @param string $firstname
     * @param string $lastname
     */
    public function addMemberToList(int $listId, string $email, string $firstname = '', string $lastname = '') {
        $apiInstance = new SendinBlue\Client\Api\ContactsApi(
        // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
        // This is optional, `GuzzleHttp\Client` will be used as default.
            new GuzzleHttp\Client(),
            $this->config
        );

        $createContact = new SendinBlue\Client\Model\CreateContact(); // Values to create a contact
        $createContact["updateEnabled"] = true;
        $createContact['email'] = $email;
        $createContact['listIds'] = [$listId];
        //if(!empty($firstname)) $createContact['attributes']["PRENOM"] = $firstname;
        //if(!empty($lastname)) $createContact['attributes']["NOM"] = $lastname;

        if($this->debug) {
            try {
                $result = $apiInstance->createContact($createContact);
                print_r($result);
            } catch (Exception $e) {
                print_r($e);
                echo 'Exception when calling ContactsApi->createContact: ', $e->getMessage(), PHP_EOL;
            }
        }
        else {
            $apiInstance->createContact($createContact);
        }

        $identifier = $email;
        $updateContact = new \SendinBlue\Client\Model\UpdateContact();
        $updateContact['attributes'] = ['PRENOM'=>$firstname, 'NOM'=>$lastname];

        if($this->debug) {
            try {
                $apiInstance->updateContact($identifier, $updateContact);
            } catch (Exception $e) {
                echo 'Exception when calling ContactsApi->updateContact: ', $e->getMessage(), PHP_EOL;
            }
        }
        else {
            $apiInstance->updateContact($identifier, $updateContact);
        }
    }
}