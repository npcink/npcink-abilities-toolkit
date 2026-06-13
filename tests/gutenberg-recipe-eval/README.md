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

1. Export the deterministic default suite:

   ```bash
   composer eval:gutenberg-recipe:suite
   ```

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

## Eval-Lab Dual Judge

Provider-backed model calls live in `magick-ai-eval-lab`, not this plugin repo.
The eval-lab wrapper refreshes the deterministic suite, exports judge cases,
and then hands the CSV to eval-lab:

```bash
composer eval:gutenberg-recipe:judge:eval-lab
```

Use eval-lab's dry run when you only want to verify the handoff path:

```bash
composer eval:gutenberg-recipe:judge:eval-lab -- dry_run=true limit=3
```

Set `MAGICK_AI_EVAL_LAB_PATH` if the eval-lab checkout is not the default
sibling path:

```bash
MAGICK_AI_EVAL_LAB_PATH=/Users/muze/gitee/magick-ai-eval-lab \
composer eval:gutenberg-recipe:judge:eval-lab
```

Eval-lab owns `.env.evaluation.local`, model profiles, provider calls, and
generated cross-judge outputs. This repo only passes the case CSV path.

Default eval-lab output is under
`magick-ai-eval-lab/gutenberg-recipe/generated/`.
