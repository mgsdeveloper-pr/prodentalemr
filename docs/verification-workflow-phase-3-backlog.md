# Verification Workflow Phase 3 Backlog

Status: Deferred

This backlog captures capabilities intentionally deferred while the core verification request lifecycle is stabilized through the Phase 1 and Phase 2 workflow layer.

## Deferred Capabilities

- Saved queue filters for individual users and teams.
- Dedicated verification task records for sub-work assignment.
- Queue and event architecture for asynchronous workflow side effects.
- Advanced workflow automation rules.
- AI-ready insight surfaces and recommendation history.
- Compare-with-master template intelligence.

## Current Decision

Phase 1 and Phase 2 should focus on the core request lifecycle first:

- Verification request naming layer.
- Status, assignment, SLA, timeline, QA, escalation, delivery, and PDF services.
- Small action classes for reusable workflow operations.
- Backward-compatible integration with the existing `billing_work_items` table.

Phase 3 should begin only after the current workflow path is stable in the browser and covered by tests.
