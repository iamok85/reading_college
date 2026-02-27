# Essay Image Agent Design

## Summary
Add a new `EssayImageAgent` that returns an OpenAI provider configured with the `gpt-image-1.5` model. The agent mirrors the simple structure of `ResearchAgent` and only overrides the provider selection.

## Goals
- Provide a dedicated agent class for essay image generation with a fixed OpenAI model.
- Match existing agent patterns for consistency and minimal risk.

## Non-Goals
- No new logging or prompt behavior.
- No new public methods beyond what `Agent` already provides.
- No refactor of existing agents.

## Architecture
- New class: `App\Neuron\Agents\EssayImageAgent`.
- Base class: `NeuronAI\Agent`.
- Method overrides:
  - `provider(): AIProviderInterface` returns `OpenAI` initialized with `OPENAI_API_KEY` and model `gpt-image-1.5`.

## Error Handling
- If `OPENAI_API_KEY` is missing or empty, throw an exception with a clear message (same behavior as `ResearchAgent`).

## Testing
- No new tests planned unless you want a unit test verifying the model string.

## Rollout
- Safe to deploy since this is additive and unused until wired.
