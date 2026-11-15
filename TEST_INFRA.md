# KAFEINA ERP — E2E Testing Infrastructure Specification
**Document Reference**: KAFEINA-TEST-INFRA-V1.0  
**Target System**: KAFEINA ERP (Specialty Coffee Roastery & Multi-Branch Enterprise ERP)  
**Standard**: Opaque-Box, Requirement-Driven 4-Tier Verification Architecture  
**Test Suite Directory**: `tests/Feature/E2E/`  

---

## 1. Test Philosophy & Principles

### 1.1 Opaque-Box & Requirement-Driven Philosophy
The KAFEINA ERP E2E testing framework operates on a strict **opaque-box (black-box)** testing methodology. Tests interact with the system strictly through:
1. Public HTTP endpoints (Inertia Web routes & REST API controllers).
2. Public service layer contracts (`CashierPinAuthService`, `QualityControlService`, `WorkOrderService`, `FifoStockAllocatorService`, `InventoryDispatchService`, `PosShiftService`).
3. Database state observable via Eloquent models, MySQL transactional state, and immutable audit logs (`audit_logs`, `stock_cards`).

Tests **never rely on private implementation details, internal class methods, or mocked business rules**. If an internal algorithm is changed or refactored, the test suite continues to validate business invariant compliance without requiring modification.

### 1.2 The 4-Tier Testing Methodology

```
┌─────────────────────────────────────────────────────────────────┐
│                     TIER 4: WORKLOAD SCENARIOS                  │
│       Realistic multi-step end-to-end operational lifecycles    │
│                 (>= 17 realistic enterprise scenarios)           │
├─────────────────────────────────────────────────────────────────┤
│                    TIER 3: PAIRWISE COMBINATORIAL               │
│          Cross-feature interactions & multi-module handoffs     │
│                 (>= 34 pairwise interaction test cases)         │
├─────────────────────────────────────────────────────────────────┤
│                  TIER 2: BOUNDARY VALUE ANALYSIS (BVA)          │
│       Edge conditions, tolerances, lockouts, rounding limits    │
│             (>= 5 cases per feature = >= 170 test cases)        │
├─────────────────────────────────────────────────────────────────┤
│                 TIER 1: CATEGORY-PARTITION COVERAGE             │
│       Equivalence partitions: valid, invalid, empty, states     │
│             (>= 5 cases per feature = >= 170 test cases)        │
└─────────────────────────────────────────────────────────────────┘
```

#### Tier 1: Category-Partition Coverage (Equivalence Partitioning)
- Decomposes each of the 34 features into discrete input domains, operational categories, and execution states.
- For every feature, at least 5 distinct partitions are systematically verified:
  1. **Primary Happy Path**: Valid standard inputs, expected state transition, successful outcome.
  2. **Invalid Parameter / Type Partition**: Non-compliant data types, missing required attributes, malformed formats.
  3. **Empty / Null / Zero Partition**: Empty payloads, zero weights, null optional relationships.
  4. **State Constraint / Pre-Condition Partition**: Attempting actions when pre-requisite records are in an invalid state (e.g. starting an already completed Work Order, or checkout on a closed shift).
  5. **Negative Domain Rule Partition**: Out-of-spec business logic inputs (e.g. unauthorized role, non-existent lot, inactive branch).
- **Target**: $\ge 5$ tests per feature $\times 34$ features = **$\ge 170$ test cases**.

