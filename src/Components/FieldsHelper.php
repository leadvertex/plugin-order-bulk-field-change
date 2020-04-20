<?php


namespace Leadvertex\Plugin\Instance\Macros\Components;


use Leadvertex\Plugin\Components\Form\FieldDefinitions\FieldDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Limit;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Values\StaticValues;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnumDefinition;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\StringDefinition;
use Leadvertex\Plugin\Components\Form\FieldGroup;
use Leadvertex\Plugin\Components\Form\FormData;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Core\Macros\Models\Session;

class FieldsHelper
{
    static public function getFieldsGroups(array $requestedFields, array $allFields): array
    {
        $groups = [];
        foreach ($requestedFields as $field) {
            $groups = array_merge($groups, self::getFieldFields($allFields[$field]['group'], $field));
        }
        return $groups;
    }

    static private function getFieldFields($field, $fieldName): array
    {
        switch ($field) {
            case "StringField":
            case "PhoneField":
            case "EmailField":
            case "DatetimeField":
                return self::getStringField($fieldName);
            case "IntegerField":
            case "UserField":
                return self::getIntegerField($fieldName);
            case "ImageField":
            case "FileField":
                return self::getFileField($fieldName);
            case "FloatField":
                return self::getFloatField($fieldName);
            case "BooleanField":
                return self::getBoolField($fieldName);
            case "AddressField":
                return self::getAddressField($fieldName);
            case "HumanNameField":
                return self::getHumanNameField($fieldName);
            case "EnumField":
                return self::getEnumField($fieldName);
            default:
                return null;
        }
    }

    private static function getStringField($fieldName): array
    {
        $stringValidator = function ($value, FieldDefinition $definition, FormData $form) {
            $errors = [];

            if (!is_scalar($value) && !is_null($value)) {
                $errors[] = Translator::get('fields_values_selector_options', 'STRING_VALIDATION_INVALID_ARGUMENT');
            }

            return $errors;
        };

        return [
            $fieldName => new FieldGroup(
                $fieldName,
                Translator::get('fields_values_selector_options', 'STRING_OPTIONS_GROUP_DESCRIPTION {field}', ['field' => $fieldName]),
                [
                    'value' => new StringDefinition(
                        Translator::get('fields_values_selector_options', 'STRING_FIELD_TITLE'),
                        Translator::get('fields_values_selector_options', 'STRING_FIELD_DESCRIPTION'),
                        $stringValidator
                    )
                ]
            )
        ];
    }

    private static function getIntegerField($fieldName): array
    {
        $integerValidator = function ($value, FieldDefinition $definition, FormData $form) {
            $errors = [];
            if (!is_int($value) && !is_null($value)) {
                $errors[] = Translator::get('fields_values_selector_options', 'INTEGER_VALIDATION_ERROR');
            }
            return $errors;
        };

        return [
            $fieldName => new FieldGroup(
                $fieldName,
                Translator::get('fields_values_selector_options', 'INTEGER_OPTIONS_GROUP_DESCRIPTION {field}', ['field' => $fieldName]),
                [
                    'value' => new StringDefinition(
                        Translator::get('fields_values_selector_options', 'INTEGER_FIELD_TITLE'),
                        Translator::get('fields_values_selector_options', 'INTEGER_FIELD_DESCRIPTION'),
                        $integerValidator
                    )
                ]
            )
        ];
    }

    private static function getFileField($fieldName): array
    {
        $fileValidator = function ($value, FieldDefinition $definition, FormData $form) {
            $errors = [];

            if (!is_scalar($value) && !is_null($value)) {
                $errors[] = Translator::get('fields_values_selector_options', 'FILE_VALIDATION_INVALID_ARGUMENT');
                return $errors;
            }

            $ch = curl_init((string) $value);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($code !== 200) {
                $errors[] = Translator::get('fields_values_selector_options', 'FILE_VALIDATION_BAD_RESPONSE');
            }

            return $errors;
        };

        return [
            $fieldName => new FieldGroup(
                $fieldName,
                Translator::get('fields_values_selector_options', 'FILE_OPTIONS_GROUP_DESCRIPTION {field}', ['field' => $fieldName]),
                [
                    'value' => new StringDefinition(
                        Translator::get('fields_values_selector_options', 'FILE_FIELD_TITLE'),
                        Translator::get('fields_values_selector_options', 'FILE_FIELD_DESCRIPTION'),
                        $fileValidator
                    )
                ]
            )
        ];
    }

