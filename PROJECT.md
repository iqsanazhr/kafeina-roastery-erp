# Project: KAFEINA ERP
**Sistem Terintegrasi Roastery Kopi Spesialti dan Manajemen Multi-Cabang**

## Architecture

KAFEINA ERP implements a high-performance Three-Tier Client-Server Architecture adhering to C4 container specifications:

- **Presentation Layer**:
  - Single Page Application (SPA) with **Vue.js 3 (Composition API) + Inertia.js + TailwindCSS v4**.
  - Dual presentation modes:
    - **Web POS Touch Terminal**: Designed for tablet viewports (10-12 inches), minimum 48px touch targets, rapid numeric keypad, cart drawer, zero animation (0ms) on high-frequency POS actions, $\le$300ms transitions on modals/feedback following Emil Kowalski motion design principles.
    - **Executive & Management Portal**: Rich desktop responsive layouts for procurement, roasting scheduling, inventory ledger, P&L analytics, and audit administration.
- **Application Layer**:
  - **Laravel 13 (PHP 8.3+)** REST controllers and Inertia page controllers.
  - **Laravel Sanctum** token and session-based authentication.
  - 6-digit cashier PIN gate with 5-strike brute-force lockout (15 minutes).
  - Service Layer pattern for domain logic:
    - `QualityControlService` & `LotGeneratorService` (P2P)
    - `RoastingYieldService` & `BatchHppService` (MTS)
    - `FifoStockAllocatorService` & `TransitIsolationService` (Logistics)
    - `PosTransactionService` with `SELECT ... FOR UPDATE` pessimistic locking (O2C)
    - `AuditLogObserver` intercepting model mutations.
- **Data Layer**:
  - **MySQL 8.4 / 8.0 Community Edition InnoDB Engine**.
  - 27 relational tables normalized in 3NF with ACID transaction compliance.
  - Composite B-Tree indexes (notably `idx_wh_stock_fifo` on `(inventory_warehouse_id, product_id, roasted_date, quantity)`).
  - Strict foreign keys: `ON UPDATE CASCADE ON DELETE RESTRICT` for master entities, `ON DELETE CASCADE` for dependent transactional line items.

---

## Feature Inventory

| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | Supplier & Farm Profile Management | Record coffee farmers, cooperatives, farm elevation (mdpl), varieties, rating | M2 | SDD §2.3.2, SRS REQ-PRC-01 |
| 2 | Purchase Order (PO) Issuance | Generate official green bean PO with variety, process, price, delivery date | M2 | SDD §2.3.2, SRS REQ-PRC-02 |
| 3 | Specialty Coffee QC Lab Inspection | Validate moisture (10-12%), bulk density, primary defect (=0), SCA score ($\ge$80) | M2 | SDD §4.1, SRS REQ-PRC-03 |
| 4 | Green Bean Lot Registration | Auto-generate unique lot code `GB-[FARM]-[YEAR]-[SEQ]` and stock cards | M2 | SDD §4.1, SRS REQ-PRC-04 |
| 5 | Goods Receipt & AP Accrual | Record physical receipt and create accounts payable liability | M2 | SDD §5.2.1, URS FR-PRC-05 |
| 6 | Master Roasting Profile Management | Manage roast profiles (Light, Med, Dark), charge temp, turning point, DTR | M3 | SDD §4.2, SRS REQ-PRD-01 |
| 7 | Secondary Packaging BOM | Define packaging bill of materials (pouch, valve, label, nitrogen flush) | M3 | SDD §4.2, URS FR-MFG-03 |
| 8 | Work Order (WO) Scheduling & Green Reservation | Batch roasting WO, reserve green beans with `SELECT ... FOR UPDATE` | M3 | SDD §5.2.2, SRS REQ-PRD-04 |
| 9 | Yield Loss Calculation & Anomaly Detection | Shrinkage calculation: $((W_{green} - W_{roasted})/W_{green}) \times 100$, 14-18% tolerance | M3 | SDD §6.4, SRS REQ-PRD-02 |
| 10 | Batch Real HPP Reconciliation | Dynamic HPP per gram: $(Cost_{green} + Cost_{packaging} + Cost_{energy}) / W_{roasted}$ | M3 | SDD §6.4, URS FR-MFG-06 |
| 11 | Atomic Batch Completion & Stock Conversion | Atomic DB transaction: deduct green & packaging, increment roasted finished stock | M3 | SDD §5.2.2, SRS REQ-PRD-04 |
| 12 | Material Request (MR) Submission | Requisition from outlet branch for roasted beans, syrup, milk, cups | M4 | SDD §5.2.3, URS FR-INV-01 |
| 13 | FIFO Batch Stock Allocation | Query & lock oldest batch based on roast date (`roast_end_time ASC`) | M4 | SDD §5.2.3, SRS REQ-INV-01 |
| 14 | Virtual IN_TRANSIT Stock Isolation | Deduct central warehouse and isolate stock in virtual `TRANSIT` account | M4 | SDD §5.2.3, SRS REQ-INV-02 |
| 15 | Delivery Note with Encrypted QR Code | Official Surat Jalan with QR code containing tracking ID and courier metadata | M4 | SDD §5.2.3, URS FR-INV-03 |
| 16 | Branch Receipt QR Verification | Branch manager scans QR, records actual receipt and transit discrepancy | M4 | SDD §5.2.3, SRS REQ-INV-03 |
| 17 | Reorder Point & Safety Stock Alerts | Monitor stock thresholds and generate low-stock visual warning alerts | M4 | URS FR-INV-07 |
| 18 | Cashier Shift Opening & Cash Validation | Cashier inputs beginning drawer cash (`opening_cash`) before unlocking POS | M5 | SDD §5.2.4, SRS REQ-POS-01 |
| 19 | Touchscreen Web POS Ordering & Cart | Responsive touch interface ($\ge$48px), category grid, numeric keypad, cart | M5 | SDD §7.1, URS FR-POS-01 |
| 20 | Multi-Payment Checkout | Cash with change calculator, dynamic QRIS, debit/credit cards | M5 | SDD §4.3, SRS REQ-POS-04 |
| 21 | Pessimistic Recipe Auto-Deduct Engine | Atomic `SELECT ... FOR UPDATE` on branch stocks, deduct ingredients per recipe | M5 | SDD §5.2.4, SDD §9, SRS REQ-POS-02 |
| 22 | Split Bill & Order Splitting | Split dining order into multiple child bills with distinct payment methods | M5 | SDD §2.3.2, SRS REQ-POS-03 |
| 23 | Non-Revenue Transactions | Process calibration espresso, customer cupping tester, barista staff quota | M5 | SRS REQ-POS-04, URS FR-POS-03 |
| 24 | ESC/POS Thermal Printing & Drawer Kick | Generate thermal receipt stream (58/80mm) and RJ-11 drawer kick pulse | M5 | SDD §8.1, URS FR-POS-06 |
| 25 | Shift Closing & Cash Reconciliation | Blind count, system cash comparison, supervisor PIN required if $|diff| > 10.000$ | M5 | SDD §5.2.4, SRS Rule 4 |
| 26 | Real-Time Consolidated Executive Dashboard | Real-time aggregate daily revenue, order volume, gross margin, stock levels | M6 | SDD §2.3.1, URS FR-FIN-01 |
| 27 | Real P&L & Recipe Standard vs Actual | Compute net profit with real batch HPP and spillage/leakage variance analysis | M6 | SRS §2.2, URS FR-FIN-02 |
| 28 | Data Export Engine (Excel & PDF) | Export financial statements, shift closings, and stock cards to XLSX/PDF | M6 | URS FR-FIN-04 |
| 29 | RBAC Authorization & Route Gatekeeper | Role-Based Access Control (Super Admin, Procurement, Roaster, Warehouse, Cashier) | M1 | SDD §2.2, SRS REQ-SEC-02 |
| 30 | 6-Digit Cashier PIN & Rapid Auth | Rapid cashier switching, shift unlocks, void approval via hashed PIN | M1 | SDD §2.2, SRS REQ-SEC-01 |
| 31 | 5-Strike Brute-Force Lockout Engine | 15-minute account lockout after 5 consecutive failed login/PIN attempts | M1 | SRS REQ-SEC-03 |
| 32 | Comprehensive Transactional Audit Trail | Observer logging model mutations (`INSERT`, `UPDATE`, `DELETE`) with old/new JSON | M1 | SDD §4.1, SRS REQ-SEC-04 |
| 33 | Local Tax PB1 (10%) Calculation | Automatic calculation of 10% Restaurant Tax (PB1) on receipts and summaries | M5 | SRS §6.3 Rule 1, SDD §4.3 |
| 34 | Halal & BPOM Traceability Certification | Store Halal certs and maintain unbroken lot lineage from farm plot to retail cup | M2 | SRS §6.3 Rule 2, SDD §1.2 |

