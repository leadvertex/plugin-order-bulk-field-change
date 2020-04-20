<?php
/**
 * Created for plugin-core
 * Date: 02.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Macros;


use Adbar\Dot;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\MultipartStream;
use Leadvertex\Plugin\Components\ApiClient\ApiFilterSortPaginate;
use Leadvertex\Plugin\Components\Developer\Developer;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\Process\Components\Error;
use Leadvertex\Plugin\Components\Process\Process;
use Leadvertex\Plugin\Components\Purpose\PluginClass;
use Leadvertex\Plugin\Components\Purpose\PluginEntity;
use Leadvertex\Plugin\Components\Purpose\PluginPurpose;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Core\Macros\Components\AutocompleteInterface;
use Leadvertex\Plugin\Core\Macros\Helpers\PathHelper;
use Leadvertex\Plugin\Core\Macros\MacrosPlugin;
use Leadvertex\Plugin\Core\Macros\Models\Session;
use Leadvertex\Plugin\Instance\Macros\Components\FieldsHelper;
use Leadvertex\Plugin\Instance\Macros\Forms\FieldSelectorSettingsForm;
use Leadvertex\Plugin\Instance\Macros\Forms\FieldsValuesSelectorOptionsForm;

class Plugin extends MacrosPlugin
{

    /** @var FieldSelectorSettingsForm */
    private $settings;

    /**
     * @inheritDoc
     */
    public static function getLanguages(): array
    {
        return [
            'en_US',
            'ru_RU'
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultLanguage(): string
    {
        return 'ru_RU';
    }

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Translator::get('info', 'PLUGIN_NAME');
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return Translator::get('info', 'PLUGIN_DESCRIPTION') . "\n" . file_get_contents(PathHelper::getRoot()->down('markdown.md'));
    }

    /**
     * @inheritDoc
     */
    public static function getPurpose(): PluginPurpose
    {
        return new PluginPurpose(
            new PluginClass(PluginClass::CLASS_HANDLER),
            new PluginEntity(PluginEntity::ENTITY_ORDER)
        );
    }

    /**
     * @inheritDoc
     */
    public static function getDeveloper(): Developer
    {
        return new Developer(
            'LeadVertex',
            'support@leadvertex.com',
            'https://leadvertex.com'
        );
    }

    /**
     * @inheritDoc
     */
    public function getSettingsForm(): Form
    {
        if (is_null($this->settings)) {
            $this->settings = new FieldSelectorSettingsForm();
        }

        return $this->settings;
    }

    /**
     * @inheritDoc
     */
    public function getRunForm(int $number): ?Form
    {
        switch ($number) {
            case 1:
                return FieldsValuesSelectorOptionsForm::getInstance();
                break;
            default:
                return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function autocomplete(string $name): ?AutocompleteInterface
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process, ?ApiFilterSortPaginate $fsp)
    {
        $fieldsToChange = Session::current()->getOptions(1);
        if (!$fieldsToChange->isEmpty()) {
            $orders = self::getOrdersToChange(Session::current()->getFsp());
            $process->initialize(count($orders['data']));
            $process->save();
            foreach ($orders['data'] as $order) {
                $query = $this->applyOrderChanges($fieldsToChange, $order['id']);
                if (!$query['success']) {
                    $process->addError(new Error(Translator::get('process', 'PROCESS_ERROR_WHILE_APPLYING_CHANGES {errors}', ['errors' => json_encode($query['errors'])]), $order['id']));
                    $process->save();
                } else {
                    $process->handle();
                    $process->save();
                }
            }
            $process->finish(true);
            $process->save();
        } else {
            $process->initialize(null);
            $process->terminate(new Error(Translator::get('process', 'PROCESS_ERROR_OPTIONS_NOT_SET')));
            $process->save();
        }
    }

    static public function getOrdersToChange(ApiFilterSortPaginate $fsp): array
    {
        $session = Session::current();
        $api = $session->getApiClient();

        $variables['query'] = '$pagination: Pagination!';
        $variables['fetcher'] = 'pagination: $pagination';
        $variablesValues = [
            'pagination' => ['pageSize' => $fsp->getPageSize()]
        ];

        if (!is_null($fsp->getFilters())) {
            $variables['query'] .= ', $filters: OrderFilter';
            $variables['fetcher'] .= ', filters: $filters';
            $variablesValues['filters'] = $fsp->getFilters();
        }

        if (!is_null($fsp->getSort())) {
            $variables['query'] .= ', $sort: OrderSort';
            $variables['fetcher'] .= ', sort: $sort';
            $variablesValues['sort'] = $fsp->getSort();
        }

        $query = <<<QUERY
query ({$variables['query']}){
  company {
    ordersFetcher({$variables['fetcher']}) {
      orders {
        id
      }
    }
  }
}
QUERY;

        $result = $api->query($query, $variablesValues);
        if ($result->hasErrors()) {
            return ['success' => false, 'errors' => $result->getErrors()];
        }
        return ['success' => true, 'data' => $result->getData()['company']['ordersFetcher']['orders']];
    }

    private function applyOrderChanges(Dot $fieldsToChange, int $orderId)
    {
        $api = Session::current()->getApiClient();
        $variables['mutation'] = '$id: Id!, $data: OrderDataInput';
        $variables['update'] = 'id: $id, orderData: $data';
        $variablesValues = [
            'id' => $orderId,
            'data' => []
        ];
        $allFields = FieldsHelper::getFields();
        foreach ($fieldsToChange as $fieldToChange => $values) {
            if (!isset($allFields[$fieldToChange])) {
                continue;
            }

            switch ($allFields[$fieldToChange]['group']) {
                case 'FileField':
                    $variablesValues['data'] = array_merge_recursive(
                        $variablesValues['data'],
                        [
                            $allFields[$fieldToChange]['group'] . 's' => [
                                [
                                    'field' => $allFields[$fieldToChange]['title'],
                                    'value' => (is_null($values['value'])) ? null : $this->sendOrderFile($values['value'])
                                ]
                            ]
                        ]
                    );
                    break;
                case 'ImageField':
                    $variablesValues['data'] = array_merge_recursive(
                        $variablesValues['data'],
                        [
                            $allFields[$fieldToChange]['group'] . 's' => [
                                [
                                    'field' => $allFields[$fieldToChange]['title'],
                                    'value' => (is_null($values['value'])) ? null : $this->sendOrderImage($values['value'])
                                ]
                            ]
                        ]
                    );
                    break;
                case 'AddressField':
                case 'HumanNameField':
                    $variablesValues['data'] = array_merge_recursive(
                        $variablesValues['data'],
                        [
                            $allFields[$fieldToChange]['group'] . 's' => [
                                [
                                    'field' => $allFields[$fieldToChange]['title'],
                                    'value' => $values
                                ]
                            ]
                        ]
                    );
                    break;
                default:
                    $variablesValues['data'] = array_merge_recursive(
                        $variablesValues['data'],
                        [
                            $allFields[$fieldToChange]['group'] . 's' => [
                                [
                                    'field' => $allFields[$fieldToChange]['title'],
                                    'value' => $values['value']
                                ]
                            ]
                        ]
                    );
            }
        }


        $query = <<<QUERY
mutation({$variables['mutation']}) {
  updateOrder({$variables['update']}){
    id
  }
}
QUERY;

        $result = $api->query($query, $variablesValues);
        if ($result->hasErrors()) {
            return ['success' => false, 'errors' => $result->getErrors()];
        }
        return ['success' => true];
    }

    private function sendOrderImage(string $url)
    {
        $file = fopen($url, "r");

        $mutation = <<<MUTATION
mutation(\$file: Upload!){
  uploadOrderImage(file: \$file) {
    large {
      id
      uri
    }
  }
}
MUTATION;

        $boundary = '-------bulkFieldChange-------';
        $multipart_form = [
            [
                'name' => 'operations',
                'contents' => json_encode([
                    'operationName' => null,
                    'variables' => [
                        "file" => null
                    ],
                    'query' => $mutation
                ])
            ],
            [
                'name' => 'map',
                'contents' => '{"0":["variables.file"]}'
            ],
            [
                'name' => "0",
                'contents' => $file,
                'filename' => substr(hash("sha256", random_bytes(5)), 0, -34) . "_" . basename($url)
            ]
        ];

        $params = [
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            ],
            'body' => new MultipartStream($multipart_form, $boundary)
        ];

        $result = (new Client())->request('POST', Session::current()->getToken()->getInputToken()->getClaim('iss')  . 'companies/' . Session::current()->getRegistration()->getCompanyId() . '/CRM?token=' . Session::current()->getToken()->getOutputToken(), $params);
        return json_decode($result->getBody(), true)['data']['uploadOrderImage']['large']['id'];
    }

    private function sendOrderFile(string $url)
    {
        $file = fopen($url, "r");

        $mutation = <<<MUTATION
mutation(\$file: Upload!){
  uploadOrderFile(file: \$file) {
    id
  }
}
MUTATION;

        $boundary = '-------bulkFieldChange-------';
        $multipart_form = [
            [
                'name' => 'operations',
                'contents' => json_encode([
                    'operationName' => null,
                    'variables' => [
                        "file" => null
                    ],
                    'query' => $mutation
                ])
            ],
            [
                'name' => 'map',
                'contents' => '{"0":["variables.file"]}'
            ],
            [
                'name' => "0",
                'contents' => $file,
                'filename' => substr(hash("sha256", random_bytes(5)), 0, -34) . "_" . basename($url)
            ]
        ];

        $params = [
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            ],
            'body' => new MultipartStream($multipart_form, $boundary)
        ];

        $result = (new Client())->request('POST', Session::current()->getToken()->getInputToken()->getClaim('iss')  . 'companies/' . Session::current()->getRegistration()->getCompanyId() . '/CRM?token=' . Session::current()->getToken()->getOutputToken(), $params);
        return json_decode($result->getBody(), true)['data']['uploadOrderFile']['id'];
    }

}