# Graph Report - affiliate  (2026-09-22)

## Corpus Check
- 105 files · ~16,896 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 355 nodes · 502 edges · 83 communities (14 shown, 20 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS · INFERRED: 2 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `42d5d60e`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Migrations\Migration
- AffiliateCommission
- Affiliate
- App\Http\Controllers\Controller
- AffiliateLink
- Illuminate\Http\Request
- manifest.json
- LoginController
- AffiliateAttributionService.php
- master_layout.blade.php
- AffiliateServiceProvider
- dashboard/index.blade.php
- links.blade.php
- admin/index.blade.php
- AffiliateFacade
- Changelog
- affiliate_commissions.blade.php
- _order_page_commission_table.blade.php
- auth_layout.blade.php
- affiliate_field.blade.php
- wallet_index.blade.php
- admin.partials._password_fields
- affiliate::_affiliate_link_form
- affiliate::backend._create_link_modal
- affiliate::frontend.affiliate_link.form
- affiliate::frontend._create_link_modal
- affiliate::frontend._demo_login
- affiliate::partials._password_form
- affiliate::partials._status
- affiliate::scripts.dashboard_charts
- partials._addon_badge
- admin/create.blade.php
- admin/edit.blade.php
- profile.blade.php

## God Nodes (most connected - your core abstractions)
1. `Affiliate` - 31 edges
2. `AffiliateLink` - 31 edges
3. `AffiliateAttributionService` - 25 edges
4. `AffiliateCommission` - 20 edges
5. `AffiliateController` - 14 edges
6. `AffiliateLinkController` - 12 edges
7. `LoginController` - 11 edges
8. `AffiliateCommissionService` - 9 edges
9. `AccountController` - 7 edges
10. `AttributionController` - 7 edges

## Surprising Connections (you probably didn't know these)
- `affiliate_link_exists()` --calls--> `AffiliateLink`  [EXTRACTED]
  src/Helpers/functions.php → src/Models/AffiliateLink.php
- `current_affiliates_link_for_item()` --calls--> `AffiliateLink`  [EXTRACTED]
  src/Helpers/functions.php → src/Models/AffiliateLink.php
- `AffiliateCommissionService` --references--> `AffiliateAttributionService`  [EXTRACTED]
  src/Services/AffiliateCommissionService.php → src/Services/AffiliateAttributionService.php
- `AttributionController` --references--> `AffiliateAttributionService`  [EXTRACTED]
  src/Http/Controllers/Api/AttributionController.php → src/Services/AffiliateAttributionService.php

## Import Cycles
- None detected.

## Communities (83 total, 20 thin omitted)

### Community 0 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.08
Nodes (9): CreateAffiliatesTable, CreateAffiliateLinkTable, CreateAffiliateCommissionsTable, EnableAffiliateAndConfigs, Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Log (+1 more)

### Community 1 - "AffiliateCommission"
Cohesion: 0.09
Nodes (10): App\Models\Order, Exception, Illuminate\Database\Eloquent\Model, Illuminate\Database\QueryException, Illuminate\Support\Collection, Illuminate\Support\Str, CommissionController, AffiliateCommission (+2 more)

### Community 2 - "Affiliate"
Cohesion: 0.11
Nodes (14): App\Common\Addressable, App\Common\ApiAuthTokens, App\Common\Billable, App\Common\HasHumanAttributes, App\Common\Imageable, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\SoftDeletes, Illuminate\Foundation\Auth\User (+6 more)

### Community 3 - "App\Http\Controllers\Controller"
Cohesion: 0.09
Nodes (11): App\Http\Controllers\Controller, Illuminate\Foundation\Auth\RegistersUsers, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Route, Illuminate\Validation\Rule, Illuminate\Validation\ValidationException, Incevio\Package\Wallet\Http\Controllers\WithdrawalController, AccountController (+3 more)

### Community 4 - "AffiliateLink"
Cohesion: 0.11
Nodes (8): App\Models\Inventory, App\Models\Shop, Inventory, affiliate_link_exists(), current_affiliates_link_for_item(), AffiliateLinkController, FrontController, AffiliateLink

### Community 5 - "Illuminate\Http\Request"
Cohesion: 0.20
Nodes (5): App\Http\Resources\ItemResource, Carbon, Illuminate\Http\Request, AttributionController, AffiliateAttributionService

### Community 6 - "manifest.json"
Cohesion: 0.12
Nodes (16): active, author, compatible, dependency, description, dir, email, icon (+8 more)

### Community 7 - "LoginController"
Cohesion: 0.31
Nodes (3): App\Services\Auth\JwtAuthService, Illuminate\Foundation\Auth\AuthenticatesUsers, LoginController

### Community 8 - "AffiliateAttributionService.php"
Cohesion: 0.22
Nodes (7): App\Models\PaymentMethod, Carbon\Carbon, AffiliateSeeder, Illuminate\Database\Seeder, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\Cookie, Illuminate\Support\Facades\Session

### Community 9 - "master_layout.blade.php"
Cohesion: 0.25
Nodes (7): admin.notification, admin.partials.ui.alerts, affiliate::backend._page_header, affiliate::backend._sidebar, affiliate::scripts.footer_js, otp-login::scripts, scripts.password_toggle

### Community 10 - "AffiliateServiceProvider"
Cohesion: 0.38
Nodes (3): App\Common\PackageConfig, Illuminate\Foundation\Support\Providers\AuthServiceProvider, AffiliateServiceProvider

### Community 11 - "dashboard/index.blade.php"
Cohesion: 0.40
Nodes (4): affiliate::backend.dashboard._chart, affiliate::backend.dashboard._ranking_lists, affiliate::backend.dashboard._top_cards, plugins.ionic

### Community 12 - "links.blade.php"
Cohesion: 0.50
Nodes (3): admin.partials.ui.trash_start, admin.partials.ui.card_end, admin.partials.ui.card_start

### Community 13 - "admin/index.blade.php"
Cohesion: 0.50
Nodes (3): affiliate::scripts.datatable, admin.partials.ui.card_end, admin.partials.ui.card_start

## Knowledge Gaps
- **57 isolated node(s):** `id`, `slug`, `name`, `description`, `icon` (+52 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 186 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **20 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `AffiliateLink` connect `AffiliateLink` to `AffiliateAttributionService.php`, `AffiliateCommission`, `Affiliate`, `Illuminate\Http\Request`?**
  _High betweenness centrality (0.076) - this node is a cross-community bridge._
- **Why does `Affiliate` connect `Affiliate` to `App\Http\Controllers\Controller`, `LoginController`?**
  _High betweenness centrality (0.057) - this node is a cross-community bridge._
- **What connects `id`, `slug`, `name` to the rest of the system?**
  _57 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Migrations\Migration` be split into smaller, more focused modules?**
  _Cohesion score 0.07948717948717948 - nodes in this community are weakly interconnected._
- **Should `AffiliateCommission` be split into smaller, more focused modules?**
  _Cohesion score 0.0907563025210084 - nodes in this community are weakly interconnected._
- **Should `Affiliate` be split into smaller, more focused modules?**
  _Cohesion score 0.11264367816091954 - nodes in this community are weakly interconnected._
- **Should `App\Http\Controllers\Controller` be split into smaller, more focused modules?**
  _Cohesion score 0.09425287356321839 - nodes in this community are weakly interconnected._