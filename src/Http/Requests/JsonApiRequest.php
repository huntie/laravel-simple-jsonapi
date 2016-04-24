<?php

namespace Huntie\JsonApi\Http\Requests;

use Huntie\JsonApi\Http\JsonApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

abstract class JsonApiRequest extends FormRequest
{
    /**
     * Format the errors from the given Validator instance.
     *
     * {@inheritdoc}
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
                    'detail' => $message,
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