    private static function getFloatField($fieldName): array
    {
        $floatValidator = function ($value, FieldDefinition $definition, FormData $form) {
            $errors = [];
            if (!is_numeric($value) && !is_null($value)) {
                $errors[] = Translator::get('fields_values_selector_options', 'FLOAT_VALIDATION_ERROR');
            }
            return $errors;
        };

        return [
            $fieldName => new FieldGroup(
                $fieldName,
                Translator::get('fields_values_selector_options', 'FLOAT_OPTIONS_GROUP_DESCRIPTION {field}', ['field' => $fieldName]),
                [
                    'value' => new StringDefinition(
                        Translator::get('fields_values_selector_options', 'FLOAT_FIELD_TITLE'),
                        Translator::get('fields_values_selector_options', 'FLOAT_FIELD_DESCRIPTION'),
                        $floatValidator
                    )
                ]
            )
        ];
    }

    private static function getBoolField($fieldName): array
    {
        $boolValidator = function ($value, FieldDefinition $definition, FormData $form) {
            $errors = [];
            if (!is_bool($value)) {
                $errors[] = Translator::get('fields_values_selector_options', 'BOOL_VALIDATION_ERROR');
            }
            return $errors;
        };

        return [
            $fieldName => new FieldGroup(
                $fieldName,
                Translator::get('fields_values_selector_options', 'BOOL_OPTIONS_GROUP_DESCRIPTION {field}', ['field' => $fieldName]),
                [
                    'value' => new StringDefinition(
                        Translator::get('fields_values_selector_options', 'BOOL_FIELD_TITLE'),
                        Translator::get('fields_values_selector_options', 'BOOL_FIELD_DESCRIPTION'),
                        $boolValidator
                    )
                ]
            )
        ];
    }

    private static function getAddressField($fieldName): array
    {
        $stringValidator = function ($value, FieldDefinition $definition, FormData $form) {
            $errors = [];

            if (!is_scalar($value) && !is_null($value)) {
                $errors[] = Translator::get('fields_values_selector_options', 'STRING_VALIDATION_INVALID_ARGUMENT');
            }

            return $errors;
        };

        return [
            $fieldName => new FieldGroup(
                $fieldName,
                Translator::get('fields_values_selector_options', 'ADDRESS_OPTIONS_GROUP_DESCRIPTION {field}', ['field' => $fieldName]),
                [
                    'postcode' => new StringDefinition(
                        Translator::get('fields_values_selector_options', 'POSTCODE_FIELD_TITLE'),
                        Translator::get('fields_values_selector_options', 'POSTCODE_FIELD_DESCRIPTION'),
                        $stringValidator
                    ),
                    'city' => new StringDefinition(
                        Translator::get('fields_values_selector_options', 'CITY_FIELD_TITLE'),
                        Translator::get('fields_values_selector_options', 'CITY_FIELD_DESCRIPTION'),
                        $stringValidator
                    ),
                    'address_1' => new StringDefinition(
                        Translator::get('fields_values_selector_options', 'ADDRESS_1_FIELD_TITLE'),
                        Translator::get('fields_values_selector_options', 'ADDRESS_1_FIELD_DESCRIPTION'),
                        $stringValidator
                    ),
                    'address_2' => new StringDefinition(
                        Translator::get('fields_values_selector_options', 'ADDRESS_2_FIELD_TITLE'),
                        Translator::get('fields_values_selector_options', 'ADDRESS_2_FIELD_DESCRIPTION'),
                        $stringValidator
                    )
                ]
            )
        ];
    }