#### Tier 2: Boundary Value Analysis (BVA) & Corner Cases
- Tests precision at exact mathematical frontiers and tolerance boundaries:
  - **Specialty QC Moisture**: Exactly $9.99\%$ (Reject), $10.00\%$ (Accept), $12.00\%$ (Accept), $12.01\%$ (Reject).
  - **Specialty QC Cupping Score**: $79.99$ (Downgrade/Reject), $80.00$ (Specialty Accepted).
  - **Specialty QC Primary Defects**: Exactly $0$ (Accept), $\ge 1$ (Reject).
  - **Roasting Yield Loss Tolerance**: $13.99\%$ (Under-developed Warning), $14.00\%$ (Normal Auto-Approve), $18.00\%$ (Normal Auto-Approve), $18.01\%$ (Over-roasted Alert).
  - **Cashier 6-Digit PIN**: 5 digits (invalid), 6 digits (valid), 7 digits (invalid), non-numeric (invalid).
  - **5-Strike Lockout**: Strike 1 to 4 (allowed with decrement), Strike 5 (locked out for 15 minutes, HTTP 429), attempt during lockout (blocked).
  - **Shift Closing Cash Reconciliation**: Discrepancy $= \text{Rp } 10.000$ (approved without supervisor PIN), Discrepancy $= \text{Rp } 10.001$ (hard freeze, supervisor PIN mandatory).
  - **PB1 10% Local Restaurant Tax**: Decimal fractions rounded half-up to whole IDR currency.
  - **Inventory Balances**: Exactly zero balance, negative stock prevention via pessimistic locks.
- **Target**: $\ge 5$ tests per feature $\times 34$ features = **$\ge 170$ test cases**.

#### Tier 3: Pairwise Combinatorial Testing
- Verifies integration contracts between pairs of adjacent features across modules:
  - QC Lab Acceptance $\rightarrow$ Work Order Green Bean Reservation (Features 3 & 8).
  - Roasting Completion $\rightarrow$ FIFO Central Warehouse Stock Intake (Features 11 & 13).
  - FIFO Batch Allocation $\rightarrow$ Virtual `TRANSIT` Warehouse Isolation (Features 13 & 14).
  - Delivery Note QR Code $\rightarrow$ Branch Bar Stock Ingestion & Discrepancy (Features 15 & 16).
  - Shift Opening $\rightarrow$ Web POS Touch Order Checkout (Features 18 & 19).
  - Web POS Checkout $\rightarrow$ Pessimistic Recipe Stock Auto-Deduct (Features 20 & 21).
  - Dining Order Splitting $\rightarrow$ Multi-Payment Settlement & PB1 Segregation (Features 22 & 33).
  - Non-Revenue Espresso Calibration $\rightarrow$ Stock Deduction without Cash Inflow (Features 21 & 23).
  - Cash Discrepancy $\rightarrow$ 6-Digit Supervisor PIN Gate (Features 25 & 30).
  - Security Breach Lockout $\rightarrow$ Transactional Audit Trail Logging (Features 31 & 32).
  - Green Bean Origin Lot $\rightarrow$ Retail Bag Traceability & Halal Lineage (Features 4 & 34).
- **Target**: **$\ge 34$ pairwise interaction tests**.

#### Tier 4: Real-World Enterprise Workload Scenarios
- End-to-end multi-step realistic simulations of daily specialty roastery and multi-branch cafe operations:
  - Complete farm-to-cup procurement, roasting, branch dispatch, and POS sale.
  - Morning barista calibration and dial-in expense write-offs.
  - High-traffic weekend rush hour with concurrent order deductions.
  - Shift closing reconciliation with discrepancy supervisor escalation.
- **Target**: **$\ge 17$ complete lifecycle workload scenarios**.

---

## 2. Feature Inventory Mapping (All 34 Features)

