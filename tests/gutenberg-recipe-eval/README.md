# Gutenberg Recipe Evaluation

This folder contains optional local evaluation tooling for the
`npcink-abilities-toolkit/evaluate-gutenberg-recipe-suite` read ability.

The runtime contract stays deterministic:

- the ability routes prompts and builds plans only;
- it does not create Core proposals;
- it does not execute WordPress writes;
- it does not call AI providers.

The Promptfoo files here are a development-only second-opinion layer. They are
intended for the same pattern used by the Toolbox summary evaluation workflow:
one deterministic gate first, then one or more AI judges to review the gate
outputs, then a cross-judge comparison to identify cases that need human review.

## Flow

1. Save an `evaluate-gutenberg-recipe-suite` response to:

   `tests/gutenberg-recipe-eval/generated/gutenberg-recipe-suite.json`

2. Export Promptfoo judge cases:

   ```bash
   composer eval:gutenberg-recipe:judge:cases
   ```

3. Run one judge:

   ```bash
   GUTENBERG_RECIPE_JUDGE_GRADER="openai:gpt-4.1-mini" \
   GUTENBERG_RECIPE_JUDGE_OUTPUT="tests/gutenberg-recipe-eval/generated/promptfoo-judge-primary.json" \
   composer eval:gutenberg-recipe:judge
   ```

4. Run a second judge with a different grader/output.

5. Compare the two judge outputs:

   ```bash
   php tests/gutenberg-recipe-eval/compare-ai-judge-results.php \
     primary=tests/gutenberg-recipe-eval/generated/promptfoo-judge-primary.json \
     secondary=tests/gutenberg-recipe-eval/generated/promptfoo-judge-secondary.json \
     primary_label=primary \
     secondary_label=secondary
   ```

Cases with missing judge results, low scores, large score gaps, failed judge
assertions, or risky reasons are marked for human review.
