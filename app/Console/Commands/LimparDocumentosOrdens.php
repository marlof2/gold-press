<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class LimparDocumentosOrdens extends Command
{
    protected $signature = 'ordens:limpar-documentos {--dry-run : Apenas mostrar a quantidade de arquivos que seriam removidos}';

    protected $description = 'Remove cópias antigas de PDF e DOCX de ordens, agora gerados sob demanda';

    public function handle(): int
    {
        $disco = Storage::disk('public');
        $arquivos = array_values(array_filter($disco->allFiles('ordens'), fn (string $arquivo): bool => in_array(strtolower(pathinfo($arquivo, PATHINFO_EXTENSION)), ['pdf', 'docx'], true)));

        if ($this->option('dry-run')) {
            $this->info(count($arquivos).' documento(s) antigo(s) encontrado(s).');

            return self::SUCCESS;
        }

        foreach ($arquivos as $arquivo) {
            if (! $disco->delete($arquivo)) {
                $this->error('Não foi possível remover um documento antigo. Execute novamente após verificar as permissões.');

                return self::FAILURE;
            }
        }

        $this->info(count($arquivos).' documento(s) antigo(s) removido(s).');

        return self::SUCCESS;
    }
}
