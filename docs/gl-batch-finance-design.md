# GL Batch Finance Design

This document defines the target design for batching multiple Guarantee Letter transactions into one shared ORS, DV, and LDDAP-ADA while keeping record review one by one.

## 1. Final Workflow Rule

The workflow is split into two responsibilities:

- `review` is always one by one at the individual application level
- `approval` is by batch at the finance-document level

Approvers must still be able to open and inspect each included record one by one before approving the batch.

## 2. Batch Eligibility Rules

A GL application can only be grouped into a batch when all of these match:

- same `service_provider_id`
- same `gl_finance_fund_source`
- same selected `service_provider_bank_account_id`

And all of these must also be true:

- mode of assistance is `Guarantee Letter`
- updated SOA has already been uploaded and accepted
- actual utilized amount is present
- application is not already assigned to another active batch
- application is not yet paid
- application has already passed all officer-level review stages required before approval batching

## 3. Core Design Principle

Keep `applications` as the base case record.

Introduce a new parent finance-batch entity that owns:

- one ORS
- one DV
- one LDDAP-ADA
- one batch-level approval chain

That means:

- application = client/beneficiary transaction record
- finance batch = approval and disbursement packet

## 4. Review vs Approval Scope

### Officer Review Scope

These stages remain one by one at the application level:

- GL Payment Processor
- Approving Officer
- Budget Officer
- Accounting Officer
- Cash Officer

At these stages, each application is individually reviewed and either:

- passed forward
- returned for compliance
- disapproved

### Approver Scope

These stages become batch-based:

- Budget Approver
- Accounting Approver
- Program Amount Approval
- Cash Approver
- Accounting Certification
- Finance Director

At these stages:

- the queue shows one batch row
- the final decision is done at the batch level
- individual included records remain viewable one by one

## 5. Compliance Rule

If one GL inside the batch fails approval review:

- the whole batch goes back
- the approver must identify which record triggered the compliance return
- the system stores the triggering application for audit and follow-up

This means compliance is still a batch decision even if the issue came from one record.

## 6. New Tables

## 6.1 `gl_finance_batches`

Purpose:

- parent record for shared ORS/DV/LDDAP-ADA
- batch-level approval workflow

Suggested columns:

- `id`
- `batch_no`
- `service_provider_id`
- `service_provider_bank_account_id`
- `finance_fund_source_id` nullable if later normalized fully
- `finance_fund_source_name`
- `status`
- `current_stage`
- `total_amount`
- `application_count`
- `compliance_trigger_application_id` nullable
- `decision_notes` nullable
- `created_by_user_id`
- `batched_by_user_id`
- `submitted_at`
- `completed_at`
- `created_at`
- `updated_at`

Shared finance document fields:

- `fund_cluster`
- `responsibility_center`
- `mfo_pap`
- `mode_of_payment`
- `payee_tin`
- `ors_number`
- `ors_date`
- `dv_number`
- `dv_date`
- `lddap_ada_number`
- `lddap_ada_date`
- `nca_number`
- `nca_date`
- `servicing_bank_branch`
- `mds_sub_account_number`
- `withholding_tax_amount`

Batch approval fields:

- `budget_approval_status`
- `budget_approval_remarks`
- `budget_approved_by`
- `budget_approved_at`
- `accounting_approval_status`
- `accounting_approval_remarks`
- `accounting_approved_by`
- `accounting_approved_at`
- `program_amount_approval_status`
- `program_amount_approval_remarks`
- `program_amount_approved_by`
- `program_amount_approved_at`
- `cash_approval_status`
- `cash_approval_remarks`
- `cash_approved_by`
- `cash_approved_at`
- `accounting_certification_status`
- `accounting_certification_remarks`
- `accounting_certified_by`
- `accounting_certified_at`
- `finance_director_status`
- `finance_director_remarks`
- `finance_director_approved_by`
- `finance_director_approved_at`

Recommended indexes:

