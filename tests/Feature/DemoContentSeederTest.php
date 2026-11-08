<?php

namespace Tests\Feature;

use Database\Seeders\DemoContentSeeder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DemoContentSeederTest extends TestCase
{
    public function test_catalog_rows_are_normalized_to_the_named_shape_used_by_the_seeder(): void
    {
        $seeder = new DemoContentSeeder();

        $catalogMethod = new ReflectionMethod($seeder, 'catalog');
        $catalog = $catalogMethod->invoke($seeder);

        $normalizeMethod = new ReflectionMethod($seeder, 'normalizeCatalog');
        $normalized = $normalizeMethod->invoke($seeder, $catalog);

        $this->assertCount(12, $normalized);

        foreach ($normalized as $item) {
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('summary', $item);
            $this->assertArrayHasKey('lesson', $item);
            $this->assertArrayHasKey('points', $item);
            $this->assertArrayHasKey('questions', $item);
            $this->assertIsString($item['name']);
            $this->assertNotSame('', $item['name']);
            $this->assertCount(5, $item['questions']);
            $this->assertIsArray($item['points']);
        }

        $this->assertSame('General Knowledge', $normalized[0]['name']);
        $this->assertSame('Indian History', $normalized[1]['name']);
        $this->assertSame('English', $normalized[11]['name']);
    }

    public function test_associative_catalog_rows_are_preserved_without_positional_remapping(): void
    {
        $seeder = new DemoContentSeeder();

        $normalizeMethod = new ReflectionMethod($seeder, 'normalizeCatalog');
        $input = [[
            'name' => 'Custom Subject',
            'summary' => 'Summary',
            'lesson' => 'Lesson',
            'points' => ['Point'],
            'questions' => [['Question', ['A', 'B', 'C', 'D'], 'A', 'Explanation']],
        ]];

        $this->assertSame($input, $normalizeMethod->invoke($seeder, $input));
    }
}
