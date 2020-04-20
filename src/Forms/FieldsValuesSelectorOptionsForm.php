<?php


namespace Leadvertex\Plugin\Instance\Macros\Forms;


use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Core\Macros\Models\Session;
use Leadvertex\Plugin\Instance\Macros\Components\FieldsHelper;
use Leadvertex\Plugin\Instance\Macros\Components\OptionsSingletonTrait;

class FieldsValuesSelectorOptionsForm extends Form
{
    use OptionsSingletonTrait;

    private function __construct()
    {
        if (Session::current()->getSettings()->getData()->isEmpty()) {
            parent::__construct(
                Translator::get('fields_values_selector_options', 'OPTIONS_TITLE'),
                Translator::get('fields_values_selector_options', 'OPTIONS_NOT_SET_DESCRIPTION'),
                [],
                Translator::get(
                    'fields_values_selector_options',
                    'FORM_BUTTON'
                )
            );
        } else {
            parent::__construct(
                Translator::get('fields_values_selector_options', 'OPTIONS_TITLE'),
                Translator::get('fields_values_selector_options', 'OPTIONS_DESCRIPTION'),
                $this->getArrayOfGroups(),
                Translator::get(
                    'fields_values_selector_options',
                    'FORM_BUTTON'
                )
            );
        }
    }

    private function getArrayOfGroups(): array
    {
        $requestedFields = Session::current()->getSettings()->getData()->get("fields.fieldsSelector");
        return FieldsHelper::getFieldsGroups($requestedFields, FieldsHelper::getFields());
    }

}