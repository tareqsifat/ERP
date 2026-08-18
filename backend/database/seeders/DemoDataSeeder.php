<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\App\Models\AccountingCategory;
use Modules\Accounting\App\Models\BankAccount;
use Modules\Accounting\App\Models\Cheque;
use Modules\Accounting\App\Models\PartyBill;
use Modules\Accounting\App\Services\ChequeService;
use Modules\Accounting\App\Services\VoucherService;
use Modules\Booking\App\Models\Booking;
use Modules\Hrm\App\Models\Designation;
use Modules\Hrm\App\Models\Employee;
use Modules\Hrm\App\Services\SalaryService;
use Modules\Location\App\Models\Location;
use Modules\Location\App\Services\StockTransferService;
use Modules\Order\App\Models\Order;
use Modules\Order\App\Services\OrderNumberGenerator;
use Modules\Party\App\Models\Party;
use Modules\Production\App\Models\CutTicket;
use Modules\Production\App\Models\Line;
use Modules\Production\App\Models\PieceSerial;
use Modules\Production\App\Services\CuttingService;
use Modules\Production\App\Services\QcService;
use Modules\Production\App\Services\SewingService;
use Modules\RawMaterial\App\Models\RawMaterial;
use Modules\RawMaterial\App\Models\RawMaterialPurchaseOrder;
use Modules\RawMaterial\App\Services\PurchaseOrderNumberGenerator;
use Modules\RawMaterial\App\Services\RawMaterialStockService;
use Modules\Shipment\App\Models\Shipment;
use Modules\Shipment\App\Services\ShipmentInvoiceNumberGenerator;
use Modules\Subcontract\App\Models\SubcontractOrder;
use Modules\Subcontract\App\Services\SubcontractInwardService;
use Modules\Subcontract\App\Services\SubcontractNumberGenerator;
use Modules\Subcontract\App\Services\SubcontractOutwardService;

/**
 * todo.md Phase 9 — realistic, connected demo/test data walking every
 * module through at least one real business cycle: Order -> Booking ->
 * Cutting -> Sewing -> QC -> Finished Goods -> Stock Transfer ->
 * Shipment, one Outward and one Inward Subcontract cycle, a handful of
 * Accounting vouchers/a cheque, one full Employee + Salary cycle, and
 * all 4 non-Factory locations (Main Store + 3 Showrooms) populated with
 * real Finished Goods movements.
 *
 * Deliberately calls the real App\Services classes (CuttingService,
 * QcService, VoucherService, SalaryService, ...) rather than
 * `Model::create()`-ing rows directly — this is the same code path a
 * real user hits through the API, so the seeded data is exactly as
 * self-consistent (correct sequence numbers, correct ledger postings,
 * correct traceability chain) as data created through the UI would be.
 *
 * Guarded the same way AdminUserSeeder is: refuses to run in
 * `production` unless explicitly forced, and the demo users it creates
 * use one clearly-labeled, non-secret password — this is seed data for
 * a demo/staging environment, not real credentials, and
 * `user_usage_guide.md` says so explicitly.
 */
class DemoDataSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Demo@12345';

    public function run(): void
    {
        if (app()->environment('production') && ! env('DEMO_SEED_FORCE')) {
            $this->command?->warn(
                'Refusing to seed demo/test data in production without '
                .'DEMO_SEED_FORCE set explicitly. This seeder creates '
                .'realistic-looking but fake business records and demo '
                .'logins — not appropriate for a real client\'s data.'
            );

            return;
        }

        if (Party::query()->where('name', 'Global Fashion Buyers')->exists()) {
            $this->command?->info('Demo data already present — skipping DemoDataSeeder.');

            return;
        }

        $users = $this->seedUsers();
        $parties = $this->seedParties();
        $rawMaterials = $this->seedRawMaterials($parties);
        $this->seedPurchaseOrder($parties, $rawMaterials, $users);

        [$order, $mainStore] = $this->seedOrderThroughFinishedGoods($parties, $rawMaterials, $users);
        $this->seedStockTransfers($order, $mainStore, $users);
        $this->seedShipment($order, $users);
        $this->seedSubcontractCycles($order, $parties, $rawMaterials, $users);
        $this->seedAccounting($order, $parties, $users);
        $this->seedHrm($users);

        $this->command?->warn('Demo users seeded — DEMO DATA ONLY, do not use in production:');
        foreach ($users as $role => $user) {
            $this->command?->line("  {$role}: {$user->email} / ".self::DEMO_PASSWORD);
        }
    }

    /** @return array<string, User> keyed by role name */
    private function seedUsers(): array
    {
        $showroom1 = Location::where('name', 'Showroom 1')->firstOrFail();

        $roleUsers = [
            'Merchandiser' => ['name' => 'Nasrin Akter', 'email' => 'merchandiser@vishesh-textiles.example'],
            'Commercial' => ['name' => 'Shafiul Islam', 'email' => 'commercial@vishesh-textiles.example'],
            'Accountant' => ['name' => 'Farhana Yasmin', 'email' => 'accountant@vishesh-textiles.example'],
            'Production' => ['name' => 'Abdul Kader', 'email' => 'production@vishesh-textiles.example'],
            'Cutting Master' => ['name' => 'Jashim Uddin', 'email' => 'cutting.master@vishesh-textiles.example'],
            'Line Supervisor' => ['name' => 'Selina Begum', 'email' => 'line.supervisor@vishesh-textiles.example'],
            'Store Keeper (Raw Material)' => ['name' => 'Mizanur Rahman', 'email' => 'store.rawmaterial@vishesh-textiles.example'],
            'Store Keeper (Finished Goods)' => ['name' => 'Kamrul Hasan', 'email' => 'store.finishedgoods@vishesh-textiles.example'],
            'Showroom Staff' => ['name' => 'Ruma Chowdhury', 'email' => 'showroom.staff@vishesh-textiles.example', 'location_id' => $showroom1->id],
            'Buyer' => ['name' => 'Buyer Portal Access', 'email' => 'buyer@vishesh-textiles.example'],
        ];

        $users = [];
        foreach ($roleUsers as $role => $attrs) {
            $user = User::create([
                'name' => $attrs['name'],
                'email' => $attrs['email'],
                'password' => self::DEMO_PASSWORD,
                'is_active' => true,
                'location_id' => $attrs['location_id'] ?? null,
            ]);
            $user->assignRole($role);
            $users[$role] = $user;
        }

        return $users;
    }

    /** @return array<string, Party> */
    private function seedParties(): array
    {
        $parties = [
            'buyer1' => Party::create(['name' => 'Global Fashion Buyers', 'type' => 'buyer', 'email' => 'contact@globalfashion.example', 'phone' => '+1-212-555-0101', 'country' => 'USA', 'is_active' => true]),
            'buyer2' => Party::create(['name' => 'EuroStyle Imports', 'type' => 'buyer', 'email' => 'orders@eurostyle.example', 'phone' => '+34-91-555-0102', 'country' => 'Spain', 'is_active' => true]),
            'supplier1' => Party::create(['name' => 'Prime Fabrics Ltd', 'type' => 'supplier', 'email' => 'sales@primefabrics.example', 'phone' => '+880-2-555-0201', 'country' => 'Bangladesh', 'is_active' => true]),
            'supplier2' => Party::create(['name' => 'Trimline Accessories', 'type' => 'supplier', 'email' => 'sales@trimline.example', 'phone' => '+880-2-555-0202', 'country' => 'Bangladesh', 'is_active' => true]),
            'subcontractor1' => Party::create(['name' => 'Rahman Stitching House', 'type' => 'subcontractor', 'email' => 'info@rahmanstitching.example', 'phone' => '+880-2-555-0301', 'country' => 'Bangladesh', 'is_active' => true]),
        ];

        return $parties;
    }

    /** @return array<string, RawMaterial> */
    private function seedRawMaterials(array $parties): array
    {
        return [
            'cotton' => RawMaterial::create([
                'name' => 'Cotton Single Jersey', 'category' => 'fabric', 'unit' => 'kg',
                'reorder_level' => 50, 'default_supplier_id' => $parties['supplier1']->id, 'unit_cost' => 4.5, 'is_active' => true,
            ]),
            'poly_rib' => RawMaterial::create([
                'name' => 'Poly-Cotton Rib', 'category' => 'fabric', 'unit' => 'kg',
                'reorder_level' => 20, 'default_supplier_id' => $parties['supplier1']->id, 'unit_cost' => 5.0, 'is_active' => true,
            ]),
            'label' => RawMaterial::create([
                'name' => 'Woven Label', 'category' => 'trim', 'unit' => 'pcs',
                'reorder_level' => 500, 'default_supplier_id' => $parties['supplier2']->id, 'unit_cost' => 0.05, 'is_active' => true,
            ]),
        ];
    }

    private function seedPurchaseOrder(array $parties, array $rawMaterials, array $users): void
    {
        $factory = Location::where('type', 'factory')->firstOrFail();

        // PO 1 (Prime Fabrics): both fabrics used by the cutting chain
        // below — receiving both up front avoids a materially-misleading
        // "negative raw material stock" showing up in the Stock Report
        // once the Cutting/Subcontract chain issues against them (raw
        // material issuing has no negative-stock guard by design —
        // failed_doc.md §7 — but demo data shouldn't look broken because
        // of it).
        $this->receivePurchaseOrder($parties['supplier1'], $factory, $users, [
            ['material' => $rawMaterials['cotton'], 'quantity' => '500', 'unit_price' => 4.5],
            ['material' => $rawMaterials['poly_rib'], 'quantity' => '80', 'unit_price' => 5.0],
        ]);

        // PO 2 (Trimline Accessories): trims.
        $this->receivePurchaseOrder($parties['supplier2'], $factory, $users, [
            ['material' => $rawMaterials['label'], 'quantity' => '3000', 'unit_price' => 0.05],
        ]);
    }

    private function receivePurchaseOrder(Party $supplier, Location $location, array $users, array $lines): void
    {
        DB::transaction(function () use ($supplier, $location, $users, $lines) {
            $year = (int) now()->year;
            $sequence = PurchaseOrderNumberGenerator::nextFor($year);

            $po = new RawMaterialPurchaseOrder([
                'supplier_id' => $supplier->id,
                'location_id' => $location->id,
                'status' => 'ordered',
                'order_date' => now()->subDays(20)->toDateString(),
                'expected_date' => now()->subDays(10)->toDateString(),
                'created_by' => $users['Store Keeper (Raw Material)']->id,
                'remarks' => 'Demo data — initial stock-up.',
            ]);
            $po->year = $year;
            $po->sequence_no = $sequence;
            $po->po_no = PurchaseOrderNumberGenerator::format($year, $sequence);
            $po->save();

            foreach ($lines as $line) {
                $item = $po->items()->create([
                    'raw_material_id' => $line['material']->id,
                    'quantity_ordered' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total_price' => round($line['quantity'] * $line['unit_price'], 2),
                ]);

                // Receive it in full — populates real raw material stock
                // via the same ledger every real receipt uses.
                RawMaterialStockService::receipt($line['material'], $location, (string) $line['quantity'], $users['Store Keeper (Raw Material)']->id, $po);
                $item->quantity_received = $line['quantity'];
                $item->save();
            }

            $po->refreshStatus();
        });
    }

    /** @return array{0: Order, 1: Location} */
    private function seedOrderThroughFinishedGoods(array $parties, array $rawMaterials, array $users): array
    {
        $factory = Location::where('type', 'factory')->firstOrFail();
        $mainStore = Location::where('name', 'Main Store')->firstOrFail();

        $order = DB::transaction(function () use ($parties, $users) {
            $order = Order::create([
                'party_id' => $parties['buyer1']->id,
                'merchandiser_id' => $users['Merchandiser']->id,
                'title' => 'Polo Shirt Program — Spring',
                'shipment_mode' => 'sea',
                'payment_mode' => 'lc',
                'year' => now()->year,
                'season' => 'Spring',
                'status' => 'approved',
                'remarks' => 'Demo data — full traceability chain.',
            ]);
            $order->order_no = OrderNumberGenerator::generateFor($order);
            $order->save();

            $order->lineItems()->create([
                'style' => 'GF-101', 'color' => 'Navy', 'item' => 'Polo Shirt',
                'quantity' => 200, 'unit_price' => 6.5, 'total_price' => round(200 * 6.5, 2),
            ]);
            $order->lineItems()->create([
                'style' => 'GF-102', 'color' => 'White', 'item' => 'Polo Shirt',
                'quantity' => 150, 'unit_price' => 6.5, 'total_price' => round(150 * 6.5, 2),
            ]);
            $order->recalculateGrandTotal();

            return $order;
        });

        $booking = Booking::create([
            'order_id' => $order->id,
            'preparer_id' => $users['Merchandiser']->id,
            'booking_date' => now()->subDays(18)->toDateString(),
            'composition' => '100% Cotton',
            'status' => 'confirmed',
        ]);
        $booking->lineItems()->create(['style' => 'GF-101', 'color' => 'Navy', 'quantity' => 200, 'unit_price' => 6.5, 'total_value' => round(200 * 6.5, 2)]);
        $booking->lineItems()->create(['style' => 'GF-102', 'color' => 'White', 'quantity' => 150, 'unit_price' => 6.5, 'total_value' => round(150 * 6.5, 2)]);

        // Two Cut Tickets — one per style — so the demo data shows more
        // than one style/color/size moving through the same chain.
        $cutTicket1 = new CutTicket([
            'order_id' => $order->id, 'booking_id' => $booking->id,
            'style' => 'GF-101', 'color' => 'Navy', 'size' => 'M',
            'cut_date' => now()->subDays(15)->toDateString(),
            'cutting_master_id' => $users['Cutting Master']->id,
            'raw_material_id' => $rawMaterials['cotton']->id,
            'fabric_consumed' => 50, 'location_id' => $factory->id,
            'bundle_size' => 10, 'planned_quantity' => 30,
        ]);
        $cutTicket1->status = 'draft';
        $cutTicket1->save();
        $cutTicket1 = CuttingService::finalize($cutTicket1, $users['Cutting Master']->id);

        $cutTicket2 = new CutTicket([
            'order_id' => $order->id, 'booking_id' => $booking->id,
            'style' => 'GF-102', 'color' => 'White', 'size' => 'L',
            'cut_date' => now()->subDays(14)->toDateString(),
            'cutting_master_id' => $users['Cutting Master']->id,
            'raw_material_id' => $rawMaterials['cotton']->id,
            'fabric_consumed' => 20, 'location_id' => $factory->id,
            'bundle_size' => 10, 'planned_quantity' => 20,
        ]);
        $cutTicket2->status = 'draft';
        $cutTicket2->save();
        $cutTicket2 = CuttingService::finalize($cutTicket2, $users['Cutting Master']->id);

        $line = Line::firstOrCreate(['name' => 'Line A'], ['capacity' => 40, 'is_active' => true]);

        // Sew every bundle from both cut tickets through to 'sewn'
        // (leaves one CutTicket 1 bundle deliberately unassigned below
        // to show a mid-flight 'cut' status in the demo data).
        $bundles1 = $cutTicket1->bundles()->orderBy('bundle_no')->get();
        $bundles2 = $cutTicket2->bundles()->orderBy('bundle_no')->get();

        foreach ($bundles1->take(2) as $bundle) {
            SewingService::assignToLine($bundle, $line);
            SewingService::logOutput($bundle);
        }
        foreach ($bundles2 as $bundle) {
            SewingService::assignToLine($bundle, $line);
            SewingService::logOutput($bundle);
        }

        // QC: pass most sewn pieces from the two style-1 bundles (8 of
        // 20), reject 2 as defective; pass every style-2 piece.
        $sewnStyle1 = PieceSerial::whereIn('bundle_id', $bundles1->take(2)->pluck('id'))->get();

        foreach ($sewnStyle1->take(8) as $piece) {
            QcService::pass($piece, $mainStore, $users['Production']->id);
        }
        foreach ($sewnStyle1->slice(8, 2) as $piece) {
            QcService::reject($piece, 'Stitch defect at collar seam', $users['Production']->id);
        }

        $sewnStyle2 = PieceSerial::whereIn('bundle_id', $bundles2->pluck('id'))->get();
        foreach ($sewnStyle2 as $piece) {
            QcService::pass($piece, $mainStore, $users['Production']->id);
        }

        return [$order, $mainStore];
    }

    private function seedStockTransfers(Order $order, Location $mainStore, array $users): void
    {
        $showroom1 = Location::where('name', 'Showroom 1')->firstOrFail();
        $showroom2 = Location::where('name', 'Showroom 2')->firstOrFail();
        $showroom3 = Location::where('name', 'Showroom 3')->firstOrFail();

        $t1 = StockTransferService::dispatch($mainStore, $showroom1, $order, 'GF-101', 'Navy', 'M', 5, $users['Store Keeper (Finished Goods)']->id);
        StockTransferService::receive($t1, 5, $users['Showroom Staff']->id);

        $t2 = StockTransferService::dispatch($mainStore, $showroom2, $order, 'GF-102', 'White', 'L', 8, $users['Store Keeper (Finished Goods)']->id);
        StockTransferService::receive($t2, 8, $users['Showroom Staff']->id);

        // Deliberately under-received to demonstrate the discrepancy
        // path — 3 dispatched, only 2 actually arrive.
        $t3 = StockTransferService::dispatch($mainStore, $showroom3, $order, 'GF-101', 'Navy', 'M', 3, $users['Store Keeper (Finished Goods)']->id);
        StockTransferService::receive($t3, 2, $users['Showroom Staff']->id);
    }

    private function seedShipment(Order $order, array $users): void
    {
        DB::transaction(function () use ($order, $users) {
            $year = (int) now()->year;
            $sequence = ShipmentInvoiceNumberGenerator::nextFor($year);

            $shipment = new Shipment([
                'order_id' => $order->id,
                'created_by' => $users['Commercial']->id,
                'total_quantity' => 12,
                'total_cbm' => 8.5,
                'shipment_date' => now()->subDays(2)->toDateString(),
                'status' => 'shipped',
                'remarks' => 'Demo data. NOTE: does not deduct Finished Goods stock — see failed_doc.md Pass 3 / Modules/Shipment known gap.',
            ]);
            $shipment->year = $year;
            $shipment->sequence_no = $sequence;
            $shipment->invoice_no = ShipmentInvoiceNumberGenerator::format($year, $sequence);
            $shipment->save();
        });
    }

    private function seedSubcontractCycles(Order $order, array $parties, array $rawMaterials, array $users): void
    {
        $factory = Location::where('type', 'factory')->firstOrFail();

        // --- Outward: we send raw material out, subcontractor cuts &
        // sews it, most pieces come back, a couple are written off.
        $outward = DB::transaction(function () use ($order, $parties, $rawMaterials, $factory, $users) {
            $year = (int) now()->year;
            $sequence = SubcontractNumberGenerator::nextFor($year);

            $sc = new SubcontractOrder([
                'direction' => 'outward', 'party_id' => $parties['subcontractor1']->id, 'order_id' => $order->id,
                'style' => 'GF-101', 'color' => 'Navy', 'size' => 'M', 'rate' => 2.5, 'rate_unit' => 'piece',
                'quantity_expected' => 10, 'raw_material_id' => $rawMaterials['cotton']->id, 'raw_material_quantity' => 15,
                'location_id' => $factory->id, 'expected_date' => now()->addDays(7)->toDateString(),
                'remarks' => 'Demo data — outward job work.',
            ]);
            $sc->created_by = $users['Merchandiser']->id;
            $sc->year = $year;
            $sc->sequence_no = $sequence;
            $sc->subcontract_no = SubcontractNumberGenerator::format($year, $sequence);
            $sc->save();

            return $sc;
        });

        $outwardCutTicket = SubcontractOutwardService::issueRawMaterial(
            $outward, now()->subDays(10)->toDateString(), $users['Cutting Master']->id, 10, 10, $users['Merchandiser']->id,
        );
        $issuedPieceIds = PieceSerial::whereHas('bundle', fn ($q) => $q->where('cut_ticket_id', $outwardCutTicket->id))
            ->pluck('id')->all();

        SubcontractOutwardService::returnPieces(
            $outward,
            returnedPieceSerialIds: array_slice($issuedPieceIds, 0, 8),
            writtenOffPieceSerialIds: array_slice($issuedPieceIds, 8, 2),
            returnedBy: $users['Production']->id,
        );

        // --- Inward: subcontractor's own job, our factory sews it as
        // paid capacity, dispatched back out once QC'd.
        $inward = DB::transaction(function () use ($parties, $rawMaterials, $factory, $users) {
            $year = (int) now()->year;
            $sequence = SubcontractNumberGenerator::nextFor($year);

            $sc = new SubcontractOrder([
                'direction' => 'inward', 'party_id' => $parties['subcontractor1']->id,
                'style' => 'EXT-JOB-01', 'color' => 'Grey', 'size' => 'L', 'rate' => 3.0, 'rate_unit' => 'piece',
                'quantity_expected' => 10, 'raw_material_id' => $rawMaterials['poly_rib']->id, 'raw_material_quantity' => 5,
                'location_id' => $factory->id, 'expected_date' => now()->addDays(5)->toDateString(),
                'remarks' => 'Demo data — inward job work capacity sold to Rahman Stitching House.',
            ]);
            $sc->created_by = $users['Merchandiser']->id;
            $sc->year = $year;
            $sc->sequence_no = $sequence;
            $sc->subcontract_no = SubcontractNumberGenerator::format($year, $sequence);
            $sc->save();

            return $sc;
        });

        // CutTicket.order_id is NOT NULL even for an inward job — the
        // SubcontractOrder itself has no order_id (the work isn't for
        // any of our own Orders), but every serial still needs *some*
        // Order to hang off (see SubcontractInwardModuleTest's "Inward
        // still needs a real Order to hang the Cut Ticket's serials
        // off" comment) — reuses the same demo Order for convenience.
        $inwardCutTicket = new CutTicket([
            'order_id' => $order->id,
            'style' => 'EXT-JOB-01', 'color' => 'Grey', 'size' => 'L',
            'cut_date' => now()->subDays(6)->toDateString(),
            'cutting_master_id' => $users['Cutting Master']->id,
            'raw_material_id' => $rawMaterials['poly_rib']->id,
            'fabric_consumed' => 5, 'location_id' => $factory->id,
            'bundle_size' => 10, 'planned_quantity' => 10,
            'inward_subcontract_order_id' => $inward->id,
        ]);
        $inwardCutTicket->status = 'draft';
        $inwardCutTicket->save();
        $inwardCutTicket = CuttingService::finalize($inwardCutTicket, $users['Cutting Master']->id);

        $line = Line::firstOrCreate(['name' => 'Line A'], ['capacity' => 40, 'is_active' => true]);
        $inwardBundle = $inwardCutTicket->bundles()->firstOrFail();
        SewingService::assignToLine($inwardBundle, $line);
        SewingService::logOutput($inwardBundle);

        $mainStore = Location::where('name', 'Main Store')->firstOrFail();
        $inwardPieces = PieceSerial::where('bundle_id', $inwardBundle->id)->get();
        foreach ($inwardPieces as $piece) {
            // Skips Finished Goods intake automatically — QcService's
            // inward branch (this piece was never ours).
            QcService::pass($piece, $mainStore, $users['Production']->id);
        }

        SubcontractInwardService::dispatchBack($inward, $users['Production']->id);
    }

    private function seedAccounting(Order $order, array $parties, array $users): void
    {
        $bank = BankAccount::create([
            'account_holder_name' => 'Vishesh Textiles', 'bank_name' => 'Prime Bank',
            'account_number' => '1234567890', 'branch_name' => 'Gulshan Branch',
            'routing_swift_no' => 'PRMBBDDH', 'is_active' => true,
        ]);

        $incomeCategory = AccountingCategory::create(['kind' => 'income', 'name' => 'Job Work Income', 'is_active' => true]);
        $expenseCategory = AccountingCategory::create(['kind' => 'expense', 'name' => 'Utility Bills', 'is_active' => true]);

        PartyBill::create([
            'party_id' => $parties['buyer1']->id,
            'amount' => $order->grand_total,
            'bill_date' => now()->subDays(15)->toDateString(),
            'description' => "Order {$order->order_no}",
            'reference' => $order->order_no,
            'created_by' => $users['Accountant']->id,
        ]);

        VoucherService::record([
            'type' => 'credit', 'purpose' => 'payment', 'party_id' => $parties['buyer1']->id,
            'amount' => 5000, 'payment_type' => 'cash', 'date' => now()->subDays(5)->toDateString(),
            'remarks' => 'Partial payment received against order.',
        ], $users['Accountant']->id);

        VoucherService::record([
            'type' => 'debit', 'purpose' => 'payment', 'party_id' => $parties['supplier1']->id,
            'amount' => 2250, 'payment_type' => 'bank', 'bank_account_id' => $bank->id,
            'date' => now()->subDays(18)->toDateString(), 'remarks' => 'Fabric purchase order payment.',
        ], $users['Accountant']->id);

        VoucherService::record([
            'type' => 'credit', 'purpose' => 'general', 'category_id' => $incomeCategory->id,
            'amount' => 30, 'payment_type' => 'cash', 'date' => now()->toDateString(),
            'remarks' => 'Inward subcontract job work income.',
        ], $users['Accountant']->id);

        VoucherService::record([
            'type' => 'debit', 'purpose' => 'general', 'category_id' => $expenseCategory->id,
            'amount' => 450, 'payment_type' => 'cash', 'date' => now()->subDays(1)->toDateString(),
            'remarks' => 'Factory electricity bill.',
        ], $users['Accountant']->id);

        $cheque = Cheque::create([
            'party_id' => $parties['supplier2']->id, 'bank_account_id' => $bank->id,
            'cheque_no' => 'CHQ-000123', 'amount' => 1200, 'issue_date' => now()->subDays(7)->toDateString(),
            'type' => 'expense', 'remarks' => 'Accessories payment.', 'created_by' => $users['Accountant']->id,
        ]);
        ChequeService::markPassed($cheque, $users['Accountant']->id);
    }

    private function seedHrm(array $users): void
    {
        $sewingOperator = Designation::create(['name' => 'Sewing Operator', 'is_active' => true]);
        $cuttingMasterDesignation = Designation::create(['name' => 'Cutting Master', 'is_active' => true]);

        $employee1 = Employee::create([
            'full_name' => 'Rahim Uddin', 'phone' => '+880-1700-000001', 'gender' => 'male',
            'employment_type' => 'permanent', 'birth_date' => now()->subYears(28)->toDateString(),
            'joining_date' => now()->subMonths(6)->toDateString(),
            'designation_id' => $sewingOperator->id, 'salary' => 12000, 'status' => 'active',
        ]);
        $employee2 = Employee::create([
            'full_name' => 'Karim Islam', 'phone' => '+880-1700-000002', 'gender' => 'male',
            'employment_type' => 'permanent', 'birth_date' => now()->subYears(35)->toDateString(),
            'joining_date' => now()->subYear()->toDateString(),
            'designation_id' => $cuttingMasterDesignation->id, 'salary' => 15000, 'status' => 'active',
        ]);

        $month = (int) now()->month;
        $year = (int) now()->year;

        // Partial payment — leaves a real, visible due amount.
        $payment1 = SalaryService::openMonth($employee1, $month, $year, $users['Accountant']->id);
        SalaryService::pay($payment1, '8000', 'cash');

        // Fully paid.
        $payment2 = SalaryService::openMonth($employee2, $month, $year, $users['Accountant']->id);
        SalaryService::pay($payment2, '15000', 'bank');
    }
}