| # | Feature Name | Module | Tier 1 Scope | Tier 2 Boundary Scope | Tier 3 Pairwise Combinations | Tier 4 Scenario Mapping |
|---|--------------|--------|--------------|----------------------|------------------------------|-------------------------|
| 1 | Supplier & Farm Profile Management | P2P | Create, read, update, deactivate, code format | Altitude $= 0$, altitude negative, rating $0.00 - 5.00$ | Supplier $\leftrightarrow$ PO (F1+F2) | Scenario 1, 16 |
| 2 | Purchase Order (PO) Issuance | P2P | Draft, issue, cancel, item lines, total amount | Unit price $\le 0$, order date past/future | PO $\leftrightarrow$ Green Lot (F2+F4) | Scenario 1, 13 |
| 3 | Specialty Coffee QC Lab Inspection | P2P | Accept, downgrade, reject, moisture, SCA cupping | Moisture: $9.99, 10.00, 12.00, 12.01$; Defects: $0, 1$; SCA: $79.99, 80.00$ | QC $\leftrightarrow$ Lot Generation (F3+F4) | Scenario 1, 5 |
| 4 | Green Bean Lot Registration | P2P | Lot code format `GB-[FARM]-[YEAR]-[SEQ]`, weight, price/g | Current weight $= 0$, negative weight, price/g $= 0$ | Lot $\leftrightarrow$ WO Reservation (F4+F8) | Scenario 1, 9, 14 |
| 5 | Goods Receipt & AP Accrual | P2P | Full receipt, partial receipt, warehouse intake | Over-delivery $+5\%$, zero receipt, duplicate GRN | GRN $\leftrightarrow$ Stock Card (F5+F4) | Scenario 1, 13 |
| 6 | Master Roasting Profile Management | MTS | Light, Med, Med-Dark, Dark, charge temp, turning point | Charge temp $\le$ turning point, DTR $0\% - 100\%$ | Profile $\leftrightarrow$ Work Order (F6+F8) | Scenario 1, 5, 14 |
| 7 | Secondary Packaging BOM | MTS | Pouch, valve, label, nitrogen gas items, cost sum | Zero component qty, single vs multi-item BOM | BOM $\leftrightarrow$ Real HPP (F7+F10) | Scenario 1, 14 |
| 8 | Work Order (WO) Scheduling & Green Reservation | MTS | Schedule, lot stock reservation, status transitions | Reserve exactly available weight vs available $+1\text{g}$ | WO $\leftrightarrow$ Green Lot Lock (F8+F4) | Scenario 1, 12, 14 |
| 9 | Yield Loss Calculation & Anomaly Detection | MTS | Yield loss formula, under-dev warning, over-roast alert | Yield loss: $13.99\%, 14.00\%, 18.00\%, 18.01\%$ | Yield Loss $\leftrightarrow$ Real HPP (F9+F10) | Scenario 1, 5 |
| 10 | Batch Real HPP Reconciliation | MTS | Green cost $+$ packaging cost $+$ energy cost per gram | Zero energy cost, zero roasted weight exception | Real HPP $\leftrightarrow$ Finished Product (F10+F11)| Scenario 1, 14, 15 |
| 11 | Atomic Batch Completion & Stock Conversion | MTS | Deduct green & BOM, credit roasted stock, stock cards | Full rollback on DB failure, atomic balance | Completion $\leftrightarrow$ FIFO Stock (F11+F13) | Scenario 1, 14 |
| 12 | Material Request (MR) Submission | Logistics | Submit, approve, reject, multi-item request | Request qty $= 0$, request exceeding central stock | MR $\leftrightarrow$ FIFO Allocator (F12+F13) | Scenario 1, 4, 16, 17 |
| 13 | FIFO Batch Stock Allocation | Logistics | Allocate oldest roasted batch by `roast_end_time` | Multi-batch split allocation, exact batch depletion | FIFO $\leftrightarrow$ Transit Isolation (F13+F14)| Scenario 1, 4, 17 |
| 14 | Virtual IN_TRANSIT Stock Isolation | Logistics | Deduct central, credit virtual `TRANSIT`, lock stock | Zero physical availability during transit | Transit $\leftrightarrow$ Delivery Note (F14+F15) | Scenario 1, 4, 6 |
| 15 | Delivery Note with Encrypted QR Code | Logistics | Surat Jalan generation, encrypted QR payload | QR checksum verification, courier tampering | Delivery Note $\leftrightarrow$ Receipt (F15+F16) | Scenario 1, 4, 6 |
| 16 | Branch Receipt QR Verification | Logistics | Scan QR, confirm receipt, damage write-off | Exact receipt vs discrepancy (received $<$ sent) | Receipt $\leftrightarrow$ Retail Bar Stock (F16+F21) | Scenario 1, 4, 6 |
| 17 | Reorder Point & Safety Stock Alerts | Logistics | Threshold monitoring, low-stock visual warning flag | Current stock $=$ safety threshold vs threshold $-1$ | Safety Alert $\leftrightarrow$ MR Auto-Draft (F17+F12) | Scenario 4, 16 |
| 18 | Cashier Shift Opening & Cash Validation | POS | Opening cash entry, pos_shifts record creation | Opening cash $= 0$, opening cash negative, duplicate shift | Shift Open $\leftrightarrow$ POS Unlock (F18+F19) | Scenario 1, 2, 7, 15 |
| 19 | Touchscreen Web POS Ordering & Cart | POS | Category filtering, item selection, cart total | Minimum touch target 48px, empty cart rejection | Cart $\leftrightarrow$ Multi-Payment (F19+F20) | Scenario 1, 3, 10 |
| 20 | Multi-Payment Checkout | POS | Cash with change, QRIS callback, Bank Transfer (VA) | Exact payment, overpayment, underpayment reject | Checkout $\leftrightarrow$ Recipe Auto-Deduct (F20+F21)| Scenario 1, 3, 10 |
| 21 | Pessimistic Recipe Auto-Deduct Engine | POS | Atomic recipe deduction, branch stock cards | Stock $= \text{needed}$ vs stock $< \text{needed}$ (rollback) | Auto-Deduct $\leftrightarrow$ Negative Stock Prevent | Scenario 1, 3, 11 |
| 22 | Split Bill & Order Splitting | POS | Split by item, split by share, independent payment | Child subtotals sum exactly equals parent order | Split Bill $\leftrightarrow$ PB1 Tax (F22+F33) | Scenario 10 |
| 23 | Non-Revenue Transactions | POS | Calibration dial-in, tasting, barista staff quota | Total $= 0\text{ IDR}$, mandatory operational reason | Non-Rev $\leftrightarrow$ Barista Expense Card (F23+F21)| Scenario 2 |
| 24 | ESC/POS Thermal Printing & Drawer Kick | POS | 58mm/80mm receipt format, RJ-11 drawer pulse | Header, line items, PB1 line, cash change, kick pulse | Print Stream $\leftrightarrow$ Shift Cash Drawer (F24+F25)| Scenario 1, 3, 15 |
| 25 | Shift Closing & Cash Reconciliation | POS | Blind count entry, system expected cash comparison | Discrepancy: $10.000$ (OK) vs $10.001$ (Supervisor PIN) | Shift Close $\leftrightarrow$ Supervisor PIN (F25+F30) | Scenario 7, 15 |
| 26 | Real-Time Consolidated Executive Dashboard | Finance | Daily revenue, order volume, gross margin, branch stats | Empty date range, single branch vs all branches | Dashboard $\leftrightarrow$ Real P&L (F26+F27) | Scenario 15 |
| 27 | Real P&L & Recipe Standard vs Actual | Finance | Gross margin with real HPP, spillage/leakage variance | Spillage tolerance $\le 5\%$ vs $> 5\%$ warning flag | Real P&L $\leftrightarrow$ Batch HPP (F27+F10) | Scenario 15 |
| 28 | Data Export Engine (Excel & PDF) | Finance | XLSX and PDF export for shift summary, stock cards | Empty dataset export, date range filtering | Export $\leftrightarrow$ Audit Trail (F28+F32) | Scenario 15 |
| 29 | RBAC Authorization & Route Gatekeeper | Security | Least privilege per role (Super Admin, Roaster, Cashier) | Role allowed vs HTTP 403 Forbidden | RBAC Gate $\leftrightarrow$ Audit Log (F29+F32) | Scenario 8 |
| 30 | 6-Digit Cashier PIN & Rapid Auth | Security | Rapid barista switching, drawer open, void authorization | Exact 6 digits, 5 digits reject, 7 digits reject, Bcrypt | Cashier PIN $\leftrightarrow$ Shift Unlock (F30+F18) | Scenario 1, 7 |
| 31 | 5-Strike Brute-Force Lockout Engine | Security | Track failed PIN attempts, 15-minute account lock | Attempts: 1..4 (retry allowed), 5 (lockout, HTTP 429) | Lockout $\leftrightarrow$ Security Breach Log (F31+F32)| Scenario 8 |
| 32 | Comprehensive Transactional Audit Trail | Security | Eloquent Observer logging INSERT, UPDATE, DELETE | JSON old/new snapshots, fail-safe non-blocking | Audit Trail $\leftrightarrow$ All Core Mutations | Scenario 1..17 |
| 33 | Local Tax PB1 (10%) Calculation | Regulatory | 10% Restaurant Tax segregation on receipts/reports | Half-up integer rounding, zero tax on non-revenue | PB1 Tax $\leftrightarrow$ Checkout (F33+F20) | Scenario 1, 3, 10 |
| 34 | Halal & BPOM Traceability Certification | Regulatory | Unbroken lot lineage from farm plot to retail cup | Certified vs uncertified lot blend warning | Halal Traceability $\leftrightarrow$ QC & Lot (F34+F3) | Scenario 9 |

