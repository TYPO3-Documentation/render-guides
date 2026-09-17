/**
 * Rebuilds the concatenated theme JavaScript whenever one of its sources
 * changes. This is what grunt-contrib-watch did; Node's own watcher does it
 * without a dependency, and assets are always built in a Linux container,
 * where recursive watching is supported.
 */
import { watch } from 'node:fs';
import { execSync } from 'node:child_process';

const rebuild = () => {
  try {
    execSync('npm run build:js', { stdio: 'inherit' });
  } catch {
    // A syntax error should not end the watch: report it and keep going.
  }
};

// Editors write a file in several steps, so wait for the burst to settle
// rather than minifying a half-written file.
let pending;
watch('assets/js', { recursive: true }, () => {
  clearTimeout(pending);
  pending = setTimeout(rebuild, 100);
});

rebuild();
