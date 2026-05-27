# Ability Contract

Every public ability should define:

- `ability_id`: stable `namespace/name`
- `label`
- `description`
- `category`
- `input_schema`: JSON Schema object
- `output_schema`: JSON Schema object
- `execute_callback`
- `permission_callback` or `capability`
- `meta.show_in_rest`
- `meta.mcp.annotations.readonly`
- `meta.mcp.annotations.destructive`
- `meta.mcp.annotations.idempotent`
- `meta.magick.channels` when Magick AI compatibility metadata is needed
- `risk_level`
- `requires_confirm`
- `contract_version`
- `deprecated`
- `successor`

Read-only abilities may return data directly.

Write-proposal abilities must return a proposal, preview, diff, or other reviewable artifact. They must not commit destructive WordPress changes directly.
