# AGENTS.md

## Project principles
- This project uses HTML, CSS and vanilla JavaScript.
- Do not introduce a frontend framework.
- Avoid unnecessary dependencies.
- Prefer simple, readable code over abstraction.
- Keep browser compatibility in mind.

## Code style
- Use ES modules when appropriate.
- Keep DOM manipulation separate from business logic when possible.
- Use descriptive function names.
- Avoid large functions.
- Variables, functions and comments are written in English

## Before finishing a task
- Explain what files were changed.
- Mention any behavior changes.
- Run available tests or explain why none were run.
- Do not modify unrelated files.
- Verify if there is translation to add.

## Tests
- The project is tested manually through Docker.
- Start the local server from the repository root with:
  ```powershell
  docker compose up
  ```
- URL to test: http://localhost:8000
- Stop the test server with `Ctrl+C`.
- If needed, clean up containers with:
  ```powershell
  docker compose down
  ```