---

## 3. Real-World Application Workload Scenarios (Tier 4)

1. **Scenario 1: Complete Farm-to-Cup Lifecycle**:
   Supplier onboarding $\rightarrow$ Green Bean PO issuance $\rightarrow$ Specialty QC inspection (SCA 86.5) $\rightarrow$ Lot creation (`GB-GAYO-2026-0001`) $\rightarrow$ Work Order roasting with Light Roast profile $\rightarrow$ Yield loss validation ($15.2\%$) $\rightarrow$ Real HPP calculation $\rightarrow$ Finished roasted bean warehouse stock intake $\rightarrow$ Material Request from Senopati outlet $\rightarrow$ FIFO batch allocation $\rightarrow$ Delivery Note dispatch with virtual `TRANSIT` isolation $\rightarrow$ Branch QR scan verification $\rightarrow$ Cashier shift opening $\rightarrow$ POS order ring-up (Dine In Latte) $\rightarrow$ Pessimistic stock auto-deduct $\rightarrow$ PB1 10% tax calculation $\rightarrow$ Cash checkout with change $\rightarrow$ ESC/POS print stream and cash drawer pulse.
2. **Scenario 2: Morning Barista Dial-In & Grinder Calibration**:
   Barista opens shift $\rightarrow$ Pulls 3 espresso calibration double-shots to adjust grind size and extraction time $\rightarrow$ Enters Non-Revenue transaction with reason `CALIBRATION` $\rightarrow$ System deducts 60g roasted beans and 0 IDR cash $\rightarrow$ Posts deduction to Barista Expense stock card.
3. **Scenario 3: Rush-Hour Concurrent Orders & Pessimistic Stock Protection**:
   Two cashiers at Senopati branch ring up orders simultaneously for the last remaining portions of Single Origin Ethiopia Geisha $\rightarrow$ First transaction acquires row lock (`SELECT ... FOR UPDATE`), decrements inventory, and completes successfully $\rightarrow$ Second transaction detects zero available stock and rolls back gracefully with descriptive `InsufficientStockException`.
4. **Scenario 4: Weekend Branch Stock Depletion & Emergency FIFO Reorder**:
   Outlet stock drops below reorder point $\rightarrow$ Automated safety stock visual alert triggered on branch dashboard $\rightarrow$ Outlet manager generates urgent Material Request $\rightarrow$ Central Roastery validates oldest available batches via FIFO $\rightarrow$ Generates Delivery Note $\rightarrow$ Isolates in `TRANSIT` account $\rightarrow$ Immediate branch receipt.
5. **Scenario 5: Roasting Yield Loss Anomaly & Sensory Re-Evaluation**:
   Roaster dumps batch with post-roast weight yielding $19.2\%$ loss ($> 18.00\%$) $\rightarrow$ System triggers critical anomaly alert flag `OVER_ROASTED` $\rightarrow$ Batch is quarantined from automatic packaging until Head Roaster override and cupping sensory evaluation is recorded.
6. **Scenario 6: In-Transit Discrepancy & Transit Loss Write-Off**:
   Central warehouse dispatches 20 kg roasted beans $\rightarrow$ Courier encounters rain/damage en route $\rightarrow$ Branch manager scans QR and enters actual receipt of 19 kg (1 kg damaged) $\rightarrow$ System moves 19 kg to active bar stock, updates Delivery Note status to `RECONCILED`, and writes off 1 kg difference to `ADJUSTMENT` loss stock card.
7. **Scenario 7: Shift Closing Cash Discrepancy & Supervisor Escalation**:
   Cashier performs blind physical cash count showing -Rp 50.000 difference ($> \text{Rp } 10.000$) $\rightarrow$ System locks shift closure with error `SUPERVISOR_PIN_REQUIRED` $\rightarrow$ Branch supervisor enters 6-digit PIN `889900` $\rightarrow$ Shift closes with recorded incident audit log.
8. **Scenario 8: POS Terminal Brute-Force PIN Attack & Security Lockdown**:
   Attacker inputs invalid PIN 5 consecutive times on POS lock screen $\rightarrow$ System triggers 5-strike lockout engine $\rightarrow$ Locks account for 15 minutes $\rightarrow$ Records `SECURITY_BREACH` event in `audit_logs` $\rightarrow$ Subsequent attempts return HTTP 429.
9. **Scenario 9: Specialty Coffee Halal & Farm Origin Traceability Audit**:
   Auditor inspects a retail 250g coffee bag $\rightarrow$ Queries traceability code $\rightarrow$ System displays unbroken digital chain: Retail bag SKU $\rightarrow$ Roasting Work Order $\rightarrow$ Green Bean Lot $\rightarrow$ Lab QC report $\rightarrow$ Farmer Cooperative profile and Halal certificate.
10. **Scenario 10: Multi-Payment Split Bill with PB1 Tax Segregation**:
    Table of 4 diners requests split bill by item $\rightarrow$ Order is split into Ticket A (Cash, Rp 111.100) and Ticket B (QRIS Midtrans, Rp 88.000) $\rightarrow$ Each ticket correctly calculates 10% PB1 tax and processes independent payment status.
11. **Scenario 11: Stock Depletion & Zero-Balance Guard**:
    Item with 1 unit remaining in branch stock is purchased $\rightarrow$ Stock decrements to exactly 0.0000 $\rightarrow$ Subsequent attempt to purchase triggers HTTP 409 / InsufficientStockException and blocks checkout without negative stock.
12. **Scenario 12: Work Order Cancellation & Green Bean Lot Un-Reservation**:
    Work Order is scheduled and green beans reserved $\rightarrow$ Roaster machine experiences maintenance issue $\rightarrow$ Roaster cancels Work Order $\rightarrow$ System atomically releases reserved green beans back to available lot inventory.
13. **Scenario 13: Partial Purchase Order Receiving & AP Accrual**:
    PO issued for 500 kg green beans $\rightarrow$ Supplier delivers initial shipment of 200 kg $\rightarrow$ Goods receipt created for partial arrival $\rightarrow$ PO status transitions to `PARTIAL_RECEIVED` $\rightarrow$ Accounts payable liability accrues for 200 kg.
14. **Scenario 14: Espresso House Blend Multi-Lot Roasting BOM**:
    House blend formulation requires $60\%$ Gayo Washed and $40\%$ Flores Bajawa $\rightarrow$ Work order reserves from two separate lots $\rightarrow$ Multi-component secondary packaging BOM applied $\rightarrow$ Blended real HPP per 250g bag calculated.
