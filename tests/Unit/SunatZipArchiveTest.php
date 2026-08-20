<?php

namespace Tests\Unit;

use App\Services\Sunat\SunatZipArchive;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class SunatZipArchiveTest extends TestCase
{
    public function test_it_creates_a_zip_with_exactly_the_named_xml(): void
    {
        $contents = (new SunatZipArchive())->create('20123456789-03-B001-00000001.xml', '<Invoice/>');
        $path = tempnam(sys_get_temp_dir(), 'zip-test-');
        file_put_contents($path, $contents);
        $zip = new ZipArchive();

        try {
            $this->assertTrue($zip->open($path));
            $this->assertSame(1, $zip->numFiles);
            $this->assertSame('20123456789-03-B001-00000001.xml', $zip->getNameIndex(0));
            $this->assertSame('<Invoice/>', $zip->getFromIndex(0));
        } finally {
            $zip->close();
            @unlink($path);
        }
    }
}
