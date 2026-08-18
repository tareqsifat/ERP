<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * PRD_GarmentsERP_v2.md §6: "implement with spatie/laravel-permission
 * (roles + permissions), not just a hardcoded enum, so the owner can
 * adjust access later without a code change." This seeder creates the
 * permission catalogue; RoleSeeder assigns starting-point subsets to each
 * role. Both can be edited later from Modules/Setting without a deploy.
 */
class PermissionSeeder extends Seeder
{
    public static array $permissions = [
        // Modules/User
        'user.view', 'user.create', 'user.edit', 'user.delete',

        // Modules/Party
        'party.view', 'party.create', 'party.edit', 'party.delete',

        // Modules/Order
        'order.view', 'order.create', 'order.edit', 'order.delete',

        // Modules/Booking
        'booking.view', 'booking.create', 'booking.edit', 'booking.delete',

        // Modules/Budgeting
        'budgeting.view', 'budgeting.create', 'budgeting.edit', 'budgeting.delete',

        // Modules/Costing
        'costing.view', 'costing.create', 'costing.edit', 'costing.delete',

        // Modules/Sampling
        'sampling.view', 'sampling.create', 'sampling.edit', 'sampling.delete',

        // Modules/Shipment
        'shipment.view', 'shipment.create', 'shipment.edit', 'shipment.delete',

        // Modules/Location
        'location.view', 'location.create', 'location.edit', 'location.delete',

        // Modules/RawMaterial
        'raw-material.view', 'raw-material.create', 'raw-material.edit',
        'raw-material.delete', 'raw-material.issue', 'raw-material.purchase-order.manage',

        // Machine/Line register (lives in Modules/Production per sdd.md §2)
        'machine.view', 'machine.create', 'machine.edit', 'machine.delete',

        // Modules/Production — cutting, sewing, QC, traceability
        'production.cutting.view', 'production.cutting.create',
        'production.sewing.view', 'production.sewing.create',
        'production.qc.record', 'production.trace.view',

        // Modules/FinishedGoods
        'finished-goods.view', 'finished-goods.intake',
        'stock-transfer.view', 'stock-transfer.dispatch', 'stock-transfer.receive',

        // Modules/Subcontract
        'subcontract.outward.manage', 'subcontract.inward.manage', 'subcontract.ledger.view',

        // Modules/Accounting — kept granular per failed_doc.md §2 (financial
        // write endpoints must not be reachable by read-only roles).
        'accounting.bank.manage', 'accounting.cash.manage', 'accounting.cheque.manage',
        'accounting.voucher.view', 'accounting.voucher.create',
        'accounting.ledger.view', 'accounting.cashbook.view', 'accounting.transaction.view',
        'accounting.loss-profit.view',

        // Modules/Hrm
        'hrm.designation.manage', 'hrm.employee.manage',
        'hrm.salary.view', 'hrm.salary.pay', 'hrm.attendance.manage',

        // Modules/Report
        'report.view',

        // Modules/Setting
        'setting.manage',
    ];

    public function run(): void
    {
        foreach (self::$permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        }
    }
}
