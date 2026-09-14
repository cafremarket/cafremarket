# Graph Report - Support  (2026-09-15)

## Corpus Check
- 2 files · ~2,520 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 24 nodes · 28 edges · 3 communities (0 shown, 3 thin omitted)
- Extraction: 89% EXTRACTED · 11% INFERRED · 0% AMBIGUOUS · INFERRED: 3 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `905f72f6`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- DefaultPolicyContent
- PolicyPages
- App\Models\Page

## God Nodes (most connected - your core abstractions)
1. `DefaultPolicyContent` - 13 edges
2. `PolicyPages` - 10 edges

## Surprising Connections (you probably didn't know these)
- None detected - all connections are within the same source files.

## Import Cycles
- None detected.

## Communities (3 total, 3 thin omitted)

## Knowledge Gaps
- **3 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `DefaultPolicyContent` connect `DefaultPolicyContent` to `PolicyPages`, `App\Models\Page`?**
  _High betweenness centrality (0.673) - this node is a cross-community bridge._
- **Why does `PolicyPages` connect `PolicyPages` to `App\Models\Page`?**
  _High betweenness centrality (0.538) - this node is a cross-community bridge._
- **Are the 2 inferred relationships involving `DefaultPolicyContent` (e.g. with `.applyDefaultContent()` and `.resolveContent()`) actually correct?**
  _`DefaultPolicyContent` has 2 INFERRED edges - model-reasoned connections that need verification._