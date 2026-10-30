<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModeloWhatsApp extends Model
{
    use HasFactory;

    protected $table = 'modelos_whatsapp';

    protected $fillable = ['nome', 'chave', 'conteudo', 'posicao'];

    protected function casts(): array
    {
        return ['posicao' => 'integer'];
    }
}
