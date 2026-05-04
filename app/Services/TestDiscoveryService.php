<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Process\Process;

class TestDiscoveryService
{
    /**
     * Discover all tests in the tests directory.
     *
     * @return array
     */
    public function discoverTests(): array
    {
        $testFiles = File::allFiles(base_path('tests'));
        $allTests = [];

        foreach ($testFiles as $file) {
            if ($file->getExtension() !== 'php' || $file->getFilename() === 'TestCase.php') {
                continue;
            }

            $className = $this->getClassNameFromFile($file->getRealPath());
            if (!$className || !class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if ($reflection->isAbstract()) {
                continue;
            }

            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if (str_starts_with($method->getName(), 'test_') || $this->hasTestAnnotation($method)) {
                    $docComment = $method->getDocComment();
                    $thaiTitle = $this->extractThaiTitle($docComment, $method->getName());
                    $category = $this->extractCategory($className);

                    $allTests[] = [
                        'class' => $className,
                        'method' => $method->getName(),
                        'name' => $method->getName(),
                        'thai_title' => $thaiTitle,
                        'category' => $category,
                        'file' => $file->getRelativePathname(),
                        'filter' => class_basename($className) . '::' . $method->getName(),
                    ];
                }
            }
        }

        return $allTests;
    }

    /**
     * Run a specific test filter and return the output.
     *
     * @param string $filter
     * @return array
     */
    public function runTest(string $filter): array
    {
        // Use full path to php and artisan for reliability
        // Force APP_ENV=testing and DB_CONNECTION=sqlite with :memory: to prevent wiping local DB
        $process = new Process(
            [PHP_BINARY, base_path('artisan'), 'test', '--without-tty', '--filter', $filter],
            base_path(),
            [
                'APP_ENV' => 'testing',
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => ':memory:',
            ]
        );
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error_output' => $process->getErrorOutput(),
            'exit_code' => $process->getExitCode(),
        ];
    }

    /**
     * Extract class name including namespace from file path.
     */
    private function getClassNameFromFile(string $path): ?string
    {
        $content = file_get_contents($path);
        $namespace = null;
        $class = null;

        if (preg_match('/namespace\s+(.+?);/', $content, $matches)) {
            $namespace = $matches[1];
        }

        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $class = $matches[1];
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }

    /**
     * Check if method has @test annotation.
     */
    private function hasTestAnnotation(ReflectionMethod $method): bool
    {
        $doc = $method->getDocComment();
        return $doc && str_contains($doc, '@test');
    }

    /**
     * Extract Thai title from DocBlock or translate method name.
     */
    private function extractThaiTitle(?string $docComment, string $methodName): string
    {
        if ($docComment) {
            // Try to find Thai characters in the doc comment
            // Pattern to match Thai characters: \x{0E00}-\x{0E7F}
            if (preg_match_all('/[^\x00-\x7F]+/', $docComment, $matches)) {
                $thaiParts = array_map('trim', $matches[0]);
                $combined = implode(' ', $thaiParts);
                // Clean up docblock characters
                $combined = preg_replace('/[\/*\s]+/', ' ', $combined);
                $combined = trim($combined);
                if (!empty($combined)) {
                    return $combined;
                }
            }
            
            // If no Thai, try to take the first line of the comment (excluding tags)
            $lines = explode("\n", $docComment);
            foreach ($lines as $line) {
                $line = trim(preg_replace('/[\/*]+/', '', $line));
                if (!empty($line) && !str_starts_with($line, '@')) {
                    return $this->translateToThai($line);
                }
            }
        }

        return $this->translateToThai($methodName);
    }

    /**
     * Basic translation of common test patterns to Thai.
     */
    private function translateToThai(string $text): string
    {
        // Remove 'test_' prefix
        $text = preg_replace('/^test_/', '', $text);
        
        // Convert snake_case to Space Separated
        $text = str_replace('_', ' ', $text);
        
        // Common mappings
        $mappings = [
            'can ' => 'สามารถ',
            'should ' => 'ควรจะ',
            'fails ' => 'ล้มเหลว',
            'success ' => 'สำเร็จ',
            'creates ' => 'สร้าง',
            'updates ' => 'อัปเดต',
            'deletes ' => 'ลบ',
            'lists ' => 'แสดงรายการ',
            'notifies ' => 'แจ้งเตือน',
            'publishes ' => 'เผยแพร่',
            'article' => 'บทความ',
            'lottery' => 'หวย',
            'result' => 'ผลลัพธ์',
            'admin' => 'แอดมิน',
            'user' => 'ผู้ใช้',
            'login' => 'ล็อกอิน',
            'setting' => 'การตั้งค่า',
            'notification' => 'การแจ้งเตือน',
            'flow' => 'ขั้นตอน',
            'refined' => 'ปรับปรุงแล้ว',
            'complete' => 'สมบูรณ์',
            'partial' => 'บางส่วน',
            'immediately' => 'ทันที',
            'live updates' => 'อัปเดตสด',
        ];

        $translated = str_ireplace(array_keys($mappings), array_values($mappings), $text);
        
        return trim($translated);
    }

    /**
     * Extract a human-readable category from the class name.
     */
    private function extractCategory(string $className): string
    {
        $baseName = class_basename($className);
        
        $mappings = [
            'Article' => 'บทความ',
            'Order' => 'คำสั่งซื้อ',
            'Lottery' => 'หวย',
            'Line' => 'LINE',
            'PhoneNumber' => 'เบอร์โทรศัพท์',
            'Number' => 'เบอร์โทรศัพท์',
            'Customer' => 'ลูกค้า',
            'Sales' => 'เอกสารขาย',
            'Estimate' => 'Lead เลือกเบอร์',
            'Contact' => 'ข้อความติดต่อ',
            'Analytics' => 'สถิติ (GA4)',
            'Facebook' => 'Social',
            'User' => 'ผู้ใช้งาน',
            'Permission' => 'สิทธิ์การใช้งาน',
        ];

        foreach ($mappings as $key => $thai) {
            if (str_contains($baseName, $key)) {
                return $thai;
            }
        }

        return 'ทั่วไป';
    }
}
