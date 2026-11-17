<?php

namespace Database\Seeders;

use App\Models\CatatanDokumen;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\LogAktivitas;
use App\Models\Mvp;
use App\Models\Pembayaran;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\RiwayatCatatan;
use App\Models\User;
use App\Models\Vendor;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataDummySeeder extends Seeder
{
    public function run(): void
    {
        if (Vendor::query()->exists()) {
            $this->command?->warn('Data dummy sudah ada. Pakai: php artisan migrate:fresh --seed');

            return;
        }

        $data = (new DummyDataBuilder(new DateTimeImmutable('today')))->build();
        $keuanganId = User::where('email', 'keuangan@demo.test')->value('id');
        $adminId = User::where('email', 'admin@demo.test')->value('id');

        DB::transaction(function () use ($data, $keuanganId, $adminId) {
            $vendors = [];
            foreach ($data['vendors'] as $i => $v) {
                $vendors[$i] = Vendor::create($v);
            }

            foreach ($data['chains'] as $c) {
                $pr = PurchaseRequisition::create($c['pr']);
                if (! $c['po']) {
                    continue;
                }

                $po = PurchaseOrder::create([
                    'no_po' => $c['po']['no_po'],
                    'purchase_requisition_id' => $pr->id,
                    'vendor_id' => $vendors[$c['po']['vendor_index']]->id,
                    'tgl_terbit' => $c['po']['tgl_terbit'],
                    'nilai' => $c['po']['nilai'],
                ]);

                if ($c['gr']) {
                    GoodsReceipt::create($c['gr'] + ['purchase_order_id' => $po->id]);
                }
                if (! $c['invoice']) {
                    continue;
                }

                $invoice = Invoice::create($c['invoice'] + ['purchase_order_id' => $po->id]);

                if ($c['mvp']) {
                    Mvp::create($c['mvp'] + ['invoice_id' => $invoice->id]);
                }
                if ($c['pembayaran']) {
                    Pembayaran::create($c['pembayaran'] + ['invoice_id' => $invoice->id]);
                }
                if ($c['catatan']) {
                    $catatan = CatatanDokumen::create(
                        $c['catatan'] + ['invoice_id' => $invoice->id, 'user_id' => $keuanganId]
                    );

                    if ($c['catatan']['ttd_status'] === 'sudah') {
                        RiwayatCatatan::create([
                            'catatan_dokumen_id' => $catatan->id,
                            'user_id' => $keuanganId,
                            'kolom' => 'ttd_status',
                            'nilai_lama' => 'belum',
                            'nilai_baru' => 'sudah',
                        ]);
                    }
                }
            }

            $contohLog = [
                [$keuanganId, 'login', 'Login berhasil (data contoh)'],
                [$keuanganId, 'ubah_catatan', 'Contoh perubahan catatan dokumen'],
                [$adminId, 'login', 'Login berhasil (data contoh)'],
                [$adminId, 'impor_data', 'Contoh impor data awal'],
                [null, 'login_gagal', 'Contoh percobaan login gagal'],
            ];
            foreach ($contohLog as [$userId, $aksi, $rincian]) {
                LogAktivitas::create(['user_id' => $userId, 'aksi' => $aksi, 'rincian' => $rincian]);
            }
        });

        $this->command?->info('Data dummy selesai: 20 vendor dan 60 rantai transaksi.');
    }
}
