<?php


namespace Leadvertex\Plugin\Instance\Macros\Forms;


use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Limit;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnum\Values\StaticValues;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\ListOfEnumDefinition;
use Leadvertex\Plugin\Components\Form\FieldGroup;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\Form\FormData;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Instance\Macros\Components\FieldsHelper;

class FieldSelectorSettingsForm extends Form
{

    public function __construct()
    {
        parent::__construct(
            Translator::get('field_selector_settings', 'SETTINGS_TITLE'),
            Translator::get('field_selector_settings', 'SETTINGS_DESCRIPTION'),
            $this->getArrayOfFields(),
            Translator::get(
                'field_selector_settings',
                'FORM_BUTTON'
            )
        );
    }

    private function getArrayOfFields(): array
    {
        $fields = FieldsHelper::getFields();

        $staticValidator = function ($values, ListOfEnumDefinition $definition, FormData $data) {
            $limit = $definition->getLimit();

            $errors = [];

            if (!is_null($values) && !is_array($values)) {
                $errors[] = Translator::get('field_selector_settings', 'FIELD_LIST_VALIDATION_INVALID_ARGUMENT');
                return $errors;
            }

            if ($limit) {

                if ($limit->getMin() && count($values) < $limit->getMin()) {
                    $errors[] = Translator::get('field_selector_settings', 'FIELD_LIST_VALIDATION_ERROR_MIN {min}', ['min' => $limit->getMin()]);
                }

                if ($limit->getMax() && count($values) > $limit->getMax()) {
                    $errors[] = Translator::get('field_selector_settings', 'FIELD_LIST_VALIDATION_ERROR_MIN {max}', ['max' => $limit->getMax()]);
                }
            }

            return $errors;
        };

        return [
            "fields" => new FieldGroup(
                Translator::get('field_selector_settings', 'GROUP_1_TITLE'),
                Translator::get('field_selector_settings', 'GROUP_1_DESCRIPTION'),
                [
                    'fieldsSelector' => new ListOfEnumDefinition(
                        Translator::get('field_selector_settings', 'FIELDS_SELECTOR_TITLE'),
                        Translator::get('field_selector_settings', 'FIELDS_SELECTOR_DESCRIPTION'),
                        $staticValidator,
                        new StaticValues($fields),
                        new Limit(1, null)
                    )
                ]
            )
        ];
    }

}