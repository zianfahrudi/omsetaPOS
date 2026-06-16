<?php

namespace Database\Seeders;

use App\Models\District;
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

            // Kecamatan (districts) per kabupaten/kota.
            $regencyIdByCode = Regency::query()
                ->where('province_id', $province->id)
                ->pluck('id', 'code');

            foreach ($regencies as $reg) {
                $regencyId = $regencyIdByCode[$reg['id']] ?? null;
                if (! $regencyId) {
                    continue;
                }

                try {
                    $districts = Http::timeout(15)->get(self::BASE."/districts/{$reg['id']}.json")->throw()->json();
                } catch (Throwable $e) {
                    continue;
                }

                $districtRows = collect($districts)->map(fn ($d) => [
                    'regency_id' => $regencyId,
                    'code' => $d['id'],
                    'name' => $this->titleCase($d['name']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                foreach (array_chunk($districtRows, 200) as $chunk) {
                    District::query()->upsert($chunk, ['code'], ['name', 'regency_id', 'updated_at']);
                }
            }
        }

        $this->command?->info('Wilayah: '.Province::count().' provinsi, '.Regency::count().' kabupaten/kota, '.District::count().' kecamatan.');
    }

    private function seedFallback(): void
    {
        $data = [
            ['31', 'DKI Jakarta', [['3171', 'Kota Jakarta Selatan', [['3171010', 'Tebet'], ['3171020', 'Setiabudi']]], ['3173', 'Kota Jakarta Pusat', [['3173010', 'Gambir'], ['3173020', 'Tanah Abang']]]]],
            ['32', 'Jawa Barat', [['3273', 'Kota Bandung', [['3273010', 'Bandung Kulon'], ['3273020', 'Babakan Ciparay']]], ['3275', 'Kota Bekasi', [['3275010', 'Bekasi Timur'], ['3275020', 'Bekasi Barat']]]]],
            ['33', 'Jawa Tengah', [['3374', 'Kota Semarang', [['3374010', 'Semarang Tengah'], ['3374020', 'Semarang Utara']]], ['3372', 'Kota Surakarta', [['3372010', 'Laweyan'], ['3372020', 'Serengan']]]]],
            ['35', 'Jawa Timur', [['3578', 'Kota Surabaya', [['3578010', 'Genteng'], ['3578020', 'Tegalsari']]], ['3573', 'Kota Malang', [['3573010', 'Klojen'], ['3573020', 'Blimbing']]]]],
            ['73', 'Sulawesi Selatan', [['7371', 'Kota Makassar', [['7371010', 'Mariso'], ['7371020', 'Mamajang']]], ['7372', 'Kota Pare-Pare', [['7372010', 'Bacukiki'], ['7372020', 'Ujung']]]]],
        ];

        foreach ($data as [$code, $name, $regencies]) {
            $province = Province::query()->updateOrCreate(['code' => $code], ['name' => $name]);
            foreach ($regencies as [$rcode, $rname, $districts]) {
                $regency = Regency::query()->updateOrCreate(['code' => $rcode], ['province_id' => $province->id, 'name' => $rname]);
                foreach ($districts as [$dcode, $dname]) {
                    District::query()->updateOrCreate(['code' => $dcode], ['regency_id' => $regency->id, 'name' => $dname]);
                }
            }
        }
    }

    private function titleCase(string $value): string
    {
        return ucwords(mb_strtolower($value));
    }
}
