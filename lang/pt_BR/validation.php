<?php

return [
    'unique' => 'O campo :attribute já está cadastrado.',
    'exists' => 'O registro selecionado em :attribute não existe.',
    'email' => 'Informe um e-mail válido em :attribute.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'boolean' => 'O campo :attribute deve ser sim ou não.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'current_password' => 'A senha atual está incorreta.',
    'password' => ['letters' => 'A senha deve conter letras.', 'numbers' => 'A senha deve conter números.'],
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'decimal' => 'O campo :attribute deve ter entre :decimal casas decimais.',
    'regex' => 'O formato de :attribute é inválido.',
    'in' => 'O valor de :attribute é inválido.',
    'date_format' => 'O campo :attribute deve estar no formato :format.',
    'after_or_equal' => 'O campo :attribute deve ser uma data igual ou posterior a :date.',
    'min' => ['numeric' => 'O campo :attribute deve ser pelo menos :min.'],
    'max' => ['numeric' => 'O campo :attribute deve ser no máximo :max.', 'string' => 'O campo :attribute deve ter no máximo :max caracteres.'],
];
