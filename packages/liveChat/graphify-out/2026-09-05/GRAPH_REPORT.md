# Graph Report - liveChat  (2026-09-04)

## Corpus Check
- 50 files · ~12,619 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 168 nodes · 216 edges · 42 communities (8 shown, 4 thin omitted)
- Extraction: 94% EXTRACTED · 6% INFERRED · 0% AMBIGUOUS · INFERRED: 13 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `b80d29c1`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- ConversationController.php
- ChatConversation
- manifest.json
- functions.php
- App\Models\User
- ChatSeeder.php
- SaveChatConversationRequest
- 2019_12_06_063720_create_chat_tables.php
- Changelog
- views/index.blade.php
- merchant/index.blade.php
- liveChat::partials.livechat_styles

## God Nodes (most connected - your core abstractions)
1. `ChatConversation` - 34 edges
2. `SaveChatConversationRequest` - 14 edges
3. `ConversationController` - 8 edges
4. `ChatConversationRequest` - 8 edges
5. `ViewChatConversationRequest` - 8 edges
6. `AdminChatController` - 6 edges
7. `livechat_message_for_attachment_only()` - 5 edges
8. `livechat_format_message_time()` - 5 edges
9. `livechat_socket_attachments_payload()` - 5 edges
10. `ChatController` - 5 edges

## Surprising Connections (you probably didn't know these)
- `ChatConversationRequest` --inherits--> `App\Http\Requests\Request`  [EXTRACTED]
  Cafrepay/packages/liveChat/src/Http/Requests/ChatConversationRequest.php →   _Bridges community 0 → community 6_

## Import Cycles
- None detected.

## Communities (42 total, 4 thin omitted)

### Community 0 - "ConversationController.php"
Cohesion: 0.12
Nodes (14): App\Events\Chat\NewMessageEvent, App\Http\Controllers\Controller, App\Http\Resources\ConversationResource, App\Models\Shop, App\Services\ChatSocketPublisher, Illuminate\Http\Request, Illuminate\Http\Resources\Json\JsonResource, Illuminate\Support\Facades\Gate (+6 more)

### Community 1 - "ChatConversation"
Cohesion: 0.11
Nodes (6): App\Common\Attachable, App\Common\Repliable, App\Models\BaseModel, App\Models\Customer, Illuminate\Database\Eloquent\SoftDeletes, ChatConversation

### Community 2 - "manifest.json"
Cohesion: 0.12
Nodes (16): active, author, compatible, dependency, description, dir, email, icon (+8 more)

### Community 3 - "functions.php"
Cohesion: 0.25
Nodes (7): livechat_format_message_time(), livechat_is_merchant_panel(), livechat_message_for_attachment_only(), livechat_socket_attachments_payload(), livechat_support_route(), livechat_support_route_name(), Shop

### Community 4 - "App\Models\User"
Cohesion: 0.23
Nodes (6): App\Common\PackageConfig, App\Helpers\Authorize, App\Models\User, Illuminate\Foundation\Support\Providers\AuthServiceProvider, LiveChatServiceProvider, ChatConversationPolicy

### Community 5 - "ChatSeeder.php"
Cohesion: 0.20
Nodes (6): App\Helpers\PackageSeeder, Carbon\Carbon, ChatSeeder, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Log, Uninstaller

### Community 6 - "SaveChatConversationRequest"
Cohesion: 0.27
Nodes (3): App\Http\Requests\Request, Illuminate\Support\Facades\Auth, SaveChatConversationRequest

### Community 7 - "2019_12_06_063720_create_chat_tables.php"
Cohesion: 0.25
Nodes (5): CreateChatTables, AddFbPageIdColumnToShopsTable, Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

## Knowledge Gaps
- **22 isolated node(s):** `id`, `slug`, `name`, `description`, `icon` (+17 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 94 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **4 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `ChatConversation` connect `ChatConversation` to `ConversationController.php`, `functions.php`, `App\Models\User`?**
  _High betweenness centrality (0.162) - this node is a cross-community bridge._
- **Why does `SaveChatConversationRequest` connect `SaveChatConversationRequest` to `ConversationController.php`, `functions.php`?**
  _High betweenness centrality (0.045) - this node is a cross-community bridge._
- **What connects `id`, `slug`, `name` to the rest of the system?**
  _22 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `ConversationController.php` be split into smaller, more focused modules?**
  _Cohesion score 0.1206896551724138 - nodes in this community are weakly interconnected._
- **Should `ChatConversation` be split into smaller, more focused modules?**
  _Cohesion score 0.11428571428571428 - nodes in this community are weakly interconnected._
- **Should `manifest.json` be split into smaller, more focused modules?**
  _Cohesion score 0.11764705882352941 - nodes in this community are weakly interconnected._