- `unique(batch_no)`
- `(status, current_stage, updated_at)`
- `(service_provider_id, finance_fund_source_name, service_provider_bank_account_id)`
- `(finance_director_status, updated_at)`
- `(budget_approval_status, updated_at)`
- `(accounting_approval_status, updated_at)`
- `(cash_approval_status, updated_at)`

## 6.2 `gl_finance_batch_items`

Purpose:

- link individual GL applications into a finance batch

Suggested columns:

- `id`
- `gl_finance_batch_id`
- `application_id`
- `sequence_no`
- `utilized_amount`
- `item_status`
- `flagged_for_compliance` boolean default false
- `flag_reason` nullable
- `created_at`
- `updated_at`

Recommended constraints:

- `unique(application_id)` for active membership
- `unique(gl_finance_batch_id, sequence_no)`

Recommended indexes:

- `(gl_finance_batch_id, sequence_no)`
- `(application_id)`

## 7. Changes To `applications`

Keep all existing client, beneficiary, recommendation, review, and compliance fields.

Add:

- `gl_finance_batch_id` nullable
- `gl_batch_status` nullable
- `gl_ready_for_batch_at` nullable datetime

Recommended meaning:

- `gl_finance_batch_id` = current linked active batch
- `gl_batch_status` = simplified batch mirror for UI
- `gl_ready_for_batch_at` = timestamp when the application becomes eligible for batching

Important:

- ORS, DV, and LDDAP-ADA should no longer be treated as authoritative per-application artifacts once batching is introduced
- after batching, the authoritative finance documents live on the batch

## 8. Status Model

## 8.1 Application-Level Statuses

For officer review and pre-batch preparation:

- `for_review_gl_processor`
- `for_review_program`
- `for_review_budget`
- `for_review_accounting`
- `for_review_cash`
- `ready_for_batch_approval`
- `batched`
- `returned_from_batch`
- `paid`

Existing `gl_payment_status` can remain, but should gradually map to these semantics after batching is introduced.

## 8.2 Batch-Level Statuses

Suggested batch statuses:

- `draft`
- `ready_for_submission`
- `for_approval_budget`
- `for_approval_accounting`
- `for_approval_program_amount`
- `for_approval_cash`
- `for_approval_accounting_certification`
- `for_approval_finance_director`
- `for_compliance`
- `disapproved`
- `paid`

Suggested `current_stage` values:

- `budget_approver`
- `accounting_approver`
- `program_amount_approver`
- `cash_approver`
- `accounting_certifier`
- `finance_director`

## 9. Detailed Workflow

## 9.1 Service Provider

Per application:

- uploads updated SOA
- selects bank account
- enters actual utilized amount

## 9.2 GL Payment Processor

Per application review:

- verifies SOA
- verifies supporting documents
- selects fund source
- confirms bank account
- prepares finance metadata
- marks case as ready for next officer review

May also prepare draft ORS/DV fields at the application level only as working metadata before batching.

## 9.3 Approving Officer

Per application review:

- checks case details
- checks recommendation
- passes or returns for compliance

## 9.4 Budget Officer

Per application review:

- checks budget details
- passes or returns for compliance

## 9.5 Accounting Officer

Per application review:

- checks accounting details
- passes or returns for compliance

## 9.6 Cash Officer

Per application review:

- checks cash review details
- passes or returns for compliance

## 9.7 Ready For Batch

After passing officer reviews, application becomes:

- `ready_for_batch_approval`

This is the point where batching is allowed.

## 9.8 Batch Creation

User creates a finance batch from eligible GLs.

Validation rules:

- all applications share same service provider
- all applications share same fund source
- all applications share same bank account
- all are ready for batch approval
- all are still unpaid

Batch creation actions:

- create `gl_finance_batches`
- create `gl_finance_batch_items`
- compute total amount as sum of item utilized amounts
- generate shared ORS/DV/LDDAP-ADA numbers
- mark applications as `batched`

## 9.9 Budget Approver

Batch-level approval:

- sees batch summary
- can inspect each record one by one
- approves, returns for compliance, or disapproves entire batch

## 9.10 Accounting Approver

