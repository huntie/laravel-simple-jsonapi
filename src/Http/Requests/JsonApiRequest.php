<?php

namespace Huntie\JsonApi\Http\Requests;

use Huntie\JsonApi\Exceptions\HttpException;
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
        $this->beforeValidation();

        $validator = parent::getValidatorInstance();
        $validator->setRules(array_merge($this->baseRules, $this->rules, $validator->getRules()));

        return $validator;
    }

    /**
     * Perform additional logic before the request input is validated.
     *
     * @throws HttpException
     */
    protected function beforeValidation()
    {
        if (!$this->isJson()) {
            throw new HttpException('Unsupported Content-Type', Reponse::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        if ($this->exists('include') && config('jsonapi.enable_included_resources') === false) {
            throw new HttpException('Inclusion of related resources is not supported');
        }
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

    /**
     * Return an input field containing comma separated values as an array.
     *
     * @param string $key
     */
    public function inputSet(string $key): array
    {
        return preg_split('/,/', $this->input($key), null, PREG_SPLIT_NO_EMPTY);
    }
}
