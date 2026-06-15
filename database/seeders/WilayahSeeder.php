<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\Regency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Seed Indonesian provinces & regencies (kabupaten/kota) from the free
 * emsifa/api-wilayah-indonesia dataset (BPS based, MIT). Run manually:
 *   php artisan db:seed --class=WilayahSeeder
 *
 * Falls back to a minimal built-in set when offline so the app stays usable.
 */
class WilayahSeeder extends Seeder
{
    private const BASE = 'https://www.emsifa.com/api-wilayah-indonesia/api';

    public function run(): void
    {
        try {
            $provinces = Http::timeout(15)->get(self::BASE.'/provinces.json')->throw()->json();
        } catch (Throwable $e) {
            $this->command?->warn('Gagal ambil data wilayah online, pakai data fallback minimal. ('.$e->getMessage().')');
            $this->seedFallback();

            return;
        }

        foreach ($provinces as $prov) {
            $province = Province::query()->updateOrCreate(
                ['code' => $prov['id']],
                ['name' => $this->titleCase($prov['name'])],
            );

            try {
                $regencies = Http::timeout(15)->get(self::BASE."/regencies/{$prov['id']}.json")->throw()->json();
            } catch (Throwable $e) {
                continue;
            }

            $rows = collect($regencies)->map(fn ($r) => [
                'province_id' => $province->id,
                'code' => $r['id'],
                'name' => $this->titleCase($r['name']),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            foreach (array_chunk($rows, 200) as $chunk) {
                Regency::query()->upsert($chunk, ['code'], ['name', 'province_id', 'updated_at']);
            }
        }

        $this->command?->info('Wilayah: '.Province::count().' provinsi, '.Regency::count().' kabupaten/kota.');
    }

    private function seedFallback(): void
    {
        $data = [
            ['31', 'DKI Jakarta', [['3171', 'Kota Jakarta Selatan'], ['3173', 'Kota Jakarta Pusat']]],
            ['32', 'Jawa Barat', [['3273', 'Kota Bandung'], ['3275', 'Kota Bekasi']]],
            ['33', 'Jawa Tengah', [['3374', 'Kota Semarang'], ['3372', 'Kota Surakarta']]],
            ['35', 'Jawa Timur', [['3578', 'Kota Surabaya'], ['3573', 'Kota Malang']]],
            ['73', 'Sulawesi Selatan', [['7371', 'Kota Makassar'], ['7372', 'Kota Pare-Pare']]],
        ];

        foreach ($data as [$code, $name, $regencies]) {
            $province = Province::query()->updateOrCreate(['code' => $code], ['name' => $name]);
            foreach ($regencies as [$rcode, $rname]) {
                Regency::query()->updateOrCreate(['code' => $rcode], ['province_id' => $province->id, 'name' => $rname]);
            }
        }
    }

    private function titleCase(string $value): string
    {
        return ucwords(mb_strtolower($value));
    }
}