---

## Milestones

| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| M1 | Sprint 1: Foundation, DB Migrations (27 Tables), RBAC & Audit Engine | Scaffolding consolidation, Inertia/Vue/Sanctum setup, 27 InnoDB migrations, Eloquent models, RBAC middleware, 6-digit PIN auth, 5-strike lockout, AuditLog observer, Database seeders | none | COMPLETED [DONE] |
| M2 | Sprint 2: Procure-to-Pay (P2P) & Quality Control | Suppliers CRUD, Purchase Orders, QC Lab Inspection (moisture 10-12%, SCA $\ge$80), Lot Generator `GB-[FARM]-[YEAR]-[SEQ]`, Goods Receipt, Stock Card entries | M1 | COMPLETED [DONE] |
| M3 | Sprint 3: Roastery Manufacturing & Yield Loss Engine (MTS) | Roasting Profiles, Packaging BOM, Work Orders scheduling, Yield Loss formula (14-18% tolerance), Batch Real HPP calculation, Atomic batch stock conversion | M2 | COMPLETED [DONE] |
| M4 | Sprint 4: Multi-Branch Inventory & FIFO Logistics (Branch Dispatch) | Material Requests, FIFO batch stock allocator (`idx_wh_stock_fifo`), Virtual `TRANSIT` isolation, Delivery Notes with QR code, Branch receipt verification, Reorder alerts | M3 | COMPLETED [DONE] |
| M5 | Sprint 5: Web POS Touch & Auto-Deduct (O2C) | Cashier shift opening, Touch Web POS UI ($\ge$48px, Emil Kowalski $\le$300ms motion), Multi-payment (Cash/QRIS), `SELECT ... FOR UPDATE` recipe auto-deduct, Split bill, Non-revenue orders, Thermal print format, Shift closing (> Rp 10.000 supervisor PIN) | M4 | COMPLETED [DONE] |
| M6 | Sprint 6: Executive Dashboard, Real P&L & E2E Validation | Real-time Executive Dashboard, Real P&L with real batch HPP, Recipe variance analysis, Excel/PDF exports, 100% E2E test suite pass (Tiers 1-4) & adversarial hardening (Tier 5) | M5 | COMPLETED [DONE] |

---

## Interface Contracts

### M1 (Foundation & RBAC) ↔ All Modules
- `RoleMiddleware`: `auth:sanctum` + `role:super_admin,procurement,roaster,warehouse,cashier`
- `PinAuthService::verifyPin(User $user, string $pin): bool` (with 5-strike rate limiting)
- `AuditLogObserver`: intercepts `created`, `updated`, `deleted` on audited models, persists to `audit_logs(table_name, record_id, action, old_values, new_values, user_id, ip_address)`

### M2 (P2P & QC) ↔ M3 (MTS Roastery)
- `QualityControlService::evaluate(array $metrics): QualityControl`
  - Requires: `moisture_content BETWEEN 10.00 AND 12.00`, `primary_defects == 0`, `sca_score >= 80.00`
- `GreenBeanLot::getAvailableStock(int $lotId): float` (in grams)

