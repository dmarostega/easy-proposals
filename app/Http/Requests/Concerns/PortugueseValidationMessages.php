<?php

namespace App\Http\Requests\Concerns;

trait PortugueseValidationMessages
{
    public function messages(): array
    {
        return [
            'array' => 'O campo :attribute deve ser uma lista.',
            'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
            'confirmed' => 'A confirmação do campo :attribute não confere.',
            'current_password' => 'A senha atual está incorreta.',
            'date' => 'O campo :attribute deve ser uma data válida.',
            'email' => 'O campo :attribute deve ser um e-mail válido.',
            'enum' => 'O campo :attribute selecionado é inválido.',
            'exists' => 'O campo :attribute selecionado é inválido.',
            'image' => 'O campo :attribute deve ser uma imagem.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'max.array' => 'O campo :attribute não pode ter mais de :max itens.',
            'max.file' => 'O arquivo :attribute não pode ter mais de :max KB.',
            'max.numeric' => 'O campo :attribute não pode ser maior que :max.',
            'max.string' => 'O campo :attribute não pode ter mais de :max caracteres.',
            'mimes' => 'O campo :attribute deve ser um arquivo do tipo: :values.',
            'min.array' => 'O campo :attribute deve ter pelo menos :min itens.',
            'min.numeric' => 'O campo :attribute deve ser pelo menos :min.',
            'min.string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
            'numeric' => 'O campo :attribute deve ser um número.',
            'password.letters' => 'O campo :attribute deve conter pelo menos uma letra.',
            'password.min' => 'O campo :attribute deve ter pelo menos :min caracteres.',
            'password.mixed' => 'O campo :attribute deve conter letras maiúsculas e minúsculas.',
            'password.numbers' => 'O campo :attribute deve conter pelo menos um número.',
            'password.symbols' => 'O campo :attribute deve conter pelo menos um símbolo.',
            'password.uncompromised' => 'Esta :attribute apareceu em um vazamento de dados. Escolha outra.',
            'regex' => 'O formato do campo :attribute é inválido.',
            'required' => 'O campo :attribute é obrigatório.',
            'required_with' => 'O campo :attribute é obrigatório quando :values está preenchido.',
            'string' => 'O campo :attribute deve ser um texto.',
            'unique' => 'O campo :attribute já está em uso.',
            'uploaded' => 'O envio do arquivo :attribute falhou.',
        ];
    }

    public function attributes(): array
    {
        return [
            'address' => 'endereço',
            'allows_custom_logo' => 'logo personalizada',
            'allows_pdf' => 'PDF',
            'business_name' => 'nome comercial',
            'commercial_terms' => 'condições comerciais',
            'contact_details' => 'dados de contato',
            'current_password' => 'senha atual',
            'customer_id' => 'cliente',
            'customer_limit' => 'limite de clientes',
            'default_footer_text' => 'rodapé padrão',
            'description' => 'descrição',
            'discount' => 'desconto',
            'document' => 'documento',
            'email' => 'e-mail',
            'is_active' => 'ativo',
            'items' => 'itens',
            'items.*.description' => 'descrição do item',
            'items.*.quantity' => 'quantidade do item',
            'items.*.unit_price' => 'valor unitário do item',
            'logo' => 'logo',
            'monthly_price_cents' => 'preço mensal',
            'monthly_proposal_limit' => 'limite mensal de propostas',
            'name' => 'nome',
            'notes' => 'observações',
            'password' => 'senha',
            'phone' => 'telefone',
            'plan_id' => 'plano',
            'primary_color' => 'cor primária',
            'profile_photo' => 'foto de perfil',
            'role' => 'perfil',
            'secondary_color' => 'cor secundária',
            'settings' => 'configurações',
            'settings.*' => 'configuração',
            'slug' => 'slug',
            'title' => 'título',
            'unit_price' => 'valor unitário',
            'valid_until' => 'validade',
        ];
    }
}
