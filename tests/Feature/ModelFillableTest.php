<?php

namespace Tests\Feature;

use App\Models\Categories;
use App\Models\Crop;
use App\Models\Cycle;
use App\Models\Items;
use App\Models\Land;
use App\Models\Movements;
use App\Models\MoveTypes;
use App\Models\Needs;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Stage;
use App\Models\Status;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Memastikan setiap kolom di $fillable benar-benar ada di tabelnya.
 * Kalau ada kolom di fillable yang tidak ada di DB, mass-assignment di controller
 * akan diam-diam gagal / error — ini bug laten untuk layer API.
 */
class ModelFillableTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: class-string<Model>}> */
    public static function modelProvider(): array
    {
        return [
            'Role' => [Role::class],
            'User' => [User::class],
            'Land' => [Land::class],
            'Crop' => [Crop::class],
            'Stage' => [Stage::class],
            'Status' => [Status::class],
            'Cycle' => [Cycle::class],
            'Phase' => [Phase::class],
            'Warehouse' => [Warehouse::class],
            'Categories' => [Categories::class],
            'Items' => [Items::class],
            'MoveTypes' => [MoveTypes::class],
            'Movements' => [Movements::class],
            'Needs' => [Needs::class],
        ];
    }

    /**
     * @dataProvider modelProvider
     *
     * @param  class-string<Model>  $modelClass
     */
    public function test_fillable_columns_exist_in_table(string $modelClass): void
    {
        /** @var Model $model */
        $model = new $modelClass;
        $table = $model->getTable();

        foreach ($model->getFillable() as $column) {
            $this->assertTrue(
                Schema::hasColumn($table, $column),
                "[$modelClass] kolom fillable '{$column}' tidak ada di tabel '{$table}'."
            );
        }
    }
}