    private static function getHumanNameField($fieldName): array
    {
        $stringValidator = function ($value, FieldDefinition $definition, FormData $form) {
            $errors = [];

            if (!is_scalar($value) && !is_null($value)) {
                $errors[] = Translator::get('fields_values_selector_options', 'STRING_VALIDATION_INVALID_ARGUMENT');
            }

            return $errors;
        };

        return [
            $fieldName => new FieldGroup(
                $fieldName,
                Translator::get('fields_values_selector_options', 'HUMAN_NAME_OPTIONS_GROUP_DESCRIPTION {field}', ['field' => $fieldName]),
                [
                    'firstName' => new StringDefinition(
                        Translator::get('fields_values_selector_options', 'FIRSTNAME_FIELD_TITLE'),
                        Translator::get('fields_values_selector_options', 'FIRSTNAME_FIELD_DESCRIPTION'),
                        $stringValidator
                    ),
                    'lastName' => new StringDefinition(
                        Translator::get('fields_values_selector_options', 'LASTNAME_FIELD_TITLE'),
                        Translator::get('fields_values_selector_options', 'LASTNAME_FIELD_DESCRIPTION'),
                        $stringValidator
                    )
                ]
            )
        ];
    }

    private static function getEnumField($fieldName): array
    {
        $valuesArray = self::getEnumPossibleValues($fieldName);
        $values = [];

        array_walk($valuesArray, function ($field) use (&$values, $fieldName) {
            $values[] = [
                'title' => $field,
                'group' => $fieldName
            ];
        });

        $enumValidator = function ($value, FieldDefinition $definition, FormData $form) use ($valuesArray) {
            $errors = [];

            if (!is_scalar($value)) {
                $errors[] = Translator::get('fields_values_selector_options', 'ENUM_VALIDATION_INVALID_ARGUMENT');
            }

            if (!in_array($value, $valuesArray)) {
                $errors[] = Translator::get('fields_values_selector_options', 'ENUM_VALIDATION_INVALID_VALUE {value}', ['value' => $value]);
            }

            return $errors;
        };

        return [
            $fieldName => new FieldGroup(
                $fieldName,
                Translator::get('fields_values_selector_options', 'ENUM_OPTIONS_GROUP_DESCRIPTION {field}', ['field' => $fieldName]),
                [
                    'value' => new ListOfEnumDefinition(
                        Translator::get('fields_values_selector_options', 'ENUM_FIELD_TITLE'),
                        Translator::get('fields_values_selector_options', 'ENUM_FIELD_DESCRIPTION'),
                        $enumValidator,
                        new StaticValues($values),
                        new Limit(1, 1)
                    )
                ]
            )
        ];
    }

    private static function getEnumPossibleValues($fieldName)
    {
        $session = Session::current();
        $api = $session->getApiClient();

        $query = <<<QUERY
query {
  company {
    fieldsFetcher( filters:{ name:"{$fieldName}" } ) {
      fields {
        ...on EnumField{
          values
        }
      }
    }
  }
}

QUERY;

        $result = $api->query($query, []);
        $result = $result->getData()['company']['fieldsFetcher']['fields'][0]['values'];


        return $result;
    }

    static public function getFields(): array
    {
        $session = Session::current();
        $api = $session->getApiClient();

        $query = <<<QUERY
query {
  company {
    fieldsFetcher {
      fields {
        name
        __typename
      }
    }
  }
}

QUERY;

        $result = $api->query($query, []);

        $fields = [];
        $queryResult = $result->getData()['company']['fieldsFetcher']['fields'];

        array_walk($queryResult, function ($field) use (&$fields) {
            $fields[$field['name']] = [
                'title' => $field['name'],
                'group' => $field['__typename']
            ];
        });

        return $fields;
    }
}