<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Roles per todo.md Phase 2 and PRD_GarmentsERP_v2.md §1 (personas) / §6
 * (new roles). Starting-point permission grants below follow each
 * persona's described scope in the PRD; the owner can rebalance these
 * later from Modules/Setting (PRD v2 §6) without a code change.
 */
class RoleSeeder extends Seeder
{
    public static array $roleGrants = [
        // PRD v2 §1: "Super Admin — Full access, all locations, all modules."
        'Admin' => ['*'],

        // PRD v1 §1: represented as a party record with optional read access.
        'Buyer' => [
            'order.view', 'booking.view', 'sampling.view', 'shipment.view',
        ],

        // PRD v2 §1: "Orders, bookings, buyer liaison." Placing Outward
        // job work (§3.23) is a merchandising decision (which order/style
        // goes to which subcontractor), so it's granted here rather than
        // to Production.
        'Merchandiser' => [
            'order.view', 'order.create', 'order.edit',
            'booking.view', 'booking.create', 'booking.edit',
            'budgeting.view', 'costing.view',
            'sampling.view', 'sampling.create', 'sampling.edit',
            'party.view', 'shipment.view',
            'production.trace.view', 'report.view',
            'subcontract.outward.manage', 'subcontract.ledger.view',
        ],

        // PRD v2 §1: "Banking, shipments, LC/documents."
        'Commercial' => [
            'accounting.bank.manage', 'accounting.cheque.manage',
            'accounting.transaction.view', 'accounting.ledger.view',
            'shipment.view', 'shipment.create', 'shipment.edit',
            'party.view', 'report.view',
        ],

        // PRD v2 §1: "Vouchers, ledgers, cashbook, banking."
        'Accountant' => [
            'accounting.bank.manage', 'accounting.cash.manage', 'accounting.cheque.manage',
            'accounting.voucher.view', 'accounting.voucher.create',
            'accounting.ledger.view', 'accounting.cashbook.view',
            'accounting.transaction.view', 'accounting.loss-profit.view',
            'party.view', 'hrm.salary.view', 'hrm.salary.pay', 'report.view',
            'subcontract.ledger.view',
        ],

        // PRD v1 §1: "Production tracking" (broader floor-level oversight
        // role, above the narrower Cutting Master / Line Supervisor roles).
        // Both subcontract directions touch the floor operationally
        // (issuing pieces/raw material out, receiving+QCing job work in
        // — PRD v2 §3.23/§3.24), so Production holds both manage grants.
        'Production' => [
            'production.cutting.view', 'production.cutting.create',
            'production.sewing.view', 'production.sewing.create',
            'production.qc.record', 'production.trace.view',
            'machine.view', 'machine.create', 'machine.edit',
            'raw-material.view', 'report.view',
            'subcontract.outward.manage', 'subcontract.inward.manage', 'subcontract.ledger.view',
        ],

        // PRD v2 §1/§6: "Cutting entries, serial generation, line
        // assignment" / "Cutting module, Raw Material issue (write),
        // read-only elsewhere in production."
        'Cutting Master' => [
            'production.cutting.view', 'production.cutting.create',
            'production.sewing.view', 'production.qc.record',
            'raw-material.view', 'raw-material.issue', 'machine.view',
        ],

        // PRD v2 §6: "Sewing output entry only."
        'Line Supervisor' => [
            'production.sewing.view', 'production.sewing.create',
        ],

        // PRD v2 §6: "Raw Material module (full), Purchase Order receipt."
        'Store Keeper (Raw Material)' => [
            'raw-material.view', 'raw-material.create', 'raw-material.edit',
            'raw-material.issue', 'raw-material.purchase-order.manage',
        ],

        // PRD v2 §6: "Finished Goods, Stock Transfer (dispatch side)."
        'Store Keeper (Finished Goods)' => [
            'finished-goods.view', 'finished-goods.intake',
            'stock-transfer.view', 'stock-transfer.dispatch',
        ],

        // PRD v2 §6: "Stock Transfer (receive side, own showroom only),
        // read-only Finished Goods for their location." Location scoping
        // itself is enforced via $user->location_id, not a permission
        // (sdd.md §4).
        'Showroom Staff' => [
            'finished-goods.view', 'stock-transfer.view', 'stock-transfer.receive',
        ],
    ];

    public function run(): void
    {
        foreach (self::$roleGrants as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);

            if ($permissions === ['*']) {
                $role->syncPermissions(PermissionSeeder::$permissions);
            } else {
                $role->syncPermissions($permissions);
            }
        }
    }
}