### M3 (MTS Roastery) ↔ M4 (Logistics & FIFO)
- `RoastingYieldService::calculateYieldLoss(float $greenWeight, float $roastedWeight): float` (tolerance 14.00% - 18.00%)
- `BatchHppService::calculateBatchHpp(float $costGreen, float $costPackaging, float $costEnergy, float $roastedWeight): float` (IDR / gram)
- Finished roasted beans registered in `warehouse_stocks` under central roastery warehouse with `roasted_date`.

### M4 (Logistics & FIFO) ↔ M5 (POS O2C)
- `FifoStockAllocatorService::allocateOldestBatches(int $warehouseId, int $productId, float $quantity): Collection`
  - Query: `WarehouseStock::where('inventory_warehouse_id', $warehouseId)->where('product_id', $productId)->where('quantity', '>', 0)->orderBy('roasted_date', 'asc')->lockForUpdate()->get()`
- Branch receipt transitions inventory from virtual `TRANSIT` warehouse to destination `RETAIL_BAR`.

### M5 (POS O2C) ↔ M6 (Executive & Finance)
- `PosTransactionService::processOrder(array $orderData, User $cashier, PosShift $shift): PosOrder`
  - Executes `DB::transaction(...)` with pessimistic lock on branch warehouse stock.
  - Generates `recipe_deductions` and decrements `warehouse_stocks`.
- `PosShiftClosingService::closeShift(PosShift $shift, float $actualCash, ?string $supervisorPin = null): PosShift`
  - Enforces supervisor PIN authorization if `abs($actualCash - $systemExpectedCash) > 10000`.

---

## Code Layout

```
d:\Kafeina\
├── app\
│   ├── Enums\                   # OrderStatus, QcStatus, StockMovementType, RoleType
│   ├── Http\
│   │   ├── Controllers\         # Inertia & API controllers grouped by module
│   │   │   ├── Auth\            # Login, PinAuthController
│   │   │   ├── Procurement\     # Supplier, PurchaseOrder, QCController
│   │   │   ├── Roastery\        # Profile, WorkOrder, BomController
│   │   │   ├── Inventory\       # MaterialRequest, DeliveryNoteController
│   │   │   ├── Pos\             # PosTerminal, PosShift, OrderController
│   │   │   └── Report\          # Dashboard, FinanceReportController
│   │   ├── Middleware\          # RoleMiddleware, CashierShiftMiddleware
│   │   └── Requests\            # Form validation requests per feature
│   ├── Models\                  # 27 Eloquent Models
│   ├── Observers\               # AuditLogObserver
│   └── Services\                # Pure business logic services per domain
│       ├── Procurement\         # QcEvaluationService, LotGeneratorService
│       ├── Roastery\            # YieldLossCalculator, BatchHppCalculator
│       ├── Inventory\           # FifoAllocatorService, StockCardService
│       └── Pos\                 # AutoDeductEngine, ShiftReconciliationService
├── database\
│   ├── migrations\              # 27 migration files prefixed by execution order
│   └── seeders\                 # Roles, Branches, Users, Coffee Master seeders
├── resources\
│   ├── css\
│   │   └── app.css              # TailwindCSS v4 theme & motion tokens
│   └── js\
│       ├── app.js               # Inertia Vue 3 bootstrapping
│       ├── Components\          # Reusable UI (Button, Modal, Input, Keypad, Card)
│       ├── Layouts\             # AppLayout, PosLayout
│       └── Pages\               # Inertia page views
│           ├── Auth\            # Login, PinLockScreen
│           ├── Dashboard\       # Executive KPI metrics
│           ├── Procurement\     # PO, QC Lab, Lot lists
│           ├── Roastery\        # Profiles, WorkOrders, BOMs
│           ├── Inventory\       # Stocks, Transfers, DeliveryNotes
│           └── Pos\             # Touchscreen terminal, Cart, Payment, ShiftClose
├── routes\
│   ├── web.php                  # Inertia Web & POS routes
│   └── api.php                  # Machine & hardware webhook routes
└── tests\
    ├── Feature\                 # E2E & Feature integration test suite
    └── Unit\                    # Mathematical algorithms & isolated logic tests
```
