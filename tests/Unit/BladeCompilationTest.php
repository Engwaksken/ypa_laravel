<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use PHPUnit\Framework\TestCase;

class BladeCompilationTest extends TestCase
{
    public function test_all_views_compile_to_valid_php_with_a_shared_compiler(): void
    {
        $files = new Filesystem;
        $compiler = new BladeCompiler($files, sys_get_temp_dir());
        $failures = [];

        // Match view:cache: an unbalanced loop can poison subsequent compilations.
        foreach ($files->allFiles(__DIR__.'/../../resources/views') as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            try {
                token_get_all($compiler->compileString($files->get($file->getPathname())), TOKEN_PARSE);
            } catch (\Throwable $exception) {
                $failures[] = $file->getRelativePathname().': '.$exception->getMessage();
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }
}
