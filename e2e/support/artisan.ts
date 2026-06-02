import { execFileSync } from 'node:child_process';

/**
 * Run a host artisan command. The suite runs against each host's real `.env`
 * (production-style, single env — no swap), so there is nothing to back up or
 * restore: what we test is the actual deployment configuration.
 */
export function artisan(hostPath: string, args: string[]): string {
  return execFileSync('php', ['artisan', ...args], {
    cwd: hostPath,
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
    maxBuffer: 32 * 1024 * 1024,
  });
}
