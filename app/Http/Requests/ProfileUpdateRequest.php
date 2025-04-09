<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'matricula' => ['sometimes', 'string', 'max:20'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'cargo' => ['sometimes', 'string', 'max:255'],
            'lotacao' => ['nullable', 'string', 'max:255'],
            'documento' => ['sometimes', 'string', 'max:20'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Digite um endereço de e-mail válido.',
            'email.max' => 'O e-mail não pode ter mais de 255 caracteres.',
            'email.unique' => 'Este e-mail já está em uso.',
            'telefone.max' => 'O telefone não pode ter mais de 20 caracteres.',
            'lotacao.max' => 'A lotação não pode ter mais de 255 caracteres.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Se houver manipulações de dados necessárias antes da validação
        // Por exemplo, formatar telefone, CPF, etc.
        if ($this->filled('telefone')) {
            // Remove caracteres não numéricos do telefone se necessário
            // $this->merge(['telefone' => preg_replace('/[^0-9]/', '', $this->telefone)]);
        }
    }
}