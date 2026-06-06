<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Memastikan seluruh migration berjalan dan tabel + kolom inti terbentuk.
 */
class SchemaMigrationTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, array<int, string>> */
    private array $tablesAndColumns = [
        'roles' => ['id', 'name'],
        'users' => ['id', 'name', 'role_id', 'profil_url', 'phone_number', 'email', 'password'],
        'lands' => ['id', 'farmer_id', 'name', 'image_url', 'description', 'latitude', 'longitude', 'area'],
        'crops' => ['id', 'name', 'description', 'image_url'],
        'stages' => ['id', 'crop_id', 'name', 'order', 'duration_days'],
        'statuses' => ['id', 'name', 'type'],
        'cycles' => ['id', 'land_id', 'crop_id', 'status_id', 'name', 'description', 'start_date', 'end_date'],
        'phases' => ['id', 'cycle_id', 'stage_id', 'status_id', 'started_at', 'ended_at'],
        'warehouses' => ['id', 'farmer_id', 'name', 'image_url', 'description', 'location'],
        'categories' => ['id', 'name'],
        'items' => ['id', 'warehouse_id', 'category_id', 'name', 'unit', 'stock'],
        'move_types' => ['id', 'name', 'code'],
        'movements' => ['id', 'warehouse_id', 'item_id', 'movetype_id', 'status_id', 'land_dest', 'warehouse_dest', 'quantity', 'note'],
        'needs' => ['id', 'phase_id', 'item_id', 'quantity_needed'],
    ];

    public function test_all_tables_exist(): void
    {
        foreach (array_keys($this->tablesAndColumns) as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Tabel '{$table}' tidak terbentuk setelah migrate."
            );
        }
    }

    public function test_all_expected_columns_exist(): void
    {
        foreach ($this->tablesAndColumns as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    "Kolom '{$table}.{$column}' tidak ada."
                );
            }
        }
    }
}
