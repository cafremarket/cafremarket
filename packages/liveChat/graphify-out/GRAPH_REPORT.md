# Graph Report - liveChat  (2026-09-14)

## Corpus Check
- 53 files · ~16,690 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 183 nodes · 253 edges · 44 communities (8 shown, 3 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 17 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `14a02422`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- ConversationController.php
- ChatConversation
- manifest.json
- SaveChatConversationRequest
- App\Models\User
- ChatSeeder.php
- 2019_12_06_063720_create_chat_tables.php
- Changelog
- views/index.blade.php
- liveChat::partials.livechat_styles
- functions.php

## God Nodes (most connected - your core abstractions)
1. `ChatConversation` - 42 edges
2. `SaveChatConversationRequest` - 16 edges
3. `ConversationController` - 8 edges
4. `ChatConversationRequest` - 8 edges
5. `ViewChatConversationRequest` - 8 edges
6. `livechat_message_for_attachment_only()` - 6 edges
7. `livechat_format_message_time()` - 6 edges
8. `livechat_socket_attachments_payload()` - 6 edges
9. `AdminChatController` - 6 edges
10. `CustomerChatController` - 6 edges

## Surprising Connections (you probably didn't know these)
- `AdminChatController` --inherits--> `App\Http\Controllers\Controller`  [EXTRACTED]
  Cafrepay/packages/liveChat/src/Http/Controllers/AdminChatController.php →   _Bridges community 42 → community 0_
- `CustomerChatController` --inherits--> `App\Http\Controllers\Controller`  [EXTRACTED]
  Cafrepay/packages/liveChat/src/Http/Controllers/CustomerChatController.php →   _Bridges community 3 → community 0_

## Import Cycles
- None detected.

## Communities (44 total, 3 thin omitted)

### Community 0 - "ConversationController.php"
Cohesion: 0.12
Nodes (16): App\Events\Chat\NewMessageEvent, App\Http\Controllers\Controller, App\Http\Requests\Request, App\Http\Resources\ConversationResource, App\Models\Customer, App\Models\Shop, App\Services\ChatSocketPublisher, Illuminate\Auth\Access\AuthorizationException (+8 more)

### Community 1 - "ChatConversation"
Cohesion: 0.09
Nodes (6): App\Common\Attachable, App\Common\Repliable, App\Models\BaseModel, App\Models\Order, Illuminate\Database\Eloquent\SoftDeletes, ChatConversation

### Community 2 - "manifest.json"
Cohesion: 0.12
Nodes (16): active, author, compatible, dependency, description, dir, email, icon (+8 more)

### Community 3 - "SaveChatConversationRequest"
Cohesion: 0.22
Nodes (6): livechat_format_message_time(), livechat_message_for_attachment_only(), livechat_socket_attachments_payload(), Shop, CustomerChatController, SaveChatConversationRequest

### Community 4 - "App\Models\User"
Cohesion: 0.23
Nodes (6): App\Common\PackageConfig, App\Helpers\Authorize, App\Models\User, Illuminate\Foundation\Support\Providers\AuthServiceProvider, LiveChatServiceProvider, ChatConversationPolicy

### Community 5 - "ChatSeeder.php"
Cohesion: 0.20
Nodes (6): App\Helpers\PackageSeeder, Carbon\Carbon, ChatSeeder, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Log, Uninstaller

### Community 7 - "2019_12_06_063720_create_chat_tables.php"
Cohesion: 0.25
Nodes (5): CreateChatTables, AddFbPageIdColumnToShopsTable, Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

### Community 42 - "functions.php"
Cohesion: 0.17
Nodes (8): Illuminate\Support\Facades\Route, Order, livechat_build_order_share_message(), livechat_build_order_share_payload(), livechat_is_merchant_panel(), livechat_support_route(), livechat_support_route_name(), AdminChatController

## Knowledge Gaps
- **20 isolated node(s):** `id`, `slug`, `name`, `description`, `icon` (+15 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 102 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **3 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `ChatConversation` connect `ChatConversation` to `ConversationController.php`, `functions.php`, `SaveChatConversationRequest`, `App\Models\User`?**
  _High betweenness centrality (0.219) - this node is a cross-community bridge._
- **Why does `SaveChatConversationRequest` connect `SaveChatConversationRequest` to `ConversationController.php`?**
  _High betweenness centrality (0.054) - this node is a cross-community bridge._
- **Why does `ViewChatConversationRequest` connect `ConversationController.php` to `functions.php`?**
  _High betweenness centrality (0.021) - this node is a cross-community bridge._
- **What connects `id`, `slug`, `name` to the rest of the system?**
  _20 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `ConversationController.php` be split into smaller, more focused modules?**
  _Cohesion score 0.12096774193548387 - nodes in this community are weakly interconnected._
- **Should `ChatConversation` be split into smaller, more focused modules?**
  _Cohesion score 0.09333333333333334 - nodes in this community are weakly interconnected._
- **Should `manifest.json` be split into smaller, more focused modules?**
  _Cohesion score 0.11764705882352941 - nodes in this community are weakly interconnected._