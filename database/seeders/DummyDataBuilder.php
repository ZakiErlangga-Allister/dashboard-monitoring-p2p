<?php

namespace Database\Seeders;

use DateTimeImmutable;

/**
 * Pembangkit data dummy P2P (tanpa akses basis data, jadi mudah diuji).
 * Semua tanggal dihitung relatif terhadap $today supaya skenario
 * (terlambat, mendekati jatuh tempo, dan seterusnya) selalu valid.
 */
class DummyDataBuilder
{
    public const BANKS = ['BRI', 'Mandiri', 'BNI', 'BCA'];
    public const JENIS = ['non sparepart', 'sparepart', 'bahan baku'];

    private const NAMA_VENDOR = [
        'PT Alfa Material', 'CV Beta Teknik', 'PT Gamma Kimia', 'PT Delta Logistik',
        'PT Epsilon Mesin', 'CV Zeta Perkakas', 'PT Eta Pelumas', 'PT Theta Kemasan',
        'CV Iota Listrik', 'PT Kappa Pipa', 'PT Lambda Baja', 'CV Mu Karung',
        'PT Nu Filter', 'PT Xi Katup', 'CV Omikron Bearing', 'PT Pi Instrumen',
        'PT Rho Kabel', 'CV Sigma Seal', 'PT Tau Gasket', 'PT Upsilon Industri',
    ];

    /**
     * 14 invoice yang menunggu pembayaran.
     * offset = jatuh tempo SAP dikurangi hari ini (negatif = sudah lewat).
     * miss   = butir kelengkapan yang belum ada: bg, skf, surat, rek.
     * sel    = jenis selisih data: tgl (jatuh tempo beda 1 hari), bg (status bank garansi beda).
     */
    private const MENUNGGU = [
        ['bank' => 'BRI',     'tenor' => 14, 'offset' => 1,  'miss' => [],               'ttd' => 'sudah', 'sel' => null],
        ['bank' => 'Mandiri', 'tenor' => 30, 'offset' => 2,  'miss' => [],               'ttd' => 'sudah', 'sel' => 'tgl'],
        ['bank' => 'BNI',     'tenor' => 14, 'offset' => 3,  'miss' => [],               'ttd' => 'sudah', 'sel' => 'bg'],
        ['bank' => 'BCA',     'tenor' => 30, 'offset' => 9,  'miss' => [],               'ttd' => 'sudah', 'sel' => 'tgl'],
        ['bank' => 'BRI',     'tenor' => 14, 'offset' => 0,  'miss' => [],               'ttd' => 'sudah', 'sel' => null],
        ['bank' => 'Mandiri', 'tenor' => 30, 'offset' => 14, 'miss' => [],               'ttd' => 'sudah', 'sel' => 'bg'],
        ['bank' => 'BCA',     'tenor' => 14, 'offset' => 2,  'miss' => [],               'ttd' => 'belum', 'sel' => null],
        ['bank' => 'BNI',     'tenor' => 30, 'offset' => 7,  'miss' => [],               'ttd' => 'belum', 'sel' => 'tgl'],
        ['bank' => 'Mandiri', 'tenor' => 14, 'offset' => -1, 'miss' => [],               'ttd' => 'belum', 'sel' => null],
        ['bank' => 'BRI',     'tenor' => 30, 'offset' => 5,  'miss' => ['bg'],           'ttd' => 'belum', 'sel' => 'bg'],
        ['bank' => 'Mandiri', 'tenor' => 14, 'offset' => 4,  'miss' => ['skf'],          'ttd' => 'sudah', 'sel' => 'tgl'],
        ['bank' => 'BCA',     'tenor' => 30, 'offset' => -3, 'miss' => ['surat'],        'ttd' => 'belum', 'sel' => null],
        ['bank' => 'BNI',     'tenor' => 30, 'offset' => 11, 'miss' => ['rek'],          'ttd' => 'sudah', 'sel' => 'tgl'],
        ['bank' => 'BRI',     'tenor' => 14, 'offset' => 6,  'miss' => ['bg', 'rek'],    'ttd' => 'belum', 'sel' => null],
    ];

    private DateTimeImmutable $today;
    private int $n = 0;
    private array $vendors = [];
    private array $perBank = [];
    private array $cursorBank = [];

    public function __construct(DateTimeImmutable $today)
    {
        $this->today = $today->setTime(0, 0);
    }

    public function build(): array
    {
        mt_srand(2026); // supaya hasil selalu sama
        $this->buildVendors();

        $chains = [];

        // A. PR saja (8): PO belum terbit
        for ($i = 0; $i < 8; $i++) {
            $chains[] = $this->chain('pr', $i, $this->ago(1 + $i * 3), null, null, null);
        }
        // B. PO terbit (8): belum ada GR
        for ($i = 0; $i < 8; $i++) {
            $chains[] = $this->chain('po', $i, null, $this->ago(2 + $i * 2), null, null);
        }
        // C. Barang diterima (10): GR ada, invoice belum
        for ($i = 0; $i < 10; $i++) {
            $chains[] = $this->chain('gr', $i, null, null, $this->ago(3 + $i * 2), null);
        }
        // D. Invoice diterima, verifikasi berjalan (10)
        for ($i = 0; $i < 10; $i++) {
            $chains[] = $this->chain('invoice', $i, null, null, null, $this->ago(6 + $i));
        }
        // E. Menunggu pembayaran (14): skenario eksplisit
        foreach (self::MENUNGGU as $i => $sk) {
            $due = $this->today->modify(($sk['offset'] >= 0 ? '+' : '') . $sk['offset'] . ' days');
            $terima = $due->modify('-' . $sk['tenor'] . ' days');
            $chains[] = $this->chain('menunggu_bayar', $i, null, null, null, $terima, $sk);
        }
        // F. Dibayar (10)
        for ($i = 0; $i < 10; $i++) {
            $chains[] = $this->chain('dibayar', $i, null, null, null, $this->ago(35 + $i * 2));
        }

        return ['vendors' => $this->vendors, 'chains' => $chains];
    }

    private function buildVendors(): void
    {
        foreach (self::NAMA_VENDOR as $i => $nama) {
            $bank = self::BANKS[$i % 4];
            $this->vendors[$i] = [
                'nama' => $nama,
                'bank_penerima' => $bank,
                'no_rekening' => sprintf('0000-%02d-%06d', $i + 1, 100000 + $i * 137),
            ];
            $this->perBank[$bank][] = $i;
        }
    }

    private function ago(int $days): DateTimeImmutable
    {
        return $this->today->modify('-' . $days . ' days');
    }

    private function d(DateTimeImmutable $x): string
    {
        return $x->format('Y-m-d');
    }

    private function vendorFor(string $tahap, int $i, ?string $bank): int
    {
        if ($bank !== null) {
            $list = $this->perBank[$bank];
            $this->cursorBank[$bank] = ($this->cursorBank[$bank] ?? -1) + 1;

            return $list[$this->cursorBank[$bank] % count($list)];
        }

        return ($i * 3 + strlen($tahap)) % count($this->vendors);
    }

    /**
     * Satu rantai transaksi. Tanggal dihitung mundur dari titik jangkar:
     * PR < PO < GR < terima kasir < MVP < serah Keuangan < bayar.
     */
    private function chain(
        string $tahap,
        int $i,
        ?DateTimeImmutable $tglPr,
        ?DateTimeImmutable $tglPo,
        ?DateTimeImmutable $tglGr,
        ?DateTimeImmutable $tglTerima,
        ?array $sk = null
    ): array {
        $this->n++;
        $n = $this->n;

        // titik jangkar -> tanggal tahap sebelumnya
        if ($tglTerima !== null) {
            $tglGr = $tglTerima->modify('-' . (4 + $i % 5) . ' days');
        }
        if ($tglGr !== null) {
            $tglPo = $tglGr->modify('-' . (14 + $i % 7) . ' days');
        }
        if ($tglPo !== null) {
            $tglPr = $tglPo->modify('-' . (7 + $i % 4) . ' days');
        }

        $bank = $sk['bank'] ?? null;
        $vendorIdx = $tahap === 'pr' ? null : $this->vendorFor($tahap, $i, $bank);
        $nilai = 15000000 + mt_rand(0, 480) * 1000000;

        $c = [
            'tahap' => $tahap,
            'pr' => [
                'no_pr' => sprintf('PR-26%04d', $n),
                'jenis' => self::JENIS[$n % 3],
                'tgl_disetujui' => $this->d($tglPr),
            ],
            'po' => null, 'gr' => null, 'invoice' => null,
            'mvp' => null, 'pembayaran' => null, 'catatan' => null,
            'skenario' => $sk,
        ];

        if ($tglPo === null) {
            return $c;
        }
        $c['po'] = [
            'no_po' => sprintf('PO-45%08d', 100000 + $n),
            'vendor_index' => $vendorIdx,
            'tgl_terbit' => $this->d($tglPo),
            'nilai' => $nilai,
        ];

        if ($tglGr === null) {
            return $c;
        }
        $c['gr'] = [
            'no_gr' => sprintf('GR-50%08d', 100000 + $n),
            'tgl_gr' => $this->d($tglGr),
        ];

        if ($tglTerima === null) {
            return $c;
        }

        // Invoice
        $tenor = $sk['tenor'] ?? ($i % 2 === 0 ? 14 : 30);
        $due = $tglTerima->modify('+' . $tenor . ' days');
        $mitraku = $due;
        $bgSap = 'Valid';
        $bgMitraku = 'Valid';
        $sel = $sk['sel'] ?? null;
        if ($tahap === 'invoice' && in_array($i, [1, 3], true)) {
            $sel = 'tgl'; // dua invoice tahap verifikasi juga punya selisih
        }
        if ($sel === 'tgl') {
            $mitraku = $due->modify('-1 day');
        } elseif ($sel === 'bg') {
            $bgMitraku = 'Tidak valid';
        }
        $c['invoice'] = [
            'no_invoice' => sprintf('INV-2026-%04d', $n),
            'tgl_terima_kasir' => $this->d($tglTerima),
            'tenor_hari' => $tenor,
            'jatuh_tempo_sap' => $this->d($due),
            'jatuh_tempo_mitraku' => $this->d($mitraku),
            'status_bg_sap' => $bgSap,
            'status_bg_mitraku' => $bgMitraku,
            'nilai' => $nilai,
        ];

        // MVP
        if ($tahap === 'invoice') {
            if ($i % 2 === 1) { // MVP sudah terbit tetapi belum disetujui
                $c['mvp'] = [
                    'no_mvp' => sprintf('MVP-2026-%04d', $n),
                    'tgl_terbit' => $this->d($tglTerima->modify('+3 days')),
                    'tgl_disetujui' => null,
                    'tgl_serah_keuangan' => null,
                ];
            }

            return $c;
        }

        $serah = $tglTerima->modify('+6 days');
        $c['mvp'] = [
            'no_mvp' => sprintf('MVP-2026-%04d', $n),
            'tgl_terbit' => $this->d($tglTerima->modify('+3 days')),
            'tgl_disetujui' => $this->d($tglTerima->modify('+5 days')),
            'tgl_serah_keuangan' => $this->d($serah),
        ];

        if ($tahap === 'menunggu_bayar') {
            $miss = $sk['miss'];
            $ttd = $sk['ttd'];
            $lengkap = $miss === [];
            $c['catatan'] = [
                'bg_ada' => ! in_array('bg', $miss, true),
                'skf_ada' => ! in_array('skf', $miss, true),
                'surat_pernyataan_ada' => ! in_array('surat', $miss, true),
                'rekening_ada' => ! in_array('rek', $miss, true),
                'ttd_status' => $ttd,
                'ttd_tanggal' => $ttd === 'sudah' ? $this->d($tglTerima->modify('+7 days')) : null,
                'lokasi_arsip' => $lengkap
                    ? ($ttd === 'sudah' ? 'Lemari ' . $due->format('d') . '-A' : 'Otorisator')
                    : null,
            ];

            return $c;
        }

        // dibayar
        $bayar = $tglTerima->modify('+' . ($tenor - 2) . ' days');
        $c['pembayaran'] = [
            'bank_penerima' => $this->vendors[$vendorIdx]['bank_penerima'],
            'tgl_bayar' => $this->d($bayar),
        ];
        $c['catatan'] = [
            'bg_ada' => true,
            'skf_ada' => true,
            'surat_pernyataan_ada' => true,
            'rekening_ada' => true,
            'ttd_status' => 'sudah',
            'ttd_tanggal' => $this->d($tglTerima->modify('+7 days')),
            'lokasi_arsip' => 'Lemari ' . $due->format('d') . '-B',
        ];

        return $c;
    }
}
