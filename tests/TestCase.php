<?php

namespace Tests;

use App\Models\OrdemServico;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function documentoXml(OrdemServico $ordem): string
    {
        $resposta = $this->get(route('documentos.show', ['token' => $ordem->token, 'formato' => 'docx']))->assertOk();
        $temporario = tempnam(sys_get_temp_dir(), 'teste-docx-');
        file_put_contents($temporario, $resposta->getContent());
        $zip = new \ZipArchive;
        try {
            $this->assertTrue($zip->open($temporario));
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            $this->assertIsString($xml);

            return $xml;
        } finally {
            unlink($temporario);
        }
    }

    public function createApplication(): Application
    {
        $compiledViews = sys_get_temp_dir().'/gold-press-tests-'.getmypid().'/views';
        $_SERVER['VIEW_COMPILED_PATH'] = $compiledViews;
        $_ENV['VIEW_COMPILED_PATH'] = $compiledViews;

        return parent::createApplication();
    }
}
