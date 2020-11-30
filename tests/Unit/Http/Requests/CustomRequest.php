<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Longman\LaravelLodash\Http\Requests\RestrictsExtraAttributes;

class CustomRequest extends FormRequest
{
    use RestrictsExtraAttributes;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