15. **Scenario 15: Nightly Shift Close, Cash Drop & Consolidated Executive P&L**:
    All branch shifts close $\rightarrow$ Physical cash reconciled $\rightarrow$ Consolidated dashboard aggregates daily revenue, gross margins using actual batch HPP, spillage analysis, and net profit.
16. **Scenario 16: New Branch Onboarding & Initial Bar Stock Distribution**:
    New branch `BR-02` onboarded with `RETAIL_BAR` and virtual `TRANSIT` warehouses $\rightarrow$ Initial allocation of coffee beans, milk, and syrups dispatched from central roastery $\rightarrow$ Branch confirmed ready for POS operation.
17. **Scenario 17: Inter-Branch Stock Rebalancing**:
    Outlet A has surplus beans while Outlet B experiences stockout $\rightarrow$ Outlet B issues Material Request to Outlet A $\rightarrow$ Stock dispatched and transferred via virtual transit isolation between sister branches.

---

## 4. Test Architecture & Execution

### 4.1 Test Directory Layout
```
tests/Feature/E2E/
├── Tier1/
│   ├── FoundationRbacTier1Test.php         # Features 29, 30, 31, 32
│   ├── ProcurementQcTier1Test.php          # Features 1, 2, 3, 4, 5, 34
│   ├── RoasteryMfgTier1Test.php            # Features 6, 7, 8, 9, 10, 11
│   ├── LogisticsFifoTier1Test.php          # Features 12, 13, 14, 15, 16, 17
│   └── PosRetailTier1Test.php              # Features 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 33
├── Tier2/
│   ├── BoundarySecurityTier2Test.php       # PIN formats, 5-strike boundaries, lockout durations
│   └── BoundaryDomainRulesTier2Test.php    # QC moisture, SCA, defects, Yield loss, Cash diff, PB1 rounding
├── Tier3/
│   └── PairwiseIntegrationTier3Test.php    # Cross-feature pairs and lifecycle handoffs
└── Tier4/
    └── WorkloadScenariosTier4Test.php      # 17 realistic enterprise lifecycle workload scenarios
```

### 4.2 Test Runner Commands
```bash
# Run entire E2E test suite
php artisan test --filter=E2E

# Run specific testing tiers
php artisan test --filter=Tier1
php artisan test --filter=Tier2
php artisan test --filter=Tier3
php artisan test --filter=Tier4

# Run with raw PHPUnit runner
vendor/bin/phpunit --testsuite=Feature --filter=E2E
```

### 4.3 Database Isolation & State Management
- Tests use `Illuminate\Foundation\Testing\DatabaseTransactions`.
- Every test runs inside a nested MySQL InnoDB transaction that automatically rolls back upon test conclusion (`ROLLBACK`).
- Production seeders provide baseline master data (`roles`, `branches`, `products`), ensuring fast, isolated, deterministic test execution with zero persistent pollution.

---

## 5. Coverage Thresholds & Quality Gates

| Test Tier | Methodology | Minimum Threshold | Verified Coverage | Status |
|-----------|-------------|-------------------|-------------------|--------|
| **Tier 1** | Category-Partition | $\ge 5$ cases / feature ($\ge 170$ tests) | Comprehensive equivalence partitions across 34 features | **ACTIVE** |
| **Tier 2** | Boundary Value Analysis | $\ge 5$ cases / feature ($\ge 170$ tests) | Exact tolerance limits, math bounds, security thresholds | **ACTIVE** |
| **Tier 3** | Pairwise Combinatorial | $\ge 34$ cross-feature tests | Multi-module handoff & transactional contract pairs | **ACTIVE** |
| **Tier 4** | Real-World Workloads | $\ge 17$ realistic scenarios | 17 full enterprise operational lifecycles | **ACTIVE** |
| **Total** | Multi-Tiered E2E | **$\ge 391$ tests** | Full 34-feature business contract validation | **READY** |
