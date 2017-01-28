<?php

namespace Huntie\JsonApi\Http\Requests;

use Huntie\JsonApi\Http\JsonApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

abstract class JsonApiRequest extends FormRequest
{
    /**
     * Base validation rules for all JSON API requests.
     *
     * @var array
     */
    private $baseRules = [
        'fields' => 'regex:^(?:[A-Za-z]+[A-Za-z_.\-,]*)*[A-Za-z]+$',
        'include' => 'regex:^(?:[A-Za-z]+[A-Za-z_.\-,]*)*[A-Za-z]+$',
        'sort' => 'regex:^-?(?:[A-Za-z]+[A-Za-z_.\-,]*)*[A-Za-z]+$',
        'filter' => 'array',
        'filter.*' => 'alpha_dash',
        'page' => 'array',
        'page.size' => 'integer',
        'page.number' => 'integer',
    ];

    /**
     * Base validation rules for the individual request type.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Get the validator instance for the request.
     *
     * @return Validator
     */
    protected function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();
        $validator->setRules(array_merge($this->baseRules, $this->rules, $validator->getRules()));

        return $validator;
    }

    /**
     * Format the errors from the given Validator instance.
     *
     * @param Validator $validator
     *
     * @return array
     */
    protected function formatErrors(Validator $validator)
    {
        $errors = [];

        foreach ($validator->getMessageBag()->messages() as $field => $messages) {
            foreach ($messages as $message) {
                $errors[] = [
                    'source' => [
                        'pointer' => str_replace('.', '/', $field),
                    ],
                    'title' => 'Invalid attribute',
                    'detail' => str_replace('data.attributes.', '', $message),
                ];
            }
        }

        return compact('errors');
    }

    /**
     * Get the proper failed validation response for the request.
     *
     * @param array $errors
     *
     * @return JsonApiResponse
     */
    public function response(array $errors)
    {
        return new JsonApiResponse($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