Batch-level approval:

- same interaction pattern

## 9.11 Program Amount Approval

Batch-level approval:

- reviews batch total and line items
- approves or returns whole batch

## 9.12 Cash Approver

Batch-level approval:

- reviews batch and shared LDDAP-ADA context

## 9.13 Accounting Certification

Batch-level certification:

- certifies the batch as a whole

## 9.14 Finance Director

Final batch approval:

- if approved, batch becomes `paid`
- all linked applications become `paid`

## 10. Approver Viewing Rules

Even though approval is by batch, approvers must still be able to inspect each included record one by one.

That means the approver UI needs two layers:

### 10.1 Batch Workspace

Shows:

- batch number
- provider
- fund source
- bank account
- ORS / DV / LDDAP-ADA
- total amount
- count of included GLs
- batch remarks
- batch decision buttons

### 10.2 Record Detail View

From the batch workspace, approver can click any included record and open a record-level page showing:

- client information
- beneficiary information
- initial assessment
- recommendation
- updated SOA
- supporting documents
- utilized amount
- bank account used
- officer review history
- compliance history

Approver can inspect each record here, but cannot approve only one record.

The actual decision remains at batch level.

## 11. Compliance Return Behavior

If approver finds one problematic record in the batch:

- open the specific record
- identify the issue
- return to batch workspace
- click `For Compliance`
- select the triggering application
- enter remarks

System behavior:

- whole batch status becomes `for_compliance`
- `compliance_trigger_application_id` is saved
- all applications in the batch are returned together

Recommended return ladder:

- Finance Director -> Cash Officer
- Cash Approver -> Cash Officer
- Program Amount Approval -> Accounting Officer
- Accounting Approver -> Accounting Officer
- Budget Approver -> Budget Officer

If business later decides every batch compliance should go all the way back to GL Processor, that can be changed, but this document keeps the current officer-return logic already used in the workflow.

## 12. Document Ownership

After batching:

- ORS belongs to batch
- DV belongs to batch
- LDDAP-ADA belongs to batch

Each finance document should include:

- batch-level header data
- shared provider/fund/bank details
- line-item list of included GL applications
- batch total

Recommended line-item columns inside the documents:

- reference no
- client name
- beneficiary name
- provider
- utilized amount
- bank account
- remarks

## 13. UI / Module Plan

New module:

- `Finance Batches`

Suggested pages:

- `Ready for Batch`
- `Create Batch`
- `Batch List`
- `Batch Detail`
- `Batch ORS`
- `Batch DV`
- `Batch LDDAP-ADA`

Role behavior:

- officers keep current application queues
- approvers switch to batch queues
- approver batch queues still allow one-by-one record viewing from each batch

## 14. Route Structure Suggestion

Examples:

- `GET /gl-payment-processor/finance-batches/ready`
- `POST /gl-payment-processor/finance-batches`
- `GET /budget-approver/finance-batches`
- `GET /budget-approver/finance-batches/{batch}`
- `GET /budget-approver/finance-batches/{batch}/applications/{application}`
- `PATCH /budget-approver/finance-batches/{batch}`

Repeat similar structure for:

- accounting approver
- program amount approval
- cash approver
- accounting certification
- finance director

## 15. Implementation Phases

### Phase 1

- add batch tables
- add application link fields
- build `ready for batch` and `create batch`
- keep current approval queues untouched

### Phase 2

- move approver queues from application-based to batch-based
- keep record detail pages accessible from inside batch

### Phase 3

- move ORS/DV/LDDAP-ADA generation fully to batch ownership
- stop treating per-application finance documents as final

### Phase 4

- clean up legacy application-level approval assumptions
- simplify status mirroring
- add reporting/export around finance batches

## 16. Change Impact

Estimated size:

- schema changes: medium
- controller/query changes: medium-large
- UI changes: medium-large
- workflow status migration: medium
- document generation changes: medium

This design is much safer than making both review and approval batch-based, because it preserves detailed case inspection while still giving finance a single approval packet.